const typeLabels = {
    egzamin: 'Egzaminy',
    projekt: 'Projekty',
    zajecia: 'Zajęcia',
    praktyki: 'Praktyki',
    wyjazd: 'Wyjazdy',
    sluzba: 'Służba',
    inne: 'Inne',
};

async function requestJson(url) {
    const response = await fetch(url);
    if (response.status === 401) {
        window.location.href = 'login.php';
        throw new Error('Musisz się zalogować.');
    }
    const data = await response.json();
    if (!response.ok || !data.ok) throw new Error(data.error || 'Wystąpił błąd.');
    return data;
}

function $(selector) {
    return document.querySelector(selector);
}

function renderBars(container, data, labels = {}) {
    container.innerHTML = '';
    const entries = Object.entries(data || {});
    if (!entries.length) {
        container.innerHTML = '<div class="empty-state">Brak danych.</div>';
        return;
    }
    const max = Math.max(...entries.map(([, value]) => Number(value)), 1);
    entries.forEach(([key, value]) => {
        const row = document.createElement('div');
        row.className = 'bar-row';
        row.innerHTML = `
            <div><strong>${labels[key] || key}</strong><span>${value}</span></div>
            <i style="width:${Math.max(8, (Number(value) / max) * 100)}%"></i>
        `;
        container.appendChild(row);
    });
}

async function loadReport() {
    const data = await requestJson('api/reports.php');
    const report = data.report;
    $('#reportTotal').textContent = report.total;
    $('#reportUpcoming').textContent = report.upcoming_7_days;
    $('#reportOverdue').textContent = report.overdue;
    $('#reportShared').textContent = report.shared;
    renderBars($('#typeReport'), report.by_type, typeLabels);
    renderBars($('#statusReport'), report.by_status, { planned: 'Zaplanowane', done: 'Zrobione' });
    renderBars($('#busyReport'), report.busy_days);
}

loadReport().catch((error) => {
    $('#typeReport').innerHTML = `<div class="empty-state">${error.message}</div>`;
});
