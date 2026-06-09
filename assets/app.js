const state = {
    currentMonth: new Date(),
    events: [],
    reminders: [],
    friends: [],
};

const typeLabels = {
    egzamin: 'Egzamin',
    projekt: 'Projekt',
    zajecia: 'Zajęcia',
    praktyki: 'Praktyki',
    wyjazd: 'Wyjazd',
    sluzba: 'Służba',
    inne: 'Inne',
};

const monthNames = ['styczeń', 'luty', 'marzec', 'kwiecień', 'maj', 'czerwiec', 'lipiec', 'sierpień', 'wrzesień', 'październik', 'listopad', 'grudzień'];
const $ = (selector) => document.querySelector(selector);

const els = {
    calendarGrid: $('#calendarGrid'),
    monthLabel: $('#monthLabel'),
    todayCount: $('#todayCount'),
    weekCount: $('#weekCount'),
    examCount: $('#examCount'),
    todoCount: $('#todoCount'),
    todayList: $('#todayList'),
    weekList: $('#weekList'),
    reminderList: $('#reminderList'),
    dialog: $('#eventDialog'),
    form: $('#eventForm'),
    formError: $('#formError'),
    dialogTitle: $('#dialogTitle'),
    deleteBtn: $('#deleteEventBtn'),
    saveBtn: $('#saveEventBtn'),
    typeFilter: $('#typeFilter'),
    statusFilter: $('#statusFilter'),
    searchInput: $('#searchInput'),
    ownerLine: $('#ownerLine'),
    shareInput: $('#shareInput'),
};

const pad = (value) => String(value).padStart(2, '0');
const localDateValue = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
const localDateTimeValue = (date) => `${localDateValue(date)}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
const mysqlToDate = (value) => new Date(String(value).replace(' ', 'T'));
const dateKey = (date) => localDateValue(date);
const sameDay = (a, b) => dateKey(a) === dateKey(b);

function startOfWeek(date) {
    const result = new Date(date);
    const day = (result.getDay() + 6) % 7;
    result.setDate(result.getDate() - day);
    result.setHours(0, 0, 0, 0);
    return result;
}

function endOfWeek(date) {
    const result = startOfWeek(date);
    result.setDate(result.getDate() + 6);
    result.setHours(23, 59, 59, 999);
    return result;
}

function calendarBounds() {
    const first = new Date(state.currentMonth.getFullYear(), state.currentMonth.getMonth(), 1);
    const last = new Date(state.currentMonth.getFullYear(), state.currentMonth.getMonth() + 1, 0);
    return { from: startOfWeek(first), to: endOfWeek(last) };
}

function formatTime(value) {
    return mysqlToDate(value).toLocaleTimeString('pl-PL', { hour: '2-digit', minute: '2-digit' });
}

function formatDateTime(value) {
    return mysqlToDate(value).toLocaleString('pl-PL', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

async function requestJson(url, options = {}) {
    const response = await fetch(url, {
        headers: { 'Content-Type': 'application/json' },
        ...options,
    });

    if (response.status === 401) {
        window.location.href = 'login.php';
        throw new Error('Musisz się zalogować.');
    }

    const data = await response.json();
    if (!response.ok || !data.ok) {
        throw new Error(data.error || 'Wystąpił błąd połączenia.');
    }
    return data;
}

async function loadFriends() {
    const data = await requestJson('api/friends.php');
    state.friends = data.data.friends || [];
    renderFriendOptions();
}

async function loadEvents() {
    const { from, to } = calendarBounds();
    const params = new URLSearchParams({
        from: localDateValue(from) + ' 00:00:00',
        to: localDateValue(to) + ' 23:59:59',
        type: els.typeFilter.value,
        status: els.statusFilter.value,
        search: els.searchInput.value.trim(),
    });

    try {
        const data = await requestJson(`api/events.php?${params.toString()}`);
        state.events = data.events;
        render();
    } catch (error) {
        els.calendarGrid.innerHTML = `<div class="empty-state wide">${escapeHtml(error.message)}</div>`;
    }
}

function renderFriendOptions(selected = []) {
    els.shareInput.innerHTML = '';
    if (state.friends.length === 0) {
        const option = document.createElement('option');
        option.disabled = true;
        option.textContent = 'Brak znajomych';
        els.shareInput.appendChild(option);
        return;
    }

    state.friends.forEach((friend) => {
        const option = document.createElement('option');
        option.value = friend.id;
        option.textContent = `${friend.name} (${friend.email})`;
        option.selected = selected.includes(Number(friend.id));
        els.shareInput.appendChild(option);
    });
}

function render() {
    renderCalendar();
    renderDashboard();
    renderAgenda();
}

function renderCalendar() {
    const { from, to } = calendarBounds();
    const today = new Date();
    const month = state.currentMonth.getMonth();
    els.monthLabel.textContent = `${monthNames[month]} ${state.currentMonth.getFullYear()}`;
    els.calendarGrid.innerHTML = '';

    for (let cursor = new Date(from); cursor <= to; cursor.setDate(cursor.getDate() + 1)) {
        const cellDate = new Date(cursor);
        const dayEvents = state.events.filter((event) => sameDay(mysqlToDate(event.start_at), cellDate));
        const cell = document.createElement('button');
        cell.type = 'button';
        cell.className = 'day-cell';
        if (cellDate.getMonth() !== month) cell.classList.add('muted-day');
        if (sameDay(cellDate, today)) cell.classList.add('today');
        cell.addEventListener('click', () => openNewEvent(cellDate));

        const top = document.createElement('span');
        top.className = 'day-number';
        top.textContent = String(cellDate.getDate());
        cell.appendChild(top);

        const list = document.createElement('span');
        list.className = 'day-events';
        dayEvents.slice(0, 4).forEach((event) => {
            const eventButton = document.createElement('span');
            eventButton.className = `event-chip type-${event.type}`;
            if (!event.can_edit) eventButton.classList.add('shared-chip');
            eventButton.textContent = `${formatTime(event.start_at)} ${event.title}`;
            eventButton.title = `${typeLabels[event.type]} - ${event.title}`;
            eventButton.addEventListener('click', (e) => {
                e.stopPropagation();
                openExistingEvent(event);
            });
            list.appendChild(eventButton);
        });

        if (dayEvents.length > 4) {
            const more = document.createElement('span');
            more.className = 'more-chip';
            more.textContent = `+${dayEvents.length - 4}`;
            list.appendChild(more);
        }

        cell.appendChild(list);
        els.calendarGrid.appendChild(cell);
    }
}

function renderDashboard() {
    const today = new Date();
    const weekStart = startOfWeek(today);
    const weekEnd = endOfWeek(today);
    const planned = state.events.filter((event) => event.status === 'planned');
    const todayEvents = planned.filter((event) => sameDay(mysqlToDate(event.start_at), today));
    const weekEvents = planned.filter((event) => {
        const start = mysqlToDate(event.start_at);
        return start >= weekStart && start <= weekEnd;
    });

    els.todayCount.textContent = todayEvents.length;
    els.weekCount.textContent = weekEvents.length;
    els.examCount.textContent = planned.filter((event) => event.type === 'egzamin').length;
    els.todoCount.textContent = planned.length;
}

function renderAgenda() {
    const today = new Date();
    const weekStart = startOfWeek(today);
    const weekEnd = endOfWeek(today);
    const planned = state.events.filter((event) => event.status === 'planned');
    const todayEvents = planned.filter((event) => sameDay(mysqlToDate(event.start_at), today));
    const weekEvents = planned.filter((event) => {
        const start = mysqlToDate(event.start_at);
        return start >= weekStart && start <= weekEnd;
    });

    renderAgendaList(els.todayList, todayEvents, 'Brak wydarzeń na dziś.');
    renderAgendaList(els.weekList, weekEvents, 'Ten tydzień jest pusty.');
    renderReminderList();
}

function renderAgendaList(container, events, emptyText) {
    container.innerHTML = '';
    if (events.length === 0) {
        container.innerHTML = `<div class="empty-state">${emptyText}</div>`;
        return;
    }

    events.slice(0, 10).forEach((event) => {
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'agenda-item';
        item.addEventListener('click', () => openExistingEvent(event));
        const shared = event.can_edit ? '' : ` · od ${escapeHtml(event.owner_name || 'znajomego')}`;
        item.innerHTML = `
            <span class="agenda-time">${formatDateTime(event.start_at)}</span>
            <strong>${escapeHtml(event.title)}</strong>
            <span>${typeLabels[event.type]}${event.location ? ' · ' + escapeHtml(event.location) : ''}${shared}</span>
        `;
        container.appendChild(item);
    });
}

function renderReminderList() {
    els.reminderList.innerHTML = '';
    if (state.reminders.length === 0) {
        els.reminderList.innerHTML = '<div class="empty-state">Brak aktywnych przypomnień.</div>';
        return;
    }

    state.reminders.slice(0, 6).forEach((reminder) => {
        const item = document.createElement('div');
        item.className = 'reminder-item';
        item.innerHTML = `<strong>${escapeHtml(reminder.title)}</strong><span>${escapeHtml(reminder.body)}</span>`;
        els.reminderList.appendChild(item);
    });
}

function setFormEnabled(enabled) {
    [...els.form.elements].forEach((element) => {
        if (element.id !== 'closeDialogBtn' && element.id !== 'cancelBtn') {
            element.disabled = !enabled;
        }
    });
    $('#cancelBtn').disabled = false;
    $('#closeDialogBtn').disabled = false;
}

function openNewEvent(date = new Date()) {
    const start = new Date(date);
    start.setHours(9, 0, 0, 0);
    const end = new Date(start);
    end.setHours(start.getHours() + 1);

    els.form.reset();
    setFormEnabled(true);
    renderFriendOptions();
    $('#eventId').value = '';
    $('#startInput').value = localDateTimeValue(start);
    $('#endInput').value = localDateTimeValue(end);
    $('#statusInput').value = 'planned';
    $('#recurrenceInput').value = 'none';
    $('#visibilityInput').value = 'private';
    els.dialogTitle.textContent = 'Nowe wydarzenie';
    els.ownerLine.textContent = '';
    els.deleteBtn.hidden = true;
    els.saveBtn.hidden = false;
    els.formError.textContent = '';
    els.dialog.showModal();
}

function openExistingEvent(event) {
    els.form.reset();
    setFormEnabled(Boolean(event.can_edit));
    renderFriendOptions((event.share_friend_ids || []).map(Number));
    $('#eventId').value = event.source_id || event.id;
    $('#titleInput').value = event.title || '';
    $('#typeInput').value = event.type || 'inne';
    $('#statusInput').value = event.status || 'planned';
    $('#startInput').value = localDateTimeValue(mysqlToDate(event.base_start_at || event.start_at));
    $('#endInput').value = event.base_end_at || event.end_at ? localDateTimeValue(mysqlToDate(event.base_end_at || event.end_at)) : '';
    $('#reminderInput').value = String(event.reminder_minutes || 0);
    $('#recurrenceInput').value = event.recurrence || 'none';
    $('#recurrenceUntilInput').value = event.recurrence_until || '';
    $('#locationInput').value = event.location || '';
    $('#visibilityInput').value = event.visibility || 'private';
    $('#sharedNoteInput').value = event.shared_note || '';
    $('#prepNoteInput').value = event.prep_note || '';
    $('#prepReminderInput').value = String(event.prep_reminder_minutes || 0);
    $('#notesInput').value = event.notes || '';
    els.dialogTitle.textContent = event.is_recurring_instance ? 'Edytujesz serię wydarzeń' : 'Edytuj wydarzenie';
    els.ownerLine.textContent = event.can_edit ? '' : `Wydarzenie udostępnione przez: ${event.owner_name || 'znajomego'}`;
    els.deleteBtn.hidden = !event.can_edit;
    els.saveBtn.hidden = !event.can_edit;
    els.formError.textContent = event.can_edit ? '' : 'To wydarzenie jest tylko do podglądu.';
    els.dialog.showModal();
}

function formPayload() {
    return {
        title: $('#titleInput').value.trim(),
        type: $('#typeInput').value,
        status: $('#statusInput').value,
        start_at: $('#startInput').value,
        end_at: $('#endInput').value || null,
        reminder_minutes: Number($('#reminderInput').value),
        recurrence: $('#recurrenceInput').value,
        recurrence_until: $('#recurrenceUntilInput').value || null,
        location: $('#locationInput').value.trim() || null,
        visibility: $('#visibilityInput').value,
        shared_note: $('#sharedNoteInput').value.trim() || null,
        share_friend_ids: [...els.shareInput.selectedOptions].map((option) => Number(option.value)).filter(Boolean),
        prep_note: $('#prepNoteInput').value.trim() || null,
        prep_reminder_minutes: Number($('#prepReminderInput').value),
        notes: $('#notesInput').value.trim() || null,
    };
}

async function saveEvent() {
    const id = $('#eventId').value;
    const method = id ? 'PUT' : 'POST';
    const url = id ? `api/events.php?id=${encodeURIComponent(id)}` : 'api/events.php';

    await requestJson(url, { method, body: JSON.stringify(formPayload()) });
    els.dialog.close();
    await loadEvents();
    await checkReminders();
}

async function deleteEvent() {
    const id = $('#eventId').value;
    if (!id || !confirm('Usunąć to wydarzenie?')) return;

    await requestJson(`api/events.php?id=${encodeURIComponent(id)}`, { method: 'DELETE' });
    els.dialog.close();
    await loadEvents();
}

async function enableNotifications() {
    if (!('Notification' in window)) {
        alert('Ta przeglądarka nie obsługuje powiadomień.');
        return;
    }
    await Notification.requestPermission();
    await checkReminders();
}

async function checkReminders() {
    try {
        const data = await requestJson('api/reminders.php');
        state.reminders = data.reminders;
        renderReminderList();

        if (!('Notification' in window) || Notification.permission !== 'granted') return;

        const seen = JSON.parse(localStorage.getItem('studentPlannerReminders') || '{}');
        state.reminders.forEach((reminder) => {
            if (seen[reminder.key]) return;
            new Notification(reminder.title, { body: reminder.body });
            seen[reminder.key] = Date.now();
        });
        localStorage.setItem('studentPlannerReminders', JSON.stringify(seen));
    } catch (error) {
        state.reminders = [];
        renderReminderList();
    }
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = String(value);
    return div.innerHTML;
}

function bindEvents() {
    $('#newEventBtn').addEventListener('click', () => openNewEvent());
    $('#prevMonthBtn').addEventListener('click', () => {
        state.currentMonth.setMonth(state.currentMonth.getMonth() - 1);
        loadEvents();
    });
    $('#nextMonthBtn').addEventListener('click', () => {
        state.currentMonth.setMonth(state.currentMonth.getMonth() + 1);
        loadEvents();
    });
    $('#closeDialogBtn').addEventListener('click', () => els.dialog.close());
    $('#cancelBtn').addEventListener('click', () => els.dialog.close());
    $('#deleteEventBtn').addEventListener('click', () => deleteEvent().catch((error) => {
        els.formError.textContent = error.message;
    }));
    $('#notifyBtn').addEventListener('click', enableNotifications);
    [els.typeFilter, els.statusFilter].forEach((element) => element.addEventListener('change', loadEvents));
    els.searchInput.addEventListener('input', () => {
        clearTimeout(window.searchTimer);
        window.searchTimer = setTimeout(loadEvents, 250);
    });
    els.form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (els.saveBtn.hidden) return;
        els.formError.textContent = '';
        saveEvent().catch((error) => {
            els.formError.textContent = error.message;
        });
    });
}

bindEvents();
Promise.all([loadFriends(), loadEvents(), checkReminders()]).catch(() => {});
setInterval(checkReminders, 60000);
