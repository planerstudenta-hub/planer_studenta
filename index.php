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
    <header class="topbar app-nav">
        <div>
            <span class="eyebrow">Planer Studenta</span>
            <h1>Kalendarz uczelni, projektów i przypomnień</h1>
            <p class="user-line">Zalogowano jako <?= e($user['name']) ?>, <?= e($user['email']) ?></p>
        </div>
        <nav class="nav-links" aria-label="Nawigacja">
            <a class="active" href="index.php">Kalendarz</a>
            <a href="reports.php">Raporty</a>
            <a href="friends.php">Znajomi</a>
            <a href="logout.php">Wyloguj</a>
        </nav>
        <button class="button primary" id="newEventBtn" type="button">Dodaj wydarzenie</button>
    </header>

    <main class="layout">
        <section class="dashboard" aria-label="Podsumowanie">
            <article class="stat">
                <span>Dzisiaj</span>
                <strong id="todayCount">0</strong>
            </article>
            <article class="stat">
                <span>Ten tydzień</span>
                <strong id="weekCount">0</strong>
            </article>
            <article class="stat">
                <span>Najbliższe egzaminy</span>
                <strong id="examCount">0</strong>
            </article>
            <article class="stat">
                <span>Do zrobienia</span>
                <strong id="todoCount">0</strong>
            </article>
        </section>

        <section class="planner-shell">
            <div class="calendar-panel">
                <div class="toolbar">
                    <div class="month-switcher">
                        <button class="icon-button" id="prevMonthBtn" type="button" aria-label="Poprzedni miesiąc">‹</button>
                        <h2 id="monthLabel">Miesiąc</h2>
                        <button class="icon-button" id="nextMonthBtn" type="button" aria-label="Następny miesiąc">›</button>
                    </div>
                    <div class="filters">
                        <div class="segmented-control" aria-label="Widok kalendarza">
                            <button class="active" data-view="calendar" type="button">Miesiąc</button>
                            <button data-view="list" type="button">Lista</button>
                        </div>
                        <select id="typeFilter" aria-label="Filtr typu">
                            <option value="all">Wszystkie typy</option>
                            <option value="egzamin">Egzaminy</option>
                            <option value="projekt">Projekty</option>
                            <option value="zajecia">Zajęcia</option>
                            <option value="praktyki">Praktyki</option>
                            <option value="wyjazd">Wyjazdy</option>
                            <option value="sluzba">Służba</option>
                            <option value="inne">Inne</option>
                        </select>
                        <select id="statusFilter" aria-label="Filtr statusu">
                            <option value="all">Wszystkie statusy</option>
                            <option value="planned">Zaplanowane</option>
                            <option value="done">Zrobione</option>
                        </select>
                        <input id="searchInput" type="search" placeholder="Szukaj..." aria-label="Szukaj wydarzeń">
                        <button class="button muted" id="notifyBtn" type="button">Powiadomienia</button>
                    </div>
                </div>

                <div class="weekdays" aria-hidden="true">
                    <span>Pn</span>
                    <span>Wt</span>
                    <span>Śr</span>
                    <span>Cz</span>
                    <span>Pt</span>
                    <span>Sb</span>
                    <span>Nd</span>
                </div>
                <div class="calendar-grid" id="calendarGrid" aria-live="polite"></div>
                <div class="month-list" id="monthList" aria-live="polite"></div>
            </div>

            <aside class="side-panel" aria-label="Listy wydarzeń">
                <section>
                    <h2>Na dziś</h2>
                    <div class="agenda-list" id="todayList"></div>
                </section>
                <section>
                    <h2>Ten tydzień</h2>
                    <div class="agenda-list" id="weekList"></div>
                </section>
                <section>
                    <h2>Aktywne przypomnienia</h2>
                    <div class="agenda-list" id="reminderList"></div>
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
                    <p id="ownerLine" class="owner-line"></p>
                </div>
                <button class="icon-button" id="closeDialogBtn" type="button" aria-label="Zamknij">×</button>
            </div>

            <label>
                Tytuł
                <input id="titleInput" name="title" type="text" maxlength="160" required>
            </label>

            <div class="form-grid">
                <label>
                    Typ
                    <select id="typeInput" name="type">
                        <option value="egzamin">Egzamin</option>
                        <option value="projekt">Projekt</option>
                        <option value="zajecia">Zajęcia</option>
                        <option value="praktyki">Praktyki</option>
                        <option value="wyjazd">Wyjazd</option>
                        <option value="sluzba">Służba</option>
                        <option value="inne">Inne</option>
                    </select>
                </label>
                <label>
                    Status
                    <select id="statusInput" name="status">
                        <option value="planned">Zaplanowane</option>
                        <option value="done">Zrobione</option>
                    </select>
                </label>
            </div>

            <div class="form-grid">
                <label>
                    Start
                    <input id="startInput" name="start_at" type="datetime-local" required>
                </label>
                <label>
                    Koniec
                    <input id="endInput" name="end_at" type="datetime-local">
                </label>
            </div>

            <div class="form-grid">
                <label>
                    Przypomnienie
                    <select id="reminderInput" name="reminder_minutes">
                        <option value="0">Brak</option>
                        <option value="60">Godzinę wcześniej</option>
                        <option value="180">3 godziny wcześniej</option>
                        <option value="1440">Dzień wcześniej</option>
                        <option value="2880">2 dni wcześniej</option>
                    </select>
                </label>
                <label>
                    Cykliczność
                    <select id="recurrenceInput" name="recurrence">
                        <option value="none">Bez powtarzania</option>
                        <option value="daily">Codziennie</option>
                        <option value="weekly">Co tydzień</option>
                        <option value="monthly">Co miesiąc</option>
                    </select>
                </label>
            </div>

            <div class="form-grid">
                <label>
                    Powtarzaj do
                    <input id="recurrenceUntilInput" name="recurrence_until" type="date">
                </label>
                <label>
                    Miejsce
                    <input id="locationInput" name="location" type="text" maxlength="180">
                </label>
            </div>

            <div class="form-grid">
                <label>
                    Widoczność
                    <select id="visibilityInput" name="visibility">
                        <option value="private">Prywatne</option>
                        <option value="friends">Widoczne dla znajomych</option>
                    </select>
                </label>
                <label>
                    Udostępnij konkretnym znajomym
                    <select id="shareInput" name="share_friend_ids" multiple size="3"></select>
                </label>
            </div>

            <label>
                Notatka przy udostępnieniu
                <input id="sharedNoteInput" name="shared_note" type="text" maxlength="255" placeholder="np. wrzucam dla grupy z ćwiczeń">
            </label>

            <label>
                Notatka do przygotowania
                <input id="prepNoteInput" name="prep_note" type="text" maxlength="255" placeholder="np. instruktaż, dokumenty, materiały">
            </label>

            <label>
                Przypomnij o przygotowaniu
                <select id="prepReminderInput" name="prep_reminder_minutes">
                    <option value="0">Brak</option>
                    <option value="180">3 godziny wcześniej</option>
                    <option value="1440">Dzień wcześniej</option>
                    <option value="2880">2 dni wcześniej</option>
                </select>
            </label>

            <label>
                Notatki
                <textarea id="notesInput" name="notes" rows="4"></textarea>
            </label>

            <p class="form-error" id="formError" role="alert"></p>

            <div class="dialog-actions">
                <button class="button danger" id="deleteEventBtn" type="button">Usuń</button>
                <span></span>
                <button class="button muted" id="cancelBtn" type="button">Anuluj</button>
                <button class="button primary" id="saveEventBtn" type="submit">Zapisz</button>
            </div>
        </form>
    </dialog>
</body>
</html>
