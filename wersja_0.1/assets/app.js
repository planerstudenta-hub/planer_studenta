const state = {
    currentMonth: new Date(),
    events: [],
};

const typeLabels = {
    egzamin: 'Egzamin',
    projekt: 'Projekt',
    zajecia: 'Zajecia',
    inne: 'Inne',
};

const monthNames = ['styczen', 'luty', 'marzec', 'kwiecien', 'maj', 'czerwiec', 'lipiec', 'sierpien', 'wrzesien', 'pazdziernik', 'listopad', 'grudzien'];
const $ = (selector) => document.querySelector(selector);

const els = {
    calendarGrid: $('#calendarGrid'),
    monthLabel: $('#monthLabel'),
    todayCount: $('#todayCount'),
    weekCount: $('#weekCount'),
    examCount: $('#examCount'),
    agendaList: $('#agendaList'),
    dialog: $('#eventDialog'),
    form: $('#eventForm'),
    formError: $('#formError'),
    dialogTitle: $('#dialogTitle'),
    deleteBtn: $('#deleteEventBtn'),
};

const pad = (value) => String(value).padStart(2, '0');
const localDateValue = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
const localDateTimeValue = (date) => `${localDateValue(date)}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
const mysqlToDate = (value) => new Date(String(value).replace(' ', 'T'));
const sameDay = (a, b) => localDateValue(a) === localDateValue(b);

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

async function requestJson(url, options = {}) {
    const response = await fetch(url, {
        headers: { 'Content-Type': 'application/json' },
        ...options,
    });
    const data = await response.json();
    if (!response.ok || !data.ok) {
        throw new Error(data.error || 'Blad polaczenia.');
    }
    return data;
}

async function loadEvents() {
    const { from, to } = calendarBounds();
    const params = new URLSearchParams({
        from: localDateValue(from) + ' 00:00:00',
        to: localDateValue(to) + ' 23:59:59',
    });
    const data = await requestJson(`api/events.php?${params.toString()}`);
    state.events = data.events;
    render();
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

        cell.innerHTML = `<span class="day-number">${cellDate.getDate()}</span><span class="day-events"></span>`;
        const list = cell.querySelector('.day-events');
        dayEvents.slice(0, 3).forEach((event) => {
            const item = document.createElement('span');
            item.className = `event-chip type-${event.type}`;
            item.textContent = `${formatTime(event.start_at)} ${event.title}`;
            item.addEventListener('click', (clickEvent) => {
                clickEvent.stopPropagation();
                openExistingEvent(event);
            });
            list.appendChild(item);
        });

        els.calendarGrid.appendChild(cell);
    }
}

function renderDashboard() {
    const today = new Date();
    const weekStart = startOfWeek(today);
    const weekEnd = endOfWeek(today);
    const planned = state.events.filter((event) => event.status === 'planned');

    els.todayCount.textContent = planned.filter((event) => sameDay(mysqlToDate(event.start_at), today)).length;
    els.weekCount.textContent = planned.filter((event) => {
        const start = mysqlToDate(event.start_at);
        return start >= weekStart && start <= weekEnd;
    }).length;
    els.examCount.textContent = planned.filter((event) => event.type === 'egzamin').length;
}

function renderAgenda() {
    const now = new Date();
    const upcoming = state.events
        .filter((event) => mysqlToDate(event.start_at) >= now)
        .slice(0, 8);

    els.agendaList.innerHTML = '';
    if (upcoming.length === 0) {
        els.agendaList.innerHTML = '<div class="empty-state">Brak najblizszych wydarzen.</div>';
        return;
    }

    upcoming.forEach((event) => {
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'agenda-item';
        item.innerHTML = `<strong>${escapeHtml(event.title)}</strong><span>${formatDateTime(event.start_at)} - ${typeLabels[event.type]}</span>`;
        item.addEventListener('click', () => openExistingEvent(event));
        els.agendaList.appendChild(item);
    });
}

function openNewEvent(date = new Date()) {
    const start = new Date(date);
    start.setHours(9, 0, 0, 0);
    const end = new Date(start);
    end.setHours(10, 0, 0, 0);

    els.form.reset();
    $('#eventId').value = '';
    $('#startInput').value = localDateTimeValue(start);
    $('#endInput').value = localDateTimeValue(end);
    $('#statusInput').value = 'planned';
    els.dialogTitle.textContent = 'Nowe wydarzenie';
    els.deleteBtn.hidden = true;
    els.formError.textContent = '';
    els.dialog.showModal();
}

function openExistingEvent(event) {
    els.form.reset();
    $('#eventId').value = event.id;
    $('#titleInput').value = event.title || '';
    $('#typeInput').value = event.type || 'inne';
    $('#statusInput').value = event.status || 'planned';
    $('#startInput').value = localDateTimeValue(mysqlToDate(event.start_at));
    $('#endInput').value = event.end_at ? localDateTimeValue(mysqlToDate(event.end_at)) : '';
    $('#locationInput').value = event.location || '';
    $('#notesInput').value = event.notes || '';
    els.dialogTitle.textContent = 'Edytuj wydarzenie';
    els.deleteBtn.hidden = false;
    els.formError.textContent = '';
    els.dialog.showModal();
}

function formPayload() {
    return {
        title: $('#titleInput').value.trim(),
        type: $('#typeInput').value,
        status: $('#statusInput').value,
        start_at: $('#startInput').value,
        end_at: $('#endInput').value || null,
        location: $('#locationInput').value.trim() || null,
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
}

async function deleteEvent() {
    const id = $('#eventId').value;
    if (!id || !confirm('Usunac wydarzenie?')) return;
    await requestJson(`api/events.php?id=${encodeURIComponent(id)}`, { method: 'DELETE' });
    els.dialog.close();
    await loadEvents();
}

function formatTime(value) {
    return mysqlToDate(value).toLocaleTimeString('pl-PL', { hour: '2-digit', minute: '2-digit' });
}

function formatDateTime(value) {
    return mysqlToDate(value).toLocaleString('pl-PL', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = String(value);
    return div.innerHTML;
}

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
els.form.addEventListener('submit', (event) => {
    event.preventDefault();
    saveEvent().catch((error) => {
        els.formError.textContent = error.message;
    });
});

loadEvents().catch((error) => {
    els.calendarGrid.innerHTML = `<div class="empty-state wide">${escapeHtml(error.message)}</div>`;
});

