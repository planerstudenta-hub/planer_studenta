<?php

declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

$checks = [];
$error = null;

try {
    $db = App::db();
    $checks[] = ['PHP', PHP_VERSION, version_compare(PHP_VERSION, '8.0.0', '>=')];
    $checks[] = ['Rozszerzenie PDO', extension_loaded('pdo_mysql') ? 'pdo_mysql aktywne' : 'brak pdo_mysql', extension_loaded('pdo_mysql')];
    $checks[] = ['Połączenie z bazą', 'OK', $db->fetch('SELECT 1 AS ok') !== null];

    foreach (['users', 'events', 'friendships', 'event_shares'] as $table) {
        $checks[] = ['Tabela ' . $table, $db->tableExists($table) ? 'istnieje' : 'brak', $db->tableExists($table)];
    }

    foreach (['user_id', 'visibility', 'shared_note'] as $column) {
        $checks[] = ['Kolumna events.' . $column, $db->columnExists('events', $column) ? 'istnieje' : 'brak', $db->columnExists('events', $column)];
    }
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Diagnostyka - Planer Studenta</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="install-page">
    <main class="install-box">
        <span class="eyebrow">Planer Studenta</span>
        <h1>Diagnostyka serwera</h1>
        <?php if ($error): ?>
            <div class="notice danger"><?= e($error) ?></div>
        <?php endif; ?>
        <div class="agenda-list">
            <?php foreach ($checks as [$name, $value, $ok]): ?>
                <div class="agenda-item static">
                    <strong><?= e($name) ?></strong>
                    <span><?= $ok ? 'OK' : 'BŁĄD' ?> - <?= e($value) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <p><a class="primary-link" href="install.php">Przejdź do migracji bazy</a></p>
    </main>
</body>
</html>
