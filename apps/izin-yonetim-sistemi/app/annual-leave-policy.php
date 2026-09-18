<?php

declare(strict_types=1);

function annual_leave_policy_tiers(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT id, min_completed_years, max_completed_years, company_days, legal_minimum_days, sort_order
         FROM annual_leave_policy_tiers
         WHERE is_active = 1
         ORDER BY min_completed_years ASC, sort_order ASC, id ASC"
    );

    return $stmt->fetchAll() ?: [];
}

function annual_leave_age_rules(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT id, min_age, max_age, legal_minimum_days, sort_order
         FROM annual_leave_age_rules
         WHERE is_active = 1
         ORDER BY sort_order ASC, id ASC"
    );

    return $stmt->fetchAll() ?: [];
}

function annual_leave_service_anniversary(DateTimeImmutable $hireDate, int $completedYears): DateTimeImmutable
{
    if ($completedYears < 0) {
        throw new InvalidArgumentException('Tamamlanan hizmet yılı negatif olamaz.');
    }

    if ($completedYears === 0) {
        return $hireDate;
    }

    // Avoid PHP's Feb-29 + N years rollover to March by clamping to the
    // last valid day of the target month.
    $targetYear = (int) $hireDate->format('Y') + $completedYears;
    $month = (int) $hireDate->format('m');
    $day = (int) $hireDate->format('d');

    $first = new DateTimeImmutable(sprintf('%04d-%02d-01', $targetYear, $month), $hireDate->getTimezone());
    $maxDay = (int) $first->format('t');
    $safeDay = min($day, $maxDay);

    return new DateTimeImmutable(
        sprintf('%04d-%02d-%02d', $targetYear, $month, $safeDay),
        $hireDate->getTimezone()
    );
}

function annual_leave_completed_years(DateTimeImmutable $hireDate, DateTimeImmutable $asOf): int
{
    if ($asOf < $hireDate) {
        return 0;
    }

    $years = max(0, (int) $hireDate->diff($asOf)->y);

    while ($years > 0 && annual_leave_service_anniversary($hireDate, $years) > $asOf) {
        $years--;
    }

    while (annual_leave_service_anniversary($hireDate, $years + 1) <= $asOf) {
        $years++;
    }

    return $years;
}

function annual_leave_age_on(?string $birthDate, DateTimeImmutable $onDate): ?int
{
    if ($birthDate === null || trim($birthDate) === '') {
        return null;
    }

    $birth = parse_leave_date($birthDate);
    if ($birth === null || $birth > $onDate) {
        return null;
    }

    return (int) $birth->diff($onDate)->y;
}

function annual_leave_tier_for_year(PDO $pdo, int $completedYears): ?array
{
    foreach (annual_leave_policy_tiers($pdo) as $tier) {
        $min = (int) $tier['min_completed_years'];
        $max = $tier['max_completed_years'] !== null ? (int) $tier['max_completed_years'] : null;

        if ($completedYears >= $min && ($max === null || $completedYears <= $max)) {
            return $tier;
        }
    }

    return null;
}

function annual_leave_age_minimum(PDO $pdo, ?int $age): float
{
    if ($age === null) {
        return 0.0;
    }

    $minimum = 0.0;

    foreach (annual_leave_age_rules($pdo) as $rule) {
        $minAge = $rule['min_age'] !== null ? (int) $rule['min_age'] : null;
        $maxAge = $rule['max_age'] !== null ? (int) $rule['max_age'] : null;

        if (($minAge === null || $age >= $minAge) && ($maxAge === null || $age <= $maxAge)) {
            $minimum = max($minimum, (float) $rule['legal_minimum_days']);
        }
    }

    return $minimum;
}

function annual_leave_employee(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare(
        "SELECT id, full_name, role, is_active, hire_date, birth_date
         FROM users
         WHERE id = :id
         LIMIT 1"
    );
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function sync_annual_leave_entitlements(PDO $pdo, int $userId, ?string $asOfDate = null): array
{
    $employee = annual_leave_employee($pdo, $userId);

    if (!$employee || (string) $employee['role'] !== 'employee') {
        throw new DomainException('Çalışan bulunamadı.');
    }

    $hireDateRaw = trim((string) ($employee['hire_date'] ?? ''));
    if ($hireDateRaw === '') {
        return [];
    }

    $hireDate = parse_leave_date($hireDateRaw);
    if ($hireDate === null) {
        throw new DomainException('Çalışanın işe giriş tarihi geçersiz.');
    }

    $asOf = $asOfDate !== null ? parse_leave_date($asOfDate) : new DateTimeImmutable('today');
    if ($asOf === null) {
        throw new InvalidArgumentException('Geçersiz hak ediş tarihi.');
    }

    $completed = annual_leave_completed_years($hireDate, $asOf);

    if ($completed < 1) {
        return [];
    }

    $insert = $pdo->prepare(
        "INSERT IGNORE INTO annual_leave_entitlements
         (user_id, service_year_number, service_period_start, service_period_end, earned_on,
          entitlement_days, company_policy_days, legal_minimum_days, age_minimum_days, policy_snapshot_json)
         VALUES
         (:user_id, :service_year_number, :service_period_start, :service_period_end, :earned_on,
          :entitlement_days, :company_policy_days, :legal_minimum_days, :age_minimum_days, :policy_snapshot_json)"
    );

    for ($serviceYear = 1; $serviceYear <= $completed; $serviceYear++) {
        $tier = annual_leave_tier_for_year($pdo, $serviceYear);
        if ($tier === null) {
            throw new DomainException(
                sprintf('%d. hizmet yılı için yıllık izin politikası tanımlanmamış.', $serviceYear)
            );
        }

        $periodStart = annual_leave_service_anniversary($hireDate, $serviceYear - 1);
        $earnedOn = annual_leave_service_anniversary($hireDate, $serviceYear);
        $periodEnd = $earnedOn->modify('-1 day');

        $age = annual_leave_age_on(
            $employee['birth_date'] !== null ? (string) $employee['birth_date'] : null,
            $earnedOn
        );

        $companyDays = (float) $tier['company_days'];
        $legalMinimum = (float) $tier['legal_minimum_days'];
        $ageMinimum = annual_leave_age_minimum($pdo, $age);
        $entitlement = max($companyDays, $legalMinimum, $ageMinimum);

        $snapshot = [
            'service_year_number' => $serviceYear,
            'tier_id' => (int) $tier['id'],
            'company_days' => $companyDays,
            'legal_minimum_days' => $legalMinimum,
            'age' => $age,
            'age_minimum_days' => $ageMinimum,
            'effective_entitlement_days' => $entitlement,
            'accrual_basis' => 'service_anniversary',
        ];

        $insert->execute([
            'user_id' => $userId,
            'service_year_number' => $serviceYear,
            'service_period_start' => $periodStart->format('Y-m-d'),
            'service_period_end' => $periodEnd->format('Y-m-d'),
            'earned_on' => $earnedOn->format('Y-m-d'),
            'entitlement_days' => $entitlement,
            'company_policy_days' => $companyDays,
            'legal_minimum_days' => $legalMinimum,
            'age_minimum_days' => $ageMinimum,
            'policy_snapshot_json' => json_encode(
                $snapshot,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            ),
        ]);
    }

    return annual_leave_entitlements($pdo, $userId);
}

function annual_leave_entitlements(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        "SELECT id, user_id, service_year_number, service_period_start, service_period_end, earned_on,
                entitlement_days, company_policy_days, legal_minimum_days, age_minimum_days,
                policy_snapshot_json, created_at
         FROM annual_leave_entitlements
         WHERE user_id = :user_id
         ORDER BY earned_on ASC, id ASC"
    );
    $stmt->execute(['user_id' => $userId]);

    return $stmt->fetchAll() ?: [];
}

function annual_leave_reservations(PDO $pdo, int $userId, ?int $excludeRequestId = null): array
{
    $sql =
        "SELECT lrd.leave_date, lrd.day_value, lr.status, lr.id AS request_id
         FROM leave_request_days lrd
         INNER JOIN leave_requests lr ON lr.id = lrd.leave_request_id
         INNER JOIN leave_types lt ON lt.id = lr.leave_type_id
         WHERE lr.user_id = :user_id
           AND lt.deducts_annual_allowance = 1
           AND lr.status IN ('approved', 'pending')";

    $params = ['user_id' => $userId];

    if ($excludeRequestId !== null) {
        $sql .= ' AND lr.id <> :exclude_request_id';
        $params['exclude_request_id'] = $excludeRequestId;
    }

    $sql .= ' ORDER BY lrd.leave_date ASC, lr.id ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll() ?: [];
}

function annual_leave_assert_request_available(
    PDO $pdo,
    int $userId,
    array $newDays,
    ?int $excludeRequestId = null
): void {
    if ($newDays === []) {
        throw new DomainException('Yıllık izin için hesaplanabilir gün bulunamadı.');
    }

    $maxDate = null;
    foreach ($newDays as $day) {
        $date = trim((string) ($day['date'] ?? ''));
        $value = (float) ($day['value'] ?? 0);

        if (parse_leave_date($date) === null || $value <= 0) {
            throw new DomainException('Yıllık izin gün kaydı geçersiz.');
        }

        if ($maxDate === null || $date > $maxDate) {
            $maxDate = $date;
        }
    }

    sync_annual_leave_entitlements($pdo, $userId, $maxDate);

    $entitlements = annual_leave_entitlements($pdo, $userId);
    $reservations = annual_leave_reservations($pdo, $userId, $excludeRequestId);

    $events = [];

    foreach ($entitlements as $entitlement) {
        $date = (string) $entitlement['earned_on'];
        $events[$date]['earned'] = ($events[$date]['earned'] ?? 0.0) + (float) $entitlement['entitlement_days'];
    }

    foreach ($reservations as $reservation) {
        $date = (string) $reservation['leave_date'];
        $events[$date]['reserved'] = ($events[$date]['reserved'] ?? 0.0) + (float) $reservation['day_value'];
    }

    foreach ($newDays as $day) {
        $date = (string) $day['date'];
        $events[$date]['reserved'] = ($events[$date]['reserved'] ?? 0.0) + (float) $day['value'];
    }

    ksort($events);

    $earned = 0.0;
    $reserved = 0.0;

    foreach ($events as $date => $event) {
        $earned += (float) ($event['earned'] ?? 0.0);
        $reserved += (float) ($event['reserved'] ?? 0.0);

        if (($reserved - $earned) > 0.0001) {
            throw new DomainException(
                sprintf(
                    '%s tarihi itibarıyla hak edilmiş yıllık izin bakiyesi yeterli değil. Henüz kazanılmamış gelecek izin hakkı kullanılamaz.',
                    $date
                )
            );
        }
    }
}

function annual_leave_balance(PDO $pdo, int $userId, ?string $asOfDate = null): array
{
    $asOf = $asOfDate !== null ? parse_leave_date($asOfDate) : new DateTimeImmutable('today');
    if ($asOf === null) {
        throw new InvalidArgumentException('Geçersiz bakiye tarihi.');
    }

    sync_annual_leave_entitlements($pdo, $userId, $asOf->format('Y-m-d'));

    $entitlements = array_values(array_filter(
        annual_leave_entitlements($pdo, $userId),
        static fn (array $row): bool => (string) $row['earned_on'] <= $asOf->format('Y-m-d')
    ));

    $totalEntitlement = array_sum(array_map(
        static fn (array $row): float => (float) $row['entitlement_days'],
        $entitlements
    ));

    $approved = 0.0;
    $pending = 0.0;

    foreach (annual_leave_reservations($pdo, $userId) as $reservation) {
        if ((string) $reservation['status'] === 'approved') {
            $approved += (float) $reservation['day_value'];
        } elseif ((string) $reservation['status'] === 'pending') {
            $pending += (float) $reservation['day_value'];
        }
    }

    $remainingAfterApproved = max(0.0, $totalEntitlement - $approved);
    $availableAfterPending = max(0.0, $totalEntitlement - $approved - $pending);

    return [
        'entitlement' => round($totalEntitlement, 2),
        'approved' => round($approved, 2),
        'pending' => round($pending, 2),
        'remaining' => round($remainingAfterApproved, 2),
        'available_after_pending' => round($availableAfterPending, 2),
        'carryover_enabled' => app_setting('annual_leave_unused_carryover', '1') === '1',
        'cashout_while_active_allowed' => app_setting('annual_leave_active_cashout_allowed', '0') === '1',
        'entitlements' => $entitlements,
    ];
}

function annual_leave_next_entitlement(PDO $pdo, int $userId, ?string $asOfDate = null): ?array
{
    $employee = annual_leave_employee($pdo, $userId);
    if (!$employee || empty($employee['hire_date'])) {
        return null;
    }

    $hireDate = parse_leave_date((string) $employee['hire_date']);
    $asOf = $asOfDate !== null ? parse_leave_date($asOfDate) : new DateTimeImmutable('today');

    if ($hireDate === null || $asOf === null) {
        return null;
    }

    $completed = annual_leave_completed_years($hireDate, $asOf);
    $nextYear = max(1, $completed + 1);
    $earnedOn = annual_leave_service_anniversary($hireDate, $nextYear);
    $tier = annual_leave_tier_for_year($pdo, $nextYear);

    if ($tier === null) {
        return null;
    }

    $age = annual_leave_age_on(
        $employee['birth_date'] !== null ? (string) $employee['birth_date'] : null,
        $earnedOn
    );
    $ageMinimum = annual_leave_age_minimum($pdo, $age);
    $companyDays = (float) $tier['company_days'];
    $legalMinimum = (float) $tier['legal_minimum_days'];

    return [
        'service_year_number' => $nextYear,
        'earned_on' => $earnedOn->format('Y-m-d'),
        'days' => max($companyDays, $legalMinimum, $ageMinimum),
        'company_days' => $companyDays,
        'legal_minimum_days' => $legalMinimum,
        'age_minimum_days' => $ageMinimum,
        'age' => $age,
    ];
}
