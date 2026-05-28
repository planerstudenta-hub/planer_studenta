<?php

declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

if (current_user()) {
    redirect('index.php');
}

$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        auth()->register(
            (string) ($_POST['name'] ?? ''),
            (string) ($_POST['email'] ?? ''),
            (string) ($_POST['password'] ?? '')
        );
        redirect('index.php');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rejestracja - Planer Studenta</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="auth-page">
    <main class="auth-box">
        <span class="eyebrow">Planer Studenta</span>
        <h1>Utworz konto</h1>
        <?php if ($error): ?><div class="notice danger"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="stack-form">
            <label>Nazwa <input name="name" type="text" required></label>
            <label>E-mail <input name="email" type="email" required></label>
            <label>Haslo <input name="password" type="password" minlength="8" required></label>
            <button class="button primary" type="submit">Zaloz konto</button>
        </form>
        <p>Masz juz konto? <a href="login.php">Zaloguj sie</a>.</p>
    </main>
</body>
</html>

