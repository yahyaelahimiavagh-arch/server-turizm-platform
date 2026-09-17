<?php

declare(strict_types=1);

final class LeaveRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function activeLeaveTypes(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, code, name, deducts_annual_allowance, color_hex
             FROM leave_types
             WHERE is_active = 1
             ORDER BY sort_order, name'
        );

        return $stmt->fetchAll();
    }

    public function findLeaveType(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, code, name, deducts_annual_allowance, color_hex, is_active
             FROM leave_types WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function allowanceSummary(int $userId, int $year): array
    {
        $users = new UserRepository($this->pdo);
        $entitlement = $users->ensureAllowance($userId, $year);

        $stmt = $this->pdo->prepare(
            "SELECT lr.status, COALESCE(SUM(lrd.day_value), 0) AS total
             FROM leave_request_days lrd
             INNER JOIN leave_requests lr ON lr.id = lrd.leave_request_id
             INNER JOIN leave_types lt ON lt.id = lr.leave_type_id
             WHERE lr.user_id = :user_id
               AND lt.deducts_annual_allowance = 1
               AND YEAR(lrd.leave_date) = :year
               AND lr.status IN ('approved', 'pending')
             GROUP BY lr.status"
        );
        $stmt->execute(['user_id' => $userId, 'year' => $year]);

        $approved = 0.0;
        $pending = 0.0;
        foreach ($stmt->fetchAll() as $row) {
            if ($row['status'] === 'approved') {
                $approved = (float) $row['total'];
            } elseif ($row['status'] === 'pending') {
                $pending = (float) $row['total'];
            }
        }

        return [
            'entitlement' => $entitlement,
            'approved' => $approved,
            'pending' => $pending,
            'remaining' => max(0.0, $entitlement - $approved),
            'available_after_pending' => max(0.0, $entitlement - $approved - $pending),
        ];
    }

    public function approvedBreakdown(int $userId, int $year): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT lt.id, lt.name, lt.color_hex, COALESCE(SUM(lrd.day_value), 0) AS total
             FROM leave_types lt
             LEFT JOIN leave_requests lr
               ON lr.leave_type_id = lt.id
              AND lr.user_id = :user_id
              AND lr.status = 'approved'
             LEFT JOIN leave_request_days lrd
               ON lrd.leave_request_id = lr.id
              AND YEAR(lrd.leave_date) = :year
             WHERE lt.is_active = 1 OR lr.id IS NOT NULL
             GROUP BY lt.id, lt.name, lt.color_hex, lt.sort_order
             ORDER BY lt.sort_order, lt.name"
        );
        $stmt->execute(['user_id' => $userId, 'year' => $year]);
        return $stmt->fetchAll();
    }

    public function recentRequests(int $userId, int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $stmt = $this->pdo->prepare(
            "SELECT lr.id, lt.name AS leave_type_name, lr.start_date, lr.end_date,
                    lr.duration_type, lr.half_day_period, lr.requested_days, lr.status,
                    lr.employee_comment, lr.admin_note, lr.created_at
             FROM leave_requests lr
             INNER JOIN leave_types lt ON lt.id = lr.leave_type_id
             WHERE lr.user_id = :user_id
             ORDER BY lr.created_at DESC
             LIMIT {$limit}"
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function userRequests(int $userId): array
    {
        return $this->recentRequests($userId, 200);
    }

    public function createRequest(
        int $userId,
        int $leaveTypeId,
        string $startDate,
        string $endDate,
        string $durationType,
        ?string $halfDayPeriod,
        ?string $comment,
        array $calculation
    ): int {
        $leaveType = $this->findLeaveType($leaveTypeId);
        if (!$leaveType || (int) $leaveType['is_active'] !== 1) {
            throw new DomainException('Seçilen izin türü kullanılamıyor.');
        }

        $total = (float) ($calculation['total'] ?? 0);
        $days = $calculation['days'] ?? [];
        if ($total <= 0 || !is_array($days) || $days === []) {
            throw new DomainException('Seçilen tarihlerde hesaplanabilir izin günü yok.');
        }

        $this->pdo->beginTransaction();
        try {
            if ((int) $leaveType['deducts_annual_allowance'] === 1) {
                $this->assertAllowanceAvailable($userId, $days);
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO leave_requests
                 (user_id, leave_type_id, start_date, end_date, duration_type, half_day_period,
                  requested_days, status, employee_comment)
                 VALUES
                 (:user_id, :leave_type_id, :start_date, :end_date, :duration_type, :half_day_period,
                  :requested_days, 'pending', :employee_comment)"
            );
            $stmt->execute([
                'user_id' => $userId,
                'leave_type_id' => $leaveTypeId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'duration_type' => $durationType,
                'half_day_period' => $halfDayPeriod,
                'requested_days' => $total,
                'employee_comment' => $comment ?: null,
            ]);

            $requestId = (int) $this->pdo->lastInsertId();
            $dayStmt = $this->pdo->prepare(
                'INSERT INTO leave_request_days (leave_request_id, leave_date, day_value)
                 VALUES (:request_id, :leave_date, :day_value)'
            );

            foreach ($days as $day) {
                $dayStmt->execute([
                    'request_id' => $requestId,
                    'leave_date' => $day['date'],
                    'day_value' => $day['value'],
                ]);
            }

            $this->pdo->commit();
            return $requestId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function assertAllowanceAvailable(int $userId, array $newDays, ?int $excludeRequestId = null): void
    {
        $byYear = [];
        foreach ($newDays as $day) {
            $year = (int) substr((string) $day['date'], 0, 4);
            $byYear[$year] = ($byYear[$year] ?? 0.0) + (float) $day['value'];
        }

        $users = new UserRepository($this->pdo);
        foreach ($byYear as $year => $newTotal) {
            $entitlement = $users->ensureAllowance($userId, (int) $year);

            $sql = "SELECT COALESCE(SUM(lrd.day_value), 0)
                    FROM leave_request_days lrd
                    INNER JOIN leave_requests lr ON lr.id = lrd.leave_request_id
                    INNER JOIN leave_types lt ON lt.id = lr.leave_type_id
                    WHERE lr.user_id = :user_id
                      AND lt.deducts_annual_allowance = 1
                      AND YEAR(lrd.leave_date) = :year
                      AND lr.status IN ('approved', 'pending')";
            $params = ['user_id' => $userId, 'year' => $year];

            if ($excludeRequestId !== null) {
                $sql .= ' AND lr.id <> :exclude_request_id';
                $params['exclude_request_id'] = $excludeRequestId;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $reserved = (float) $stmt->fetchColumn();

            if (($reserved + $newTotal) - $entitlement > 0.0001) {
                throw new DomainException(
                    sprintf('%d yılı için yeterli yıllık izin bakiyesi yok.', $year)
                );
            }
        }
    }

    public function pendingRequests(): array
    {
        $stmt = $this->pdo->query(
            "SELECT lr.id, u.full_name, u.email, lt.name AS leave_type_name,
                    lr.start_date, lr.end_date, lr.duration_type, lr.half_day_period,
                    lr.requested_days, lr.employee_comment, lr.created_at
             FROM leave_requests lr
             INNER JOIN users u ON u.id = lr.user_id
             INNER JOIN leave_types lt ON lt.id = lr.leave_type_id
             WHERE lr.status = 'pending'
             ORDER BY lr.created_at ASC"
        );
        return $stmt->fetchAll();
    }

    public function processRequest(int $requestId, int $adminId, string $decision, ?string $adminNote): void
    {
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            throw new InvalidArgumentException('Geçersiz karar.');
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "SELECT lr.id, lr.user_id, lr.status, lt.deducts_annual_allowance
                 FROM leave_requests lr
                 INNER JOIN leave_types lt ON lt.id = lr.leave_type_id
                 WHERE lr.id = :id
                 FOR UPDATE"
            );
            $stmt->execute(['id' => $requestId]);
            $request = $stmt->fetch();

            if (!$request) {
                throw new DomainException('İzin talebi bulunamadı.');
            }
            if ($request['status'] !== 'pending') {
                throw new DomainException('Bu talep daha önce işlenmiş.');
            }

            if ($decision === 'approved' && (int) $request['deducts_annual_allowance'] === 1) {
                $daysStmt = $this->pdo->prepare(
                    'SELECT leave_date AS date, day_value AS value
                     FROM leave_request_days
                     WHERE leave_request_id = :request_id'
                );
                $daysStmt->execute(['request_id' => $requestId]);
                $this->assertAllowanceAvailable((int) $request['user_id'], $daysStmt->fetchAll(), $requestId);
            }

            $update = $this->pdo->prepare(
                'UPDATE leave_requests
                 SET status = :status,
                     admin_note = :admin_note,
                     processed_by = :processed_by,
                     processed_at = NOW()
                 WHERE id = :id'
            );
            $update->execute([
                'status' => $decision,
                'admin_note' => $adminNote ?: null,
                'processed_by' => $adminId,
                'id' => $requestId,
            ]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function dashboardCounts(): array
    {
        $employees = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM users WHERE role = 'employee' AND is_active = 1"
        )->fetchColumn();

        $pending = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'"
        )->fetchColumn();

        $todayStmt = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT lr.user_id)
             FROM leave_request_days lrd
             INNER JOIN leave_requests lr ON lr.id = lrd.leave_request_id
             WHERE lrd.leave_date = :today AND lr.status = 'approved'"
        );
        $todayStmt->execute(['today' => date('Y-m-d')]);
        $today = (int) $todayStmt->fetchColumn();

        $monthStmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(lrd.day_value), 0)
             FROM leave_request_days lrd
             INNER JOIN leave_requests lr ON lr.id = lrd.leave_request_id
             WHERE lr.status = 'approved'
               AND YEAR(lrd.leave_date) = :year
               AND MONTH(lrd.leave_date) = :month"
        );
        $monthStmt->execute([
            'year' => (int) date('Y'),
            'month' => (int) date('n'),
        ]);

        return [
            'employees' => $employees,
            'pending' => $pending,
            'today' => $today,
            'month_used' => (float) $monthStmt->fetchColumn(),
        ];
    }

    public function calendarEvents(?int $userId = null, bool $approvedOnly = true): array
    {
        $sql = "SELECT lrd.leave_date, lrd.day_value, lr.status,
                       u.full_name, lt.name AS leave_type_name, lt.color_hex
                FROM leave_request_days lrd
                INNER JOIN leave_requests lr ON lr.id = lrd.leave_request_id
                INNER JOIN users u ON u.id = lr.user_id
                INNER JOIN leave_types lt ON lt.id = lr.leave_type_id
                WHERE 1=1";
        $params = [];

        if ($approvedOnly) {
            $sql .= " AND lr.status = 'approved'";
        }
        if ($userId !== null) {
            $sql .= ' AND lr.user_id = :user_id';
            $params['user_id'] = $userId;
        }

        $sql .= ' ORDER BY lrd.leave_date ASC, u.full_name ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
