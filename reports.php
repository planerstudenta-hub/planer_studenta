<?php

declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';
$user = require_user();
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Raporty - Planer Studenta</title>
    <link rel="stylesheet" href="assets/styles.css">
    <script src="assets/reports.js" defer></script>
</head>
<body>
    <header class="topbar app-nav">
        <div>
            <span class="eyebrow">Planer Studenta</span>
            <h1>Raporty i obciążenie</h1>
            <p class="user-line">Zalogowano jako <?= e($user['name']) ?>, <?= e($user['email']) ?></p>
        </div>
        <nav class="nav-links" aria-label="Nawigacja">
            <a href="index.php">Kalendarz</a>
            <a class="active" href="reports.php">Raporty</a>
            <a href="friends.php">Znajomi</a>
            <a href="logout.php">Wyloguj</a>
        </nav>
    </header>

    <main class="layout">
        <section class="dashboard">
            <article class="stat"><span>Wydarzenia w miesiącu</span><strong id="reportTotal">0</strong></article>
            <article class="stat"><span>Następne 7 dni</span><strong id="reportUpcoming">0</strong></article>
            <article class="stat"><span>Zaległe</span><strong id="reportOverdue">0</strong></article>
            <article class="stat"><span>Współdzielone</span><strong id="reportShared">0</strong></article>
        </section>

        <section class="manager-grid">
            <article class="tool-panel">
                <h2>Typy wydarzeń</h2>
                <div id="typeReport" class="report-bars"></div>
            </article>
            <article class="tool-panel">
                <h2>Status</h2>
                <div id="statusReport" class="report-bars"></div>
            </article>
            <article class="tool-panel span-2">
                <h2>Najbardziej zajęte dni</h2>
                <div id="busyReport" class="report-bars"></div>
            </article>
        </section>
    </main>
</body>
</html>
