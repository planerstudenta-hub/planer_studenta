const $ = (selector) => document.querySelector(selector);

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
    if (!response.ok || !data.ok) throw new Error(data.error || 'Wystąpił błąd.');
    return data;
}

async function loadFriends() {
    const data = await requestJson('api/friends.php');
    renderList($('#friendsList'), data.data.friends, 'Nie masz jeszcze znajomych.', friendTemplate);
    renderList($('#incomingList'), data.data.incoming, 'Brak zaproszeń.', incomingTemplate);
    renderList($('#outgoingList'), data.data.outgoing, 'Brak wysłanych zaproszeń.', outgoingTemplate);
}

function renderList(container, items, emptyText, template) {
    container.innerHTML = '';
    if (!items.length) {
        container.innerHTML = `<div class="empty-state">${emptyText}</div>`;
        return;
    }
    items.forEach((item) => container.appendChild(template(item)));
}

function baseItem(item) {
    const node = document.createElement('div');
    node.className = 'agenda-item static';
    node.innerHTML = `<strong>${escapeHtml(item.name)}</strong><span>${escapeHtml(item.email)}</span>`;
    return node;
}

function friendTemplate(item) {
    const node = baseItem(item);
    const btn = document.createElement('button');
    btn.className = 'button danger small';
    btn.type = 'button';
    btn.textContent = 'Usuń';
    btn.addEventListener('click', () => removeFriend(item.friendship_id));
    node.appendChild(btn);
    return node;
}

function incomingTemplate(item) {
    const node = baseItem(item);
    const row = document.createElement('div');
    row.className = 'mini-actions';
    row.innerHTML = `
        <button class="button primary small" type="button">Akceptuj</button>
        <button class="button muted small" type="button">Odrzuć</button>
    `;
    row.children[0].addEventListener('click', () => respond(item.friendship_id, 'accept'));
    row.children[1].addEventListener('click', () => respond(item.friendship_id, 'reject'));
    node.appendChild(row);
    return node;
}

function outgoingTemplate(item) {
    const node = baseItem(item);
    const btn = document.createElement('button');
    btn.className = 'button muted small';
    btn.type = 'button';
    btn.textContent = 'Anuluj';
    btn.addEventListener('click', () => removeFriend(item.friendship_id));
    node.appendChild(btn);
    return node;
}

async function respond(friendshipId, action) {
    await requestJson('api/friends.php', {
        method: 'PUT',
        body: JSON.stringify({ friendship_id: friendshipId, action }),
    });
    await loadFriends();
}

async function removeFriend(friendshipId) {
    await requestJson(`api/friends.php?id=${encodeURIComponent(friendshipId)}`, { method: 'DELETE' });
    await loadFriends();
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = String(value);
    return div.innerHTML;
}

$('#friendForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    $('#friendError').textContent = '';
    try {
        await requestJson('api/friends.php', {
            method: 'POST',
            body: JSON.stringify({ email: $('#friendEmail').value }),
        });
        $('#friendEmail').value = '';
        await loadFriends();
    } catch (error) {
        $('#friendError').textContent = error.message;
    }
});

loadFriends().catch((error) => {
    $('#friendError').textContent = error.message;
});
