<?php

declare(strict_types=1);

final class ReportRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function employeeYear(int $userId, int $year): array
    {
        $leaveRepo = new LeaveRepository($this->pdo);
        $summary = $leaveRepo->allowanceSummary($userId, $year);

        $monthly = array_fill(1, 12, 0.0);
        $stmt = $this->pdo->prepare(
            "SELECT MONTH(lrd.leave_date) AS month_no, COALESCE(SUM(lrd.day_value), 0) AS total
             FROM leave_request_days lrd
             INNER JOIN leave_requests lr ON lr.id = lrd.leave_request_id
             WHERE lr.user_id = :user_id
               AND lr.status = 'approved'
               AND YEAR(lrd.leave_date) = :year
             GROUP BY MONTH(lrd.leave_date)
             ORDER BY month_no"
        );
        $stmt->execute(['user_id' => $userId, 'year' => $year]);
        foreach ($stmt->fetchAll() as $row) {
            $monthly[(int) $row['month_no']] = (float) $row['total'];
        }

        $breakdownStmt = $this->pdo->prepare(
            "SELECT lt.name, lt.color_hex, COALESCE(SUM(lrd.day_value), 0) AS total
             FROM leave_types lt
             LEFT JOIN leave_requests lr
               ON lr.leave_type_id = lt.id
              AND lr.user_id = :user_id
              AND lr.status = 'approved'
             LEFT JOIN leave_request_days lrd
               ON lrd.leave_request_id = lr.id
              AND YEAR(lrd.leave_date) = :year
             GROUP BY lt.id, lt.name, lt.color_hex, lt.sort_order
             ORDER BY lt.sort_order, lt.name"
        );
        $breakdownStmt->execute(['user_id' => $userId, 'year' => $year]);

        return [
            'summary' => $summary,
            'monthly' => $monthly,
            'breakdown' => $breakdownStmt->fetchAll(),
        ];
    }

    public function allEmployeesYear(int $year): array
    {
        $users = new UserRepository($this->pdo);
        $rows = [];

        foreach ($users->listEmployees() as $employee) {
            if ((int) $employee['is_active'] !== 1) {
                continue;
            }
            $report = $this->employeeYear((int) $employee['id'], $year);
            $rows[] = [
                'employee' => $employee,
                'report' => $report,
            ];
        }

        return $rows;
    }
}
