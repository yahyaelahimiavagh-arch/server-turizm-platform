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
            "SELECT id, full_name, email, role, is_active, hire_date, created_at
             FROM users
             WHERE role = 'employee'
             ORDER BY is_active DESC, full_name ASC"
        );

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, full_name, email, role, is_active, hire_date, created_at, updated_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
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
        ?string $hireDate,
        int $year
    ): int {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO users (full_name, email, password_hash, role, is_active, hire_date)
                 VALUES (:full_name, :email, :password_hash, 'employee', 1, :hire_date)"
            );
            $stmt->execute([
                'full_name' => trim($fullName),
                'email' => mb_strtolower(trim($email)),
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'hire_date' => $hireDate ?: null,
            ]);

            $userId = (int) $this->pdo->lastInsertId();
            $this->ensureAllowance($userId, $year);

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
        ?string $hireDate,
        bool $isActive,
        ?string $newPassword = null
    ): void {
        $params = [
            'id' => $id,
            'full_name' => trim($fullName),
            'email' => mb_strtolower(trim($email)),
            'hire_date' => $hireDate ?: null,
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
                 is_active = :is_active
                 {$passwordSql}
             WHERE id = :id AND role = 'employee'"
        );
        $stmt->execute($params);
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
