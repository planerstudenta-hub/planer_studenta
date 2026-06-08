<?php

declare(strict_types=1);

final class AuthService
{
    public function __construct(private Database $db)
    {
    }

    public function user(): ?array
    {
        $id = (int) ($_SESSION['user_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }

        return $this->db->fetch('SELECT id, name, email, created_at, last_login_at FROM users WHERE id = :id', ['id' => $id]);
    }

    public function register(string $name, string $email, string $password): array
    {
        $name = trim($name);
        $email = strtolower(trim($email));

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            throw new InvalidArgumentException('Podaj imię, poprawny e-mail i hasło minimum 8 znaków.');
        }

        if ($this->db->fetch('SELECT id FROM users WHERE email = :email', ['email' => $email])) {
            throw new InvalidArgumentException('Konto z tym adresem e-mail już istnieje.');
        }

        $isFirstUser = (int) ($this->db->fetch('SELECT COUNT(*) AS total FROM users')['total'] ?? 0) === 0;
        $this->db->execute(
            'INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)',
            [
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]
        );
        $id = $this->db->lastId();

        if ($isFirstUser) {
            $this->db->execute('UPDATE events SET user_id = :id WHERE user_id IS NULL', ['id' => $id]);
        }

        $_SESSION['user_id'] = $id;

        return $this->user() ?? [];
    }

    public function login(string $email, string $password): array
    {
        $user = $this->db->fetch('SELECT * FROM users WHERE email = :email', ['email' => strtolower(trim($email))]);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new InvalidArgumentException('Niepoprawny e-mail albo hasło.');
        }

        $_SESSION['user_id'] = (int) $user['id'];
        $this->db->execute('UPDATE users SET last_login_at = NOW() WHERE id = :id', ['id' => (int) $user['id']]);

        return $this->user() ?? [];
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }
}
