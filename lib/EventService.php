<?php

declare(strict_types=1);

final class EventService
{
    public function __construct(private Database $db)
    {
    }

    public function listForUser(int $userId, array $filters, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $where = [
            '(e.user_id = :viewer_owner OR es.user_id = :viewer_share OR (e.visibility = "friends" AND f.id IS NOT NULL))',
        ];
        $params = [
            'viewer_owner' => $userId,
            'viewer_share' => $userId,
            'viewer_select' => $userId,
            'viewer_friend_a' => $userId,
            'viewer_friend_b' => $userId,
        ];

        if (!empty($filters['type']) && $filters['type'] !== 'all') {
            $where[] = 'e.type = :type';
            $params['type'] = $filters['type'];
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $where[] = 'e.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(e.title LIKE :search OR e.notes LIKE :search OR e.location LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql = '
            SELECT DISTINCT e.*, u.name AS owner_name, IF(e.user_id = :viewer_select, 1, 0) AS can_edit
            FROM events e
            LEFT JOIN users u ON u.id = e.user_id
            LEFT JOIN event_shares es ON es.event_id = e.id
            LEFT JOIN friendships f ON f.status = "accepted"
                AND ((f.requester_id = :viewer_friend_a AND f.addressee_id = e.user_id)
                OR (f.addressee_id = :viewer_friend_b AND f.requester_id = e.user_id))
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY e.start_at ASC
        ';

        $events = $this->db->fetchAll($sql, $params);
        foreach ($events as &$event) {
            $event['share_friend_ids'] = [];
            if ((int) $event['user_id'] === $userId) {
                $shares = $this->db->fetchAll('SELECT user_id FROM event_shares WHERE event_id = :event_id', ['event_id' => (int) $event['id']]);
                $event['share_friend_ids'] = array_map('intval', array_column($shares, 'user_id'));
            }
        }
        unset($event);

        return $this->expand($events, $from, $to);
    }

    public function create(int $userId, array $data): int
    {
        $payload = EventValidator::normalize($data);
        $payload['user_id'] = $userId;
        $columns = array_keys($payload);
        $sql = 'INSERT INTO events (' . implode(', ', $columns) . ') VALUES (:' . implode(', :', $columns) . ')';
        $this->db->execute($sql, $payload);
        $eventId = $this->db->lastId();
        $this->syncShares($eventId, $userId, $data['share_friend_ids'] ?? []);

        return $eventId;
    }

    public function update(int $userId, int $eventId, array $data): void
    {
        if (!$this->owns($userId, $eventId)) {
            json_response(['ok' => false, 'error' => 'Możesz edytować tylko własne wydarzenia.'], 403);
        }

        $payload = EventValidator::normalize($data, true);
        if ($payload !== []) {
            $sets = [];
            foreach (array_keys($payload) as $column) {
                $sets[] = "{$column} = :{$column}";
            }
            $payload['id'] = $eventId;
            $payload['user_id'] = $userId;
            $this->db->execute('UPDATE events SET ' . implode(', ', $sets) . ' WHERE id = :id AND user_id = :user_id', $payload);
        }

        if (array_key_exists('share_friend_ids', $data)) {
            $this->syncShares($eventId, $userId, $data['share_friend_ids']);
        }
    }

    public function delete(int $userId, int $eventId): void
    {
        if (!$this->owns($userId, $eventId)) {
            json_response(['ok' => false, 'error' => 'Możesz usunąć tylko własne wydarzenia.'], 403);
        }

        $this->db->execute('DELETE FROM event_shares WHERE event_id = :id', ['id' => $eventId]);
        $this->db->execute('DELETE FROM events WHERE id = :id AND user_id = :user_id', ['id' => $eventId, 'user_id' => $userId]);
    }

    public function remindersForUser(int $userId): array
    {
        $now = new DateTimeImmutable('now');
        $events = $this->listForUser(
            $userId,
            ['status' => 'planned'],
            $now->modify('-10 minutes'),
            $now->modify('+48 hours')
        );
        $due = [];

        foreach ($events as $event) {
            $start = new DateTimeImmutable($event['start_at']);

            if ((int) $event['reminder_minutes'] > 0) {
                $reminderAt = $start->modify('-' . (int) $event['reminder_minutes'] . ' minutes');
                if ($reminderAt <= $now && $start >= $now->modify('-10 minutes')) {
                    $due[] = [
                        'key' => 'event_' . $event['occurrence_id'] . '_' . $event['reminder_minutes'],
                        'title' => $event['title'],
                        'body' => 'Start: ' . $start->format('d.m.Y H:i'),
                        'type' => $event['type'],
                        'start_at' => $event['start_at'],
                    ];
                }
            }

            if ((int) $event['prep_reminder_minutes'] > 0 && !empty($event['prep_note'])) {
                $prepAt = $start->modify('-' . (int) $event['prep_reminder_minutes'] . ' minutes');
                if ($prepAt <= $now && $start >= $now->modify('-10 minutes')) {
                    $due[] = [
                        'key' => 'prep_' . $event['occurrence_id'] . '_' . $event['prep_reminder_minutes'],
                        'title' => 'Przygotowanie: ' . $event['title'],
                        'body' => $event['prep_note'],
                        'type' => $event['type'],
                        'start_at' => $event['start_at'],
                    ];
                }
            }
        }

        return $due;
    }

    private function owns(int $userId, int $eventId): bool
    {
        return $this->db->fetch('SELECT id FROM events WHERE id = :id AND user_id = :user_id', [
            'id' => $eventId,
            'user_id' => $userId,
        ]) !== null;
    }

    private function syncShares(int $eventId, int $ownerId, array $friendIds): void
    {
        $this->db->execute('DELETE FROM event_shares WHERE event_id = :event_id', ['event_id' => $eventId]);

        foreach (array_unique(array_map('intval', $friendIds)) as $friendId) {
            if ($friendId <= 0 || !$this->areFriends($ownerId, $friendId)) {
                continue;
            }
            $this->db->execute(
                'INSERT IGNORE INTO event_shares (event_id, user_id) VALUES (:event_id, :user_id)',
                ['event_id' => $eventId, 'user_id' => $friendId]
            );
        }
    }

    private function areFriends(int $a, int $b): bool
    {
        return $this->db->fetch(
            'SELECT id FROM friendships WHERE status = "accepted" AND ((requester_id = :a1 AND addressee_id = :b1) OR (requester_id = :b2 AND addressee_id = :a2))',
            ['a1' => $a, 'b1' => $b, 'b2' => $b, 'a2' => $a]
        ) !== null;
    }

    private function expand(array $events, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $expanded = [];

        foreach ($events as $event) {
            $start = new DateTimeImmutable($event['start_at']);
            $end = $event['end_at'] ? new DateTimeImmutable($event['end_at']) : $start->modify('+1 hour');
            $duration = $end->getTimestamp() - $start->getTimestamp();
            $recurrence = $event['recurrence'] ?? 'none';
            $until = $event['recurrence_until'] ? (new DateTimeImmutable($event['recurrence_until']))->setTime(23, 59, 59) : $to;
            $limit = min($until->getTimestamp(), $to->getTimestamp());

            if ($recurrence === 'none') {
                if ($end >= $from && $start <= $to) {
                    $expanded[] = $this->occurrence($event, $start, $duration, false);
                }
                continue;
            }

            $cursor = $start;
            $guard = 0;
            while ($cursor->getTimestamp() + $duration < $from->getTimestamp() && $guard < 1000) {
                $cursor = $this->addInterval($cursor, $recurrence);
                $guard++;
            }

            while ($cursor->getTimestamp() <= $limit && $guard < 1500) {
                $occurrenceEnd = $cursor->modify('+' . max($duration, 0) . ' seconds');
                if ($occurrenceEnd >= $from && $cursor <= $to) {
                    $expanded[] = $this->occurrence($event, $cursor, $duration, true);
                }
                $cursor = $this->addInterval($cursor, $recurrence);
                $guard++;
            }
        }

        usort($expanded, static fn (array $a, array $b): int => strcmp($a['start_at'], $b['start_at']));

        return $expanded;
    }

    private function occurrence(array $event, DateTimeImmutable $start, int $duration, bool $isRecurring): array
    {
        $end = $start->modify('+' . max($duration, 0) . ' seconds');
        $event['base_start_at'] = $event['start_at'];
        $event['base_end_at'] = $event['end_at'];
        $event['source_id'] = (int) $event['id'];
        $event['occurrence_id'] = $event['id'] . '_' . $start->format('YmdHis');
        $event['is_recurring_instance'] = $isRecurring;
        $event['can_edit'] = (bool) $event['can_edit'];
        $event['start_at'] = $start->format('Y-m-d H:i:s');
        $event['end_at'] = $end->format('Y-m-d H:i:s');

        return $event;
    }

    private function addInterval(DateTimeImmutable $date, string $recurrence): DateTimeImmutable
    {
        return match ($recurrence) {
            'daily' => $date->modify('+1 day'),
            'weekly' => $date->modify('+1 week'),
            'monthly' => $date->modify('+1 month'),
            default => $date,
        };
    }
}
