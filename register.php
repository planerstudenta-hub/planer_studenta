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
        <h1>Utwórz konto</h1>
        <?php if ($error): ?><div class="notice danger"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="stack-form">
            <label>Nazwa <input name="name" type="text" autocomplete="name" required></label>
            <label>E-mail <input name="email" type="email" autocomplete="email" required></label>
            <label>Hasło <input name="password" type="password" autocomplete="new-password" minlength="8" required></label>
            <button class="button primary" type="submit">Załóż konto</button>
        </form>
        <p>Masz już konto? <a href="login.php">Zaloguj się</a>.</p>
    </main>
</body>
</html>
