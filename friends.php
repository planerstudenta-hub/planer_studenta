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
    <title>Znajomi - Planer Studenta</title>
    <link rel="stylesheet" href="assets/styles.css">
    <script src="assets/friends.js" defer></script>
</head>
<body>
    <header class="topbar app-nav">
        <div>
            <span class="eyebrow">Planer Studenta</span>
            <h1>Znajomi i współdzielenie</h1>
            <p class="user-line">Zalogowano jako <?= e($user['name']) ?>, <?= e($user['email']) ?></p>
        </div>
        <nav class="nav-links" aria-label="Nawigacja">
            <a href="index.php">Kalendarz</a>
            <a href="reports.php">Raporty</a>
            <a class="active" href="friends.php">Znajomi</a>
            <a href="logout.php">Wyloguj</a>
        </nav>
    </header>

    <main class="layout">
        <section class="manager-grid">
            <article class="tool-panel">
                <h2>Dodaj znajomego</h2>
                <form id="friendForm" class="inline-form">
                    <input id="friendEmail" type="email" placeholder="email@uczelnia.pl" required>
                    <button class="button primary" type="submit">Wyślij</button>
                </form>
                <p class="form-error" id="friendError"></p>
            </article>
            <article class="tool-panel">
                <h2>Zaproszenia do Ciebie</h2>
                <div class="agenda-list" id="incomingList"></div>
            </article>
            <article class="tool-panel">
                <h2>Twoi znajomi</h2>
                <div class="agenda-list" id="friendsList"></div>
            </article>
            <article class="tool-panel">
                <h2>Wysłane zaproszenia</h2>
                <div class="agenda-list" id="outgoingList"></div>
            </article>
        </section>
    </main>
</body>
</html>
