<?php

declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

$message = null;
$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        (new MigrationService(App::db()))->migrate();
        $message = 'Baza danych jest gotowa. Migracje zostały wykonane bez kasowania danych.';
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
    <title>Instalacja - Planer Studenta</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="install-page">
    <main class="install-box">
        <span class="eyebrow">Planer Studenta</span>
        <h1>Instalacja i migracja bazy danych</h1>
        <p>Ten krok tworzy lub aktualizuje tabele: <strong>users</strong>, <strong>events</strong>, <strong>friendships</strong> i <strong>event_shares</strong>.</p>
        <p>Jeżeli masz stare wydarzenia bez konta, pierwsze zarejestrowane konto przejmie je automatycznie.</p>

        <?php if ($message): ?>
            <div class="notice success"><?= e($message) ?></div>
            <a class="primary-link" href="register.php">Załóż pierwsze konto</a>
        <?php elseif ($error): ?>
            <div class="notice danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <button class="button primary" type="submit">Utwórz / zaktualizuj tabele</button>
        </form>
    </main>
</body>
</html>
