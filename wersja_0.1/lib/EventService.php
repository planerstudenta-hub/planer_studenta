<?php

declare(strict_types=1);

final class EventService
{
    public function __construct(private Database $db)
    {
    }

    public function listForUser(int $userId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return $this->db->fetchAll(
            'SELECT *
             FROM events
             WHERE user_id = :user_id
             AND start_at BETWEEN :date_from AND :date_to
             ORDER BY start_at ASC',
            [
                'user_id' => $userId,
                'date_from' => $from->format('Y-m-d H:i:s'),
                'date_to' => $to->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function create(int $userId, array $data): int
    {
        $payload = $this->payload($data);
        $payload['user_id'] = $userId;

        $this->db->execute(
            'INSERT INTO events (user_id, title, type, start_at, end_at, location, notes, status)
             VALUES (:user_id, :title, :type, :start_at, :end_at, :location, :notes, :status)',
            $payload
        );

        return $this->db->lastId();
    }

    public function update(int $userId, int $eventId, array $data): void
    {
        $payload = $this->payload($data);
        $payload['id'] = $eventId;
        $payload['user_id'] = $userId;

        $this->db->execute(
            'UPDATE events
             SET title = :title, type = :type, start_at = :start_at, end_at = :end_at,
                 location = :location, notes = :notes, status = :status
             WHERE id = :id AND user_id = :user_id',
            $payload
        );
    }

    public function delete(int $userId, int $eventId): void
    {
        $this->db->execute(
            'DELETE FROM events WHERE id = :id AND user_id = :user_id',
            ['id' => $eventId, 'user_id' => $userId]
        );
    }

    private function payload(array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('Tytul jest wymagany.');
        }

        return [
            'title' => mb_substr($title, 0, 160),
            'type' => in_array($data['type'] ?? '', ['egzamin', 'projekt', 'zajecia', 'inne'], true) ? $data['type'] : 'inne',
            'start_at' => str_replace('T', ' ', (string) ($data['start_at'] ?? '')),
            'end_at' => !empty($data['end_at']) ? str_replace('T', ' ', (string) $data['end_at']) : null,
            'location' => trim((string) ($data['location'] ?? '')) ?: null,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            'status' => ($data['status'] ?? 'planned') === 'done' ? 'done' : 'planned',
        ];
    }
}

