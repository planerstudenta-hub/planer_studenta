<?php

declare(strict_types=1);

final class FriendService
{
    public function __construct(private Database $db)
    {
    }

    public function summary(int $userId): array
    {
        return [
            'friends' => $this->friends($userId),
            'incoming' => $this->incoming($userId),
            'outgoing' => $this->outgoing($userId),
        ];
    }

    public function sendRequest(int $userId, string $email): void
    {
        $friend = $this->db->fetch('SELECT id FROM users WHERE email = :email', ['email' => strtolower(trim($email))]);
        if (!$friend) {
            json_response(['ok' => false, 'error' => 'Nie znaleziono użytkownika z takim e-mailem.'], 404);
        }

        $friendId = (int) $friend['id'];
        if ($friendId === $userId) {
            json_response(['ok' => false, 'error' => 'Nie możesz dodać siebie.'], 422);
        }

        $existing = $this->db->fetch(
            'SELECT * FROM friendships WHERE (requester_id = :a1 AND addressee_id = :b1) OR (requester_id = :b2 AND addressee_id = :a2)',
            ['a1' => $userId, 'b1' => $friendId, 'b2' => $friendId, 'a2' => $userId]
        );

        if ($existing) {
            if ($existing['status'] === 'rejected') {
                $this->db->execute(
                    'UPDATE friendships SET requester_id = :requester, addressee_id = :addressee, status = "pending", responded_at = NULL WHERE id = :id',
                    ['requester' => $userId, 'addressee' => $friendId, 'id' => (int) $existing['id']]
                );
                return;
            }
            json_response(['ok' => false, 'error' => 'Zaproszenie lub znajomość już istnieje.'], 422);
        }

        $this->db->execute(
            'INSERT INTO friendships (requester_id, addressee_id, status) VALUES (:requester, :addressee, "pending")',
            ['requester' => $userId, 'addressee' => $friendId]
        );
    }

    public function respond(int $userId, int $friendshipId, string $action): void
    {
        if (!in_array($action, ['accept', 'reject'], true)) {
            json_response(['ok' => false, 'error' => 'Nieznana akcja.'], 422);
        }

        $status = $action === 'accept' ? 'accepted' : 'rejected';
        $changed = $this->db->execute(
            'UPDATE friendships SET status = :status, responded_at = NOW() WHERE id = :id AND addressee_id = :user_id AND status = "pending"',
            ['status' => $status, 'id' => $friendshipId, 'user_id' => $userId]
        );

        if ($changed === 0) {
            json_response(['ok' => false, 'error' => 'Nie znaleziono oczekującego zaproszenia.'], 404);
        }
    }

    public function remove(int $userId, int $friendshipId): void
    {
        $this->db->execute(
            'DELETE FROM friendships WHERE id = :id AND (requester_id = :user_a OR addressee_id = :user_b)',
            ['id' => $friendshipId, 'user_a' => $userId, 'user_b' => $userId]
        );
    }

    public function friends(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT f.id AS friendship_id, u.id, u.name, u.email
             FROM friendships f
             JOIN users u ON u.id = IF(f.requester_id = :user_join, f.addressee_id, f.requester_id)
             WHERE f.status = "accepted" AND (f.requester_id = :user_a OR f.addressee_id = :user_b)
             ORDER BY u.name',
            ['user_join' => $userId, 'user_a' => $userId, 'user_b' => $userId]
        );
    }

    private function incoming(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT f.id AS friendship_id, u.name, u.email, f.created_at
             FROM friendships f
             JOIN users u ON u.id = f.requester_id
             WHERE f.addressee_id = :user_id AND f.status = "pending"
             ORDER BY f.created_at DESC',
            ['user_id' => $userId]
        );
    }

    private function outgoing(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT f.id AS friendship_id, u.name, u.email, f.created_at
             FROM friendships f
             JOIN users u ON u.id = f.addressee_id
             WHERE f.requester_id = :user_id AND f.status = "pending"
             ORDER BY f.created_at DESC',
            ['user_id' => $userId]
        );
    }
}
