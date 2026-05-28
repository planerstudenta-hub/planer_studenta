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
    <title>Planer Studenta</title>
    <link rel="stylesheet" href="assets/styles.css">
    <script src="assets/app.js" defer></script>
</head>
<body>
    <header class="topbar">
        <div>
            <span class="eyebrow">Planer Studenta</span>
            <h1>Prosty kalendarz wydarzen studenta</h1>
            <p class="user-line">Zalogowano jako <?= e($user['name']) ?>, <?= e($user['email']) ?></p>
        </div>
        <nav class="nav-links">
            <a class="active" href="index.php">Kalendarz</a>
            <a href="logout.php">Wyloguj</a>
        </nav>
        <button class="button primary" id="newEventBtn" type="button">Dodaj wydarzenie</button>
    </header>

    <main class="layout">
        <section class="dashboard">
            <article class="stat">
                <span>Dzisiaj</span>
                <strong id="todayCount">0</strong>
            </article>
            <article class="stat">
                <span>Ten tydzien</span>
                <strong id="weekCount">0</strong>
            </article>
            <article class="stat">
                <span>Egzaminy</span>
                <strong id="examCount">0</strong>
            </article>
        </section>

        <section class="planner-shell">
            <div class="calendar-panel">
                <div class="toolbar">
                    <div class="month-switcher">
                        <button class="icon-button" id="prevMonthBtn" type="button" aria-label="Poprzedni miesiac">&lt;</button>
                        <h2 id="monthLabel">Miesiac</h2>
                        <button class="icon-button" id="nextMonthBtn" type="button" aria-label="Nastepny miesiac">&gt;</button>
                    </div>
                </div>
                <div class="weekdays">
                    <span>Pn</span><span>Wt</span><span>Sr</span><span>Cz</span><span>Pt</span><span>Sb</span><span>Nd</span>
                </div>
                <div class="calendar-grid" id="calendarGrid"></div>
            </div>

            <aside class="side-panel">
                <section>
                    <h2>Najblizsze wydarzenia</h2>
                    <div class="agenda-list" id="agendaList"></div>
                </section>
            </aside>
        </section>
    </main>

    <dialog class="event-dialog" id="eventDialog">
        <form id="eventForm" method="dialog">
            <input id="eventId" type="hidden">
            <div class="dialog-head">
                <div>
                    <span class="eyebrow">Wydarzenie</span>
                    <h2 id="dialogTitle">Nowe wydarzenie</h2>
                </div>
                <button class="icon-button" id="closeDialogBtn" type="button" aria-label="Zamknij">x</button>
            </div>

            <label>Tytul <input id="titleInput" type="text" maxlength="160" required></label>
            <div class="form-grid">
                <label>Typ
                    <select id="typeInput">
                        <option value="egzamin">Egzamin</option>
                        <option value="projekt">Projekt</option>
                        <option value="zajecia">Zajecia</option>
                        <option value="inne">Inne</option>
                    </select>
                </label>
                <label>Status
                    <select id="statusInput">
                        <option value="planned">Zaplanowane</option>
                        <option value="done">Zrobione</option>
                    </select>
                </label>
            </div>
            <div class="form-grid">
                <label>Start <input id="startInput" type="datetime-local" required></label>
                <label>Koniec <input id="endInput" type="datetime-local"></label>
            </div>
            <label>Miejsce <input id="locationInput" type="text" maxlength="180"></label>
            <label>Notatki <textarea id="notesInput" rows="4"></textarea></label>
            <p class="form-error" id="formError"></p>

            <div class="dialog-actions">
                <button class="button danger" id="deleteEventBtn" type="button">Usun</button>
                <span></span>
                <button class="button muted" id="cancelBtn" type="button">Anuluj</button>
                <button class="button primary" type="submit">Zapisz</button>
            </div>
        </form>
    </dialog>
</body>
</html>

