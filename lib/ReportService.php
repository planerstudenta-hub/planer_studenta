<?php

declare(strict_types=1);

final class ReportService
{
    public function __construct(private Database $db, private EventService $events)
    {
    }

    public function summary(int $userId): array
    {
        $now = new DateTimeImmutable('now');
        $monthStart = $now->modify('first day of this month 00:00:00');
        $monthEnd = $now->modify('last day of this month 23:59:59');
        $events = $this->events->listForUser($userId, ['status' => 'all', 'type' => 'all'], $monthStart, $monthEnd);

        $byType = [];
        $byStatus = ['planned' => 0, 'done' => 0];
        $busyDays = [];
        $upcoming = 0;
        $overdue = 0;
        $shared = 0;

        foreach ($events as $event) {
            $byType[$event['type']] = ($byType[$event['type']] ?? 0) + 1;
            $byStatus[$event['status']] = ($byStatus[$event['status']] ?? 0) + 1;
            $day = substr($event['start_at'], 0, 10);
            $busyDays[$day] = ($busyDays[$day] ?? 0) + 1;

            $start = new DateTimeImmutable($event['start_at']);
            if ($event['status'] === 'planned' && $start >= $now && $start <= $now->modify('+7 days')) {
                $upcoming++;
            }
            if ($event['status'] === 'planned' && $start < $now) {
                $overdue++;
            }
            if ((int) $event['user_id'] !== $userId || $event['visibility'] === 'friends') {
                $shared++;
            }
        }

        arsort($busyDays);

        return [
            'month_label' => $now->format('m.Y'),
            'total' => count($events),
            'by_type' => $byType,
            'by_status' => $byStatus,
            'upcoming_7_days' => $upcoming,
            'overdue' => $overdue,
            'shared' => $shared,
            'busy_days' => array_slice($busyDays, 0, 8, true),
        ];
    }
}
