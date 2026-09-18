<?php

declare(strict_types=1);

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listEmployees(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, full_name, email, role, is_active, hire_date, birth_date, created_at
             FROM users
             WHERE role = 'employee'
             ORDER BY is_active DESC, full_name ASC"
        );

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, full_name, email, role, is_active, hire_date, birth_date, created_at, updated_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function hasServiceYearEntitlements(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM annual_leave_entitlements
             WHERE user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchColumn() !== false;
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM users WHERE email = :email';
        $params = ['email' => mb_strtolower(trim($email))];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $params['except_id'] = $exceptId;
        }

        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function createEmployee(
        string $fullName,
        string $email,
        string $password,
        string $hireDate,
        string $birthDate
    ): int {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO users (full_name, email, password_hash, role, is_active, hire_date, birth_date)
                 VALUES (:full_name, :email, :password_hash, 'employee', 1, :hire_date, :birth_date)"
            );
            $stmt->execute([
                'full_name' => trim($fullName),
                'email' => mb_strtolower(trim($email)),
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'hire_date' => $hireDate,
                'birth_date' => $birthDate,
            ]);

            $userId = (int) $this->pdo->lastInsertId();
            sync_annual_leave_entitlements($this->pdo, $userId, date('Y-m-d'));

            $this->pdo->commit();
            return $userId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateEmployee(
        int $id,
        string $fullName,
        string $email,
        string $hireDate,
        string $birthDate,
        bool $isActive,
        ?string $newPassword = null
    ): void {
        $params = [
            'id' => $id,
            'full_name' => trim($fullName),
            'email' => mb_strtolower(trim($email)),
            'hire_date' => $hireDate,
            'birth_date' => $birthDate,
            'is_active' => $isActive ? 1 : 0,
        ];

        $passwordSql = '';
        if ($newPassword !== null && $newPassword !== '') {
            $passwordSql = ', password_hash = :password_hash';
            $params['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $stmt = $this->pdo->prepare(
            "UPDATE users
             SET full_name = :full_name,
                 email = :email,
                 hire_date = :hire_date,
                 birth_date = :birth_date,
                 is_active = :is_active
                 {$passwordSql}
             WHERE id = :id AND role = 'employee'"
        );
        $stmt->execute($params);

        sync_annual_leave_entitlements($this->pdo, $id, date('Y-m-d'));
    }

    public function deleteEmployeePermanently(
        int $id,
        int $actorAdminId,
        string $confirmationEmail
    ): array {
        $employee = $this->find($id);

        if (!$employee || (string) $employee['role'] !== 'employee') {
            throw new DomainException('Çalışan bulunamadı.');
        }

        $expectedEmail = mb_strtolower(trim((string) $employee['email']));
        $providedEmail = mb_strtolower(trim($confirmationEmail));

        if ($providedEmail === '' || !hash_equals($expectedEmail, $providedEmail)) {
            throw new DomainException('Kalıcı silme için çalışanın e-posta adresini aynen yazın.');
        }

        $attachmentStmt = $this->pdo->prepare(
            "SELECT DISTINCT la.stored_name
             FROM leave_attachments la
             LEFT JOIN leave_requests lr ON lr.id = la.leave_request_id
             WHERE la.uploaded_by = :user_id
                OR lr.user_id = :user_id"
        );
        $attachmentStmt->execute(['user_id' => $id]);
        $storedNames = array_values(array_filter(array_map(
            static fn (array $row): string => (string) ($row['stored_name'] ?? ''),
            $attachmentStmt->fetchAll()
        )));

        $requestCountStmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM leave_requests WHERE user_id = :user_id'
        );
        $requestCountStmt->execute(['user_id' => $id]);
        $requestCount = (int) $requestCountStmt->fetchColumn();

        $this->pdo->beginTransaction();

        try {
            $deleteAttachments = $this->pdo->prepare(
                "DELETE FROM leave_attachments
                 WHERE uploaded_by = :user_id
                    OR leave_request_id IN (
                        SELECT id FROM leave_requests WHERE user_id = :user_id
                    )"
            );
            $deleteAttachments->execute(['user_id' => $id]);

            $deleteRequestAudit = $this->pdo->prepare(
                "DELETE FROM audit_log
                 WHERE entity_type = 'leave_request'
                   AND entity_id IN (
                       SELECT CAST(id AS CHAR)
                       FROM leave_requests
                       WHERE user_id = :user_id
                   )"
            );
            $deleteRequestAudit->execute(['user_id' => $id]);

            $deleteRequests = $this->pdo->prepare(
                'DELETE FROM leave_requests WHERE user_id = :user_id'
            );
            $deleteRequests->execute(['user_id' => $id]);

            $deleteEntitlements = $this->pdo->prepare(
                'DELETE FROM annual_leave_entitlements WHERE user_id = :user_id'
            );
            $deleteEntitlements->execute(['user_id' => $id]);

            $deleteLegacyAllowances = $this->pdo->prepare(
                'DELETE FROM annual_allowances WHERE user_id = :user_id'
            );
            $deleteLegacyAllowances->execute(['user_id' => $id]);

            $deleteUserAudit = $this->pdo->prepare(
                "DELETE FROM audit_log
                 WHERE actor_user_id = :user_id
                    OR (entity_type = 'employee' AND entity_id = :entity_id)"
            );
            $deleteUserAudit->execute([
                'user_id' => $id,
                'entity_id' => (string) $id,
            ]);

            $deleteUser = $this->pdo->prepare(
                "DELETE FROM users
                 WHERE id = :id
                   AND role = 'employee'"
            );
            $deleteUser->execute(['id' => $id]);

            if ($deleteUser->rowCount() !== 1) {
                throw new RuntimeException('Çalışan kalıcı olarak silinemedi.');
            }

            audit_log_event(
                $this->pdo,
                $actorAdminId,
                'employee_permanently_deleted',
                'employee',
                $id,
                [
                    'purged_leave_request_count' => $requestCount,
                    'purged_attachment_count' => count($storedNames),
                ]
            );

            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        $fileDeleteFailures = 0;
        foreach ($storedNames as $storedName) {
            try {
                $path = attachment_file_path($storedName);
            } catch (Throwable) {
                $fileDeleteFailures++;
                continue;
            }

            if (is_file($path) && !@unlink($path)) {
                $fileDeleteFailures++;
                error_log('Could not remove purged employee attachment: ' . $storedName);
            }
        }

        return [
            'leave_requests' => $requestCount,
            'attachments' => count($storedNames),
            'attachment_file_delete_failures' => $fileDeleteFailures,
        ];
    }

    public function ensureAllowance(int $userId, int $year): float
    {
        $existing = $this->getAllowance($userId, $year);
        if ($existing !== null) {
            return $existing;
        }

        $stmt = $this->pdo->prepare(
            "SELECT setting_value
             FROM app_settings
             WHERE setting_key = 'default_annual_allowance_days'
             LIMIT 1"
        );
        $stmt->execute();
        $defaultRaw = $stmt->fetchColumn();

        if ($defaultRaw === false) {
            throw new RuntimeException('Varsayılan yıllık izin hakkı ayarlanmamış.');
        }

        $default = (float) $defaultRaw;

        $insert = $this->pdo->prepare(
            'INSERT IGNORE INTO annual_allowances (user_id, allowance_year, entitlement_days)
             VALUES (:user_id, :year, :days)'
        );
        $insert->execute([
            'user_id' => $userId,
            'year' => $year,
            'days' => $default,
        ]);

        return $this->getAllowance($userId, $year) ?? $default;
    }

    public function getAllowance(int $userId, int $year): ?float
    {
        $stmt = $this->pdo->prepare(
            'SELECT entitlement_days
             FROM annual_allowances
             WHERE user_id = :user_id AND allowance_year = :year
             LIMIT 1'
        );
        $stmt->execute([
            'user_id' => $userId,
            'year' => $year,
        ]);
        $value = $stmt->fetchColumn();

        return $value === false ? null : (float) $value;
    }

    public function setAllowance(int $userId, int $year, float $days): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO annual_allowances (user_id, allowance_year, entitlement_days)
             VALUES (:user_id, :year, :days)
             ON DUPLICATE KEY UPDATE entitlement_days = VALUES(entitlement_days)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'year' => $year,
            'days' => $days,
        ]);
    }
}
