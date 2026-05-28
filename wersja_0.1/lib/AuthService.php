<?php

declare(strict_types=1);

final class AuthService
{
    public function __construct(private Database $db)
    {
    }

    public function user(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT id, name, email, created_at FROM users WHERE id = :id',
            ['id' => $id]
        );
    }

    public function register(string $name, string $email, string $password): void
    {
        $name = trim($name);
        $email = strtolower(trim($email));

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            throw new InvalidArgumentException('Podaj imie, poprawny e-mail i haslo minimum 8 znakow.');
        }

        $this->db->execute(
            'INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)',
            [
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]
        );

        $_SESSION['user_id'] = $this->db->lastId();
    }

    public function login(string $email, string $password): void
    {
        $user = $this->db->fetch(
            'SELECT * FROM users WHERE email = :email',
            ['email' => strtolower(trim($email))]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new InvalidArgumentException('Niepoprawny e-mail albo haslo.');
        }

        $_SESSION['user_id'] = (int) $user['id'];
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }
}

