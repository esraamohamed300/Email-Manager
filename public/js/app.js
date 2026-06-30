let selectedRows = new Set();
const messageMap = new Map();
const threadMap = new Map();

let activeMessageId = null;
let activeThreadId = null;

// ── Read routes from window.routes (set in Blade) ────────────────────────────
const ROUTES = window.routes || {};

// ── CSRF token for all AJAX POST/DELETE requests ─────────────────────────────
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

// ========================= HELPERS =========================

function escapeHtml(text) {
    return String(text || '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function buildInitials(name) {
    return String(name || '').split(' ').filter(Boolean)
        .slice(0, 2).map(p => p[0]).join('').toUpperCase() || '?';
}

function renderPreview(body) {
    const t = String(body || '');
    return t.length > 60 ? t.slice(0, 60) + '...' : t;
}

// ========================= FILE PREVIEW =========================

function previewAttachment(input, previewId) {
    const el = document.getElementById(previewId);
    if (!el) return;
    const file = input.files[0];
    if (!file) { el.innerHTML = ''; return; }

    const allowed = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    if (!allowed.includes(file.type)) {
        el.innerHTML = '<span class="text-red-500">Invalid file type. Allowed: JPG, PNG, GIF, PDF.</span>';
        input.value = ''; return;
    }
    if (file.size > 5 * 1024 * 1024) {
        el.innerHTML = '<span class="text-red-500">File exceeds 5 MB limit.</span>';
        input.value = ''; return;
    }
    el.innerHTML = `<span class="inline-flex items-center gap-1 text-slate-600">📎 ${escapeHtml(file.name)}</span>`;
}

// ========================= FILE UPLOAD =========================

async function uploadFile(fileInput) {
    if (!fileInput?.files[0]) return null;

    const formData = new FormData();
    formData.append('attachment', fileInput.files[0]);

    try {
        const res = await fetch(ROUTES.upload || '/upload', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            body: formData,
        });

        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
            const message = data.error || data.message || `Upload failed with status ${res.status}`;
            alert(message);
            return null;
        }

        if (!data.success) {
            alert('Upload error: ' + (data.error || 'Unknown error'));
            return null;
        }

        return data.filename;

    } catch (err) {
        alert('Upload failed: ' + err.message);
        return null;
    }
}

// ========================= ATTACHMENT HTML =========================

function attachmentHtml(filename) {
    if (!filename) return '';
    const safeName = String(filename).split('/').pop();
    const url = (window.uploadsUrl || 'uploads/') + encodeURIComponent(safeName);
    const ext = safeName.split('.').pop().toLowerCase();
    const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(ext);

    if (isImage) {
        return `<a href="${url}" target="_blank" class="block mt-3">
            <img src="${url}" alt="attachment"
                class="max-w-[220px] max-h-[180px] rounded-xl border border-slate-200 object-cover shadow-sm hover:opacity-90 transition-opacity">
        </a>`;
    }
    return `<a href="${url}" target="_blank"
        class="inline-flex items-center gap-1.5 mt-3 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors shadow-sm">
        📄 ${escapeHtml(safeName)}
    </a>`;
}

// ========================= FILTER =========================

function updateFilteredView() {
    const rows = Array.from(document.querySelectorAll('#emailBody tr'));
    const visible = rows.filter(r => r.style.display !== 'none');
    const empty = document.getElementById('emptyState');
    const count = document.getElementById('conv-count');
    if (empty) empty.classList.toggle('hidden', visible.length > 0);
    if (count) count.textContent = `${visible.length} message${visible.length !== 1 ? 's' : ''}`;
}

function filterTable() {
    const search = (document.getElementById('searchInput')?.value || '').toLowerCase();
    document.querySelectorAll('#emailBody tr').forEach(row => {
        const match = !search
            || (row.dataset.from || '').includes(search)
            || (row.dataset.subject || '').includes(search)
            || (row.dataset.body || '').includes(search);
        row.style.display = match ? '' : 'none';
    });
    updateFilteredView();
}

// ========================= CHECKBOXES =========================

function toggleRow(messageId, checkbox) {
    checkbox.checked ? selectedRows.add(messageId) : selectedRows.delete(messageId);
    updateBulkBar();
}

function toggleAll(masterCb) {
    document.querySelectorAll('#emailBody input[type=checkbox]').forEach(cb => {
        cb.checked = masterCb.checked;
        const id = cb.closest('tr').dataset.id;
        masterCb.checked ? selectedRows.add(id) : selectedRows.delete(id);
    });
    updateBulkBar();
}

function clearSelection() {
    selectedRows.clear();
    document.querySelectorAll('#emailBody input[type=checkbox]').forEach(cb => {
        cb.checked = false;
        cb.closest('tr')?.classList.remove('selected');
    });
    const master = document.querySelector('thead input[type=checkbox]');
    if (master) master.checked = false;
    updateBulkBar();
}

function updateBulkBar() {
    const bar = document.getElementById('bulkBar');
    const count = document.getElementById('bulkCount');
    if (!bar || !count) return;
    count.textContent = `${selectedRows.size} selected`;
    bar.classList.toggle('hidden', selectedRows.size === 0);
    bar.classList.toggle('flex', selectedRows.size > 0);
}

function updateInboxBadge(count) {
    const badge = document.getElementById('inboxBadge');
    if (!badge) return;
    badge.textContent = count;
    badge.classList.toggle('hidden', count === 0);
}

// ========================= THREAD =========================

function renderThreadMessages(message) {
    const threadEl = document.getElementById('messageThread');
    if (!threadEl) return;

    const thread = Array.isArray(message.thread) ? message.thread : [];
    const isSentRoot = (message.type || 'received') === 'sent';
    const externalName = isSentRoot ? (message.to || 'Recipient') : (message.from || 'Sender');

    if (thread.length === 0) {
        threadEl.innerHTML = `<div class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-6 text-center text-sm text-slate-500">No messages yet.</div>`;
        return;
    }

    threadEl.innerHTML = thread.map(msg => {
        const fromName = msg.from || 'Unknown';
        const isMine = fromName.toLowerCase() === 'you' || fromName.toLowerCase() === 'me'
            || fromName.toLowerCase() !== externalName.toLowerCase();
        const alignClass = isMine ? 'justify-end' : 'justify-start';
        const bubbleStyle = isMine ? 'border-blue-200 bg-blue-50' : 'border-slate-200 bg-white';
        const avatarStyle = isMine ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600';
        const label = isMine ? 'You' : fromName;

        return `
        <div class="flex ${alignClass}">
            <article class="max-w-[85%] rounded-2xl border p-4 shadow-sm ${bubbleStyle}">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full text-[10px] font-semibold ${avatarStyle}">${escapeHtml(buildInitials(label))}</span>
                        <p class="text-sm font-semibold">${escapeHtml(label)}</p>
                    </div>
                    <span class="text-xs text-slate-400">${escapeHtml(msg.time || '')}</span>
                </div>
                <p class="whitespace-pre-wrap text-sm leading-6">${escapeHtml(msg.body || '')}</p>
                ${attachmentHtml(msg.attachment || null)}
            </article>
        </div>`;
    }).join('');

    threadEl.scrollTop = threadEl.scrollHeight;
}

// ========================= MODALS =========================

function handleRowClick(e, messageId) {
    if (e.target.type === 'checkbox') return;
    openMessageModal(messageId);
}

function openMessageModal(messageId) {
    const message = messageMap.get(String(messageId));
    const dialog = document.getElementById('messageDialog');
    if (!message || !dialog) return;

    activeMessageId = String(messageId);
    activeThreadId = message.threadId;
    setActiveChatId(activeThreadId); // ← FIXED: use threadId not messageId

    document.getElementById('messageDialogTitle').textContent = message.subject || 'Message';

    const isSent = (message.type || 'received') === 'sent';
    const meta = document.getElementById('messageDialogMeta');
    const subMeta = document.getElementById('messageDialogSubMeta');
    if (meta) meta.textContent = `${isSent ? 'To' : 'From'} ${isSent ? message.to : message.from} · ${isSent ? message.toEmail : message.fromEmail}`;
    if (subMeta) {
        const c = Array.isArray(message.thread) ? message.thread.length : 0;
        subMeta.textContent = `${c} message${c !== 1 ? 's' : ''} in this thread`;
    }

    renderThreadMessages(message);
    if (!dialog.open) dialog.showModal?.();
}

function closeConversationModal() {
    clearActiveChatId();

    const d = document.getElementById('messageDialog');
    if (d?.open) d.close();
    const rf = document.getElementById('replyFile');
    if (rf) rf.value = '';
    const rp = document.getElementById('replyFilePreview');
    if (rp) rp.innerHTML = '';
}

function openComposeModal() {
    const d = document.getElementById('composeDialog');
    if (!d?.open) d?.showModal?.();
    document.getElementById('composeEmail')?.focus();
}

function closeComposeModal() {
    const d = document.getElementById('composeDialog');
    if (d?.open) d.close();
}

function setActive(el) {
    document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
    el.classList.add('active');
}

// ========================= RENDER EMAILS =========================

// Replace the entire renderEmails() function in app.js

function renderEmails(emails) {
    const tbody = document.getElementById('emailBody');
    const avatarColors = ['bg-violet-100 text-violet-700', 'bg-blue-100 text-blue-700',
        'bg-rose-100 text-rose-700', 'bg-teal-100 text-teal-700',
        'bg-amber-100 text-amber-700', 'bg-emerald-100 text-emerald-700'];

    messageMap.clear();
    threadMap.clear();
    tbody.innerHTML = '';

    emails.forEach((message, index) => {
        const avatarClass = avatarColors[index % avatarColors.length];
        const isSent = (message.type || 'received') === 'sent';
        const contactName = isSent ? (message.to ?? '') : (message.from ?? '');
        const contactEmail = isSent ? (message.toEmail ?? '') : (message.fromEmail ?? '');
        const contactInitials = isSent ? (message.toInitials ?? '') : (message.fromInitials ?? '');
        const previewBody = (message.body.length > 60)
            ? message.body.slice(0, 60) + '...'
            : message.body;

        // Store in messageMap keyed by messageId and threadMap keyed by threadId
        messageMap.set(String(message.messageId), message);
        threadMap.set(String(message.threadId), message);

        tbody.insertAdjacentHTML('beforeend', `
        <tr class="row-hover border-b border-slate-100 cursor-pointer hover:bg-slate-50"
            data-id="${escapeHtml(String(message.messageId))}"
            data-thread-id="${escapeHtml(String(message.threadId))}"
            data-subject="${escapeHtml((message.subject || '').toLowerCase())}"
            data-from="${escapeHtml(contactName.toLowerCase())}"
            data-body="${escapeHtml((message.body || '').toLowerCase())}"
            onclick="handleRowClick(event,'${escapeHtml(String(message.messageId))}')">

            <td class="px-4 py-4 w-10" onclick="event.stopPropagation()">
                <input type="checkbox" class="check-row rounded"
                    onchange="toggleRow('${escapeHtml(String(message.messageId))}', this)">
            </td>

            <td class="px-4 py-4 w-40">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold flex-shrink-0 ${avatarClass}">
                        ${escapeHtml(contactInitials)}
                    </div>
                    <div class="min-w-0">
                        <span class="font-medium text-slate-800 text-sm truncate block">${escapeHtml(contactName)}</span>
                        <div class="text-xs text-slate-400 truncate">${escapeHtml(contactEmail)}</div>
                    </div>
                </div>
            </td>

            <td class="px-4 py-4 flex-1">
                <div class="font-medium text-slate-800 text-sm truncate mb-1">${escapeHtml(message.subject || '')}</div>
                <div class="text-sm text-slate-600 truncate">
                    ${isSent ? '<span class="text-slate-400 font-medium">You: </span>' : ''}
                    ${escapeHtml(previewBody)}
                </div>
            </td>

            <td class="px-4 py-4 w-24">
                <span class="text-xs text-slate-400">${escapeHtml(message.time || '')}</span>
            </td>
        </tr>`);
    });

    selectedRows.clear();
    updateFilteredView();
    updateBulkBar();
    updateInboxBadge(emails.length);
}

async function reloadEmails() {
    const res = await fetch(ROUTES.read || '/emails');
    const emails = await res.json();
    renderEmails(emails);

    if (activeThreadId) {
        const updated = threadMap.get(String(activeThreadId));
        if (updated) {
            activeMessageId = String(updated.messageId);
            if (document.getElementById('messageDialog')?.open) {
                renderThreadMessages(updated);
            }
        }
    }
}

// ========================= COMPOSE =========================

document.getElementById('composeForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const toEmail = document.getElementById('composeEmail').value.trim();
    const subject = document.getElementById('composeSubject').value.trim();
    const body = document.getElementById('composeBody').value.trim();
    const fileInput = document.getElementById('composeFile');

    if (!toEmail || !subject || !body) { alert('Please fill in all required fields.'); return; }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(toEmail)) { alert('Please enter a valid email address.'); return; }

    let attachment = null;
    if (fileInput?.files[0]) {
        attachment = await uploadFile(fileInput);
        if (attachment === null) return;
    }

    const res = await fetch(ROUTES.store || '/emails', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', },
        body: JSON.stringify({ composeEmail: toEmail, composeSubject: subject, composeBody: body, attachment }),
        redirect: 'manual',
    });
    const data = await res.json();

    if (data.message !== 'Email added') { alert(data.message); return; }

    this.reset();
    document.getElementById('composeFilePreview').innerHTML = '';
    closeComposeModal();
    await reloadEmails();
});

// ========================= REPLY =========================

async function sendReply(event) {
    event.preventDefault();

    const input = document.getElementById('replyInput');
    const fileInput = document.getElementById('replyFile');
    if (!input || !activeThreadId) return;

    const messageBody = input.value.trim();

    // Upload first, then check
    let attachment = null;
    if (fileInput?.files[0]) {
        attachment = await uploadFile(fileInput);
        if (attachment === null) return;
    }

    if (!messageBody && !attachment) {
        alert('Write a reply or attach a file.');
        return;
    }

    const res = await fetch(`${ROUTES.reply || '/emails'}/${activeThreadId}/reply`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json',  // ← forces JSON response not redirect
        },
        body: JSON.stringify({ message: messageBody || '', attachment }),
        redirect: 'manual',                       // ← don't follow 302s
    });

    if (res.type === 'opaqueredirect' || res.status === 302) {
        window.location.reload();
        return;
    }

    if (!res.ok) {
        const err = await res.json().catch(() => null);
        alert(err?.message || 'Failed to send reply.');
        return;
    }

    const data = await res.json();
    if (data.message !== 'Reply added') { alert(data.message); return; }

    let current = messageMap.get(String(activeMessageId));
    if (!current && activeThreadId) {
        current = threadMap.get(String(activeThreadId));
    }

    if (current) {
        current.thread.push({
            from: 'You',
            body: messageBody || '',
            time: new Date().toLocaleString(),
            attachment: attachment,
        });
        renderThreadMessages(current);
    }

    input.value = '';
    if (fileInput) fileInput.value = '';
    document.getElementById('replyFilePreview').innerHTML = '';
    await reloadEmails();
}

// ========================= DELETE =========================

document.addEventListener('DOMContentLoaded', () => {
    // Load initial data
    if (window.emailData) renderEmails(window.emailData);

    document.getElementById('deleteBtn')?.addEventListener('click', async () => {
        if (selectedRows.size === 0) { alert('No messages selected'); return; }

        // Delete each selected chat
        for (const id of selectedRows) {
            const msg = messageMap.get(String(id));
            if (!msg) continue;
            await fetch(`${ROUTES.destroy || '/emails'}/${msg.threadId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF }
            });
        }

        selectedRows.clear();
        updateBulkBar();
        await reloadEmails();
    });

    document.getElementById('messageDialog')?.addEventListener('click', e => {
        if (e.target === document.getElementById('messageDialog')) closeConversationModal();
    });
    document.getElementById('composeDialog')?.addEventListener('click', e => {
        if (e.target === document.getElementById('composeDialog')) closeComposeModal();
    });
});