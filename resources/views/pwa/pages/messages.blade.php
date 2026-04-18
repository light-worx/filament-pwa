@extends('pwa::layouts.app')

@section('content')
<style>
    .msg-card {
        background: #fff;
        border-radius: 14px;
        border: none;
        box-shadow: 0 1px 4px rgba(0,0,0,.07);
        margin-bottom: .75rem;
        transition: box-shadow .15s;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }
    .msg-card:hover { box-shadow: 0 3px 12px rgba(0,0,0,.12); }
    .msg-card.unread { border-left: 4px solid var(--pwa-accent, #3b82f6); }
    .msg-card.unread .msg-title { font-weight: 700; }
    .msg-card .msg-body-preview {
        color: #6b7280; font-size: .83rem;
        display: -webkit-box; -webkit-line-clamp: 2;
        -webkit-box-orient: vertical; overflow: hidden;
    }
    .msg-card .msg-meta { font-size: .72rem; color: #9ca3af; }
    .msg-card .msg-actions { border-top: 1px solid #f3f4f6; }

    /* Detail panel */
    #msg-detail {
        display: none;
        position: fixed;
        inset: 0;
        background: var(--pwa-body-bg, #f5f6f8);
        z-index: 1060;
        overflow-y: auto;
        padding-bottom: 80px;
    }
    #msg-detail.open { display: block; }
    .detail-toolbar {
        position: sticky; top: 0;
        background: var(--pwa-toolbar-bg, #fff);
        border-bottom: 1px solid rgba(0,0,0,.07);
        z-index: 10; padding: 10px 16px;
        display: flex; align-items: center; gap: 12px;
    }
    .detail-body {
        white-space: pre-wrap;
        font-size: .93rem;
        line-height: 1.65;
        color: #1f2937;
    }
    .reply-bar {
        position: fixed;
        bottom: 60px; left: 0; right: 0;
        background: #fff;
        border-top: 1px solid #e5e7eb;
        padding: 10px 16px;
        display: flex; gap: 8px; align-items: flex-end;
    }
    .reply-bar textarea {
        flex: 1; resize: none; border-radius: 10px;
        border: 1px solid #e5e7eb; font-size: .875rem;
        padding: 8px 12px; max-height: 120px;
    }
    .reply-bar textarea:focus {
        outline: none; border-color: var(--pwa-accent, #3b82f6);
        box-shadow: 0 0 0 2px rgba(59,130,246,.15);
    }

    /* Empty state */
    .inbox-empty {
        text-align: center; padding: 60px 20px; color: #9ca3af;
    }
    .inbox-empty i { font-size: 3rem; margin-bottom: 12px; display: block; }

    /* Bulk action bar */
    #bulk-bar {
        position: sticky; top: 0; z-index: 5;
        background: #fff; border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,.1);
        padding: 8px 14px; margin-bottom: 12px;
        display: none; align-items: center; gap: 8px;
    }
    #bulk-bar.show { display: flex; }
</style>

{{-- ── Inbox list ──────────────────────────────────────────────────────── --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="fw-bold mb-0">
        <i class="bi bi-inbox me-2 text-muted"></i>Inbox
        <span id="unread-badge" class="badge bg-primary ms-1 d-none" style="font-size:.7rem"></span>
    </h6>
    <button class="btn btn-sm btn-outline-secondary d-none" id="select-mode-btn">
        Select
    </button>
</div>

{{-- Bulk action bar (shown when messages are selected) --}}
<div id="bulk-bar">
    <span id="selected-count" class="small text-muted me-auto">0 selected</span>
    <button class="btn btn-sm btn-outline-secondary" id="bulk-unread-btn">
        <i class="bi bi-envelope me-1"></i>Unread
    </button>
    <button class="btn btn-sm btn-outline-secondary" id="bulk-read-btn">
        <i class="bi bi-envelope-open me-1"></i>Read
    </button>
    <button class="btn btn-sm btn-outline-danger" id="bulk-delete-btn">
        <i class="bi bi-trash me-1"></i>Delete
    </button>
    <button class="btn btn-sm btn-link text-muted p-0" id="cancel-select-btn">Cancel</button>
</div>

<div id="inbox-list">
    <div class="inbox-empty">
        <i class="bi bi-hourglass-split"></i>
        Loading messages…
    </div>
</div>

{{-- ── Message detail panel ────────────────────────────────────────────── --}}
<div id="msg-detail">
    <div class="detail-toolbar">
        <button class="btn btn-sm text-muted p-1" id="detail-back-btn" aria-label="Back">
            <i class="bi bi-arrow-left fs-5"></i>
        </button>
        <div class="flex-grow-1">
            <div id="detail-title" class="fw-semibold" style="font-size:.95rem"></div>
            <div id="detail-meta" class="text-muted" style="font-size:.72rem"></div>
        </div>
        <button class="btn btn-sm text-muted" id="detail-toggle-read-btn" title="Toggle read/unread">
            <i class="bi bi-envelope"></i>
        </button>
        <button class="btn btn-sm text-danger" id="detail-delete-btn" title="Delete">
            <i class="bi bi-trash"></i>
        </button>
    </div>

    <div class="px-3 pt-3">
        <div id="detail-body" class="detail-body mb-3"></div>
    </div>

    {{-- Reply bar (only shown when sender_phone exists) --}}
    <div class="reply-bar d-none" id="reply-bar">
        <textarea id="reply-text" rows="2" placeholder="Write a reply…"></textarea>
        <button class="btn btn-primary btn-sm" id="send-reply-btn" style="border-radius:10px">
            <i class="bi bi-send-fill"></i>
        </button>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const CSRF     = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const STORAGE  = 'pwa_device_id';
    const PWA_BASE = (document.querySelector('meta[name="pwa-base"]')?.content ?? '/app')
                     .replace(/\/$/, '');

    function deviceId() { return localStorage.getItem(STORAGE) ?? ''; }

    async function api(url, method = 'GET', body = null) {
        const opts = {
            method,
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        };
        if (body) {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(body);
        }
        const res  = await fetch(url, opts);
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || 'HTTP ' + res.status);
        return data;
    }

    // ── State ──────────────────────────────────────────────────────────────
    let messages     = [];
    let currentMsg   = null;
    let selectMode   = false;
    let selectedIds  = new Set();

    // ── DOM refs ───────────────────────────────────────────────────────────
    const inboxList       = document.getElementById('inbox-list');
    const unreadBadge     = document.getElementById('unread-badge');
    const selectModeBtn   = document.getElementById('select-mode-btn');
    const bulkBar         = document.getElementById('bulk-bar');
    const selectedCount   = document.getElementById('selected-count');
    const detail          = document.getElementById('msg-detail');
    const detailTitle     = document.getElementById('detail-title');
    const detailMeta      = document.getElementById('detail-meta');
    const detailBody      = document.getElementById('detail-body');
    const detailBackBtn   = document.getElementById('detail-back-btn');
    const detailDeleteBtn = document.getElementById('detail-delete-btn');
    const detailToggleBtn = document.getElementById('detail-toggle-read-btn');
    const replyBar        = document.getElementById('reply-bar');
    const replyText       = document.getElementById('reply-text');
    const sendReplyBtn    = document.getElementById('send-reply-btn');

    // ── Utilities ──────────────────────────────────────────────────────────
    function timeAgo(iso) {
        const diff = Math.floor((Date.now() - new Date(iso)) / 1000);
        if (diff < 60)   return 'Just now';
        if (diff < 3600) return Math.floor(diff/60) + 'm ago';
        if (diff < 86400)return Math.floor(diff/3600) + 'h ago';
        return new Date(iso).toLocaleDateString();
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;')
                        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Load messages ──────────────────────────────────────────────────────
    async function loadMessages() {
        try {
            const data = await api(PWA_BASE + '/messages/list?device_id=' + encodeURIComponent(deviceId()));
            messages   = data.messages ?? [];
            renderList();
            updateBadge();
        } catch (e) {
            inboxList.innerHTML =
                `<div class="inbox-empty"><i class="bi bi-exclamation-circle"></i>Could not load messages.</div>`;
        }
    }

    // ── Render list ────────────────────────────────────────────────────────
    function renderList() {
        if (!messages.length) {
            inboxList.innerHTML =
                `<div class="inbox-empty"><i class="bi bi-inbox"></i>Your inbox is empty.</div>`;
            selectModeBtn.classList.add('d-none');
            return;
        }

        selectModeBtn.classList.remove('d-none');

        inboxList.innerHTML = messages.map(m => `
            <div class="msg-card ${m.seen ? '' : 'unread'}" data-id="${m.id}">
                <div class="p-3">
                    ${selectMode ? `
                    <div class="d-flex align-items-start gap-2">
                        <input type="checkbox" class="form-check-input mt-1 flex-shrink-0 msg-checkbox"
                               data-id="${m.id}" ${selectedIds.has(m.id) ? 'checked' : ''}>
                        <div class="flex-grow-1">` : '<div>'}
                            <div class="d-flex justify-content-between">
                                <div class="msg-title" style="font-size:.88rem">${escHtml(m.title)}</div>
                                <div class="msg-meta ms-2 flex-shrink-0">${timeAgo(m.created_at)}</div>
                            </div>
                            <div class="msg-body-preview mt-1">${escHtml(m.message)}</div>
                            ${m.sender_name ? `<div class="msg-meta mt-1"><i class="bi bi-person me-1"></i>${escHtml(m.sender_name)}</div>` : ''}
                        </div>
                    ${selectMode ? '</div>' : ''}
                </div>
            </div>
        `).join('');

        // Event delegation
        inboxList.querySelectorAll('.msg-card').forEach(card => {
            card.addEventListener('click', e => {
                const id = parseInt(card.dataset.id);
                if (selectMode) {
                    const cb = card.querySelector('.msg-checkbox');
                    if (e.target !== cb) cb.checked = !cb.checked;
                    toggleSelected(id, cb.checked);
                } else {
                    openMessage(id);
                }
            });
        });

        inboxList.querySelectorAll('.msg-checkbox').forEach(cb => {
            cb.addEventListener('change', e => {
                e.stopPropagation();
                toggleSelected(parseInt(cb.dataset.id), cb.checked);
            });
        });
    }

    function updateBadge() {
        const unread = messages.filter(m => !m.seen).length;
        if (unread > 0) {
            unreadBadge.textContent = unread;
            unreadBadge.classList.remove('d-none');
        } else {
            unreadBadge.classList.add('d-none');
        }
    }

    // ── Select mode ────────────────────────────────────────────────────────
    function enterSelectMode() {
        selectMode = true;
        selectedIds.clear();
        selectModeBtn.textContent = 'Done';
        bulkBar.classList.add('show');
        renderList();
        updateSelectedCount();
    }

    function exitSelectMode() {
        selectMode = false;
        selectedIds.clear();
        selectModeBtn.textContent = 'Select';
        bulkBar.classList.remove('show');
        renderList();
    }

    function toggleSelected(id, checked) {
        checked ? selectedIds.add(id) : selectedIds.delete(id);
        updateSelectedCount();
    }

    function updateSelectedCount() {
        selectedCount.textContent = selectedIds.size + ' selected';
    }

    // ── Open message detail ────────────────────────────────────────────────
    async function openMessage(id) {
        currentMsg = messages.find(m => m.id === id);
        if (!currentMsg) return;

        detailTitle.textContent = currentMsg.title;
        detailBody.textContent  = currentMsg.message;

        const from = currentMsg.sender_name
            ? `From: ${currentMsg.sender_name}${currentMsg.sender_phone ? ' · ' + currentMsg.sender_phone : ''}`
            : '';
        detailMeta.textContent = [timeAgo(currentMsg.created_at), from].filter(Boolean).join(' · ');

        // Toggle read button icon
        updateDetailReadBtn();

        // Reply bar
        if (currentMsg.sender_phone) {
            replyBar.classList.remove('d-none');
            replyText.value = '';
        } else {
            replyBar.classList.add('d-none');
        }

        detail.classList.add('open');
        detail.scrollTop = 0;

        // Mark as read if not already
        if (!currentMsg.seen) {
            await markSeen([id], true);
        }
    }

    function closeDetail() {
        detail.classList.remove('open');
        currentMsg = null;
    }

    function updateDetailReadBtn() {
        if (!currentMsg) return;
        detailToggleBtn.innerHTML = currentMsg.seen
            ? '<i class="bi bi-envelope"></i>'        // click to mark unread
            : '<i class="bi bi-envelope-open"></i>';  // click to mark read
        detailToggleBtn.title = currentMsg.seen ? 'Mark as unread' : 'Mark as read';
    }

    // ── API actions ────────────────────────────────────────────────────────
    async function markSeen(ids, seen) {
        await api(PWA_BASE + '/messages/seen', 'POST', { device_id: deviceId(), ids, seen });
        ids.forEach(id => {
            const m = messages.find(m => m.id === id);
            if (m) m.seen = seen;
        });
        renderList();
        updateBadge();
    }

    async function deleteMessages(ids) {
        await api(PWA_BASE + '/messages/delete', 'POST', { device_id: deviceId(), ids });
        messages = messages.filter(m => !ids.includes(m.id));
        renderList();
        updateBadge();
    }

    async function sendReply() {
        const body = replyText.value.trim();
        if (!body || !currentMsg) return;

        sendReplyBtn.disabled = true;
        try {
            await api(PWA_BASE + '/messages/reply', 'POST', {
                device_id:  deviceId(),
                message_id: currentMsg.id,
                body,
            });
            replyText.value = '';
            window.showToast?.('Reply sent');
        } catch (e) {
            window.showToast?.(e.message || 'Could not send reply', 'error');
        } finally {
            sendReplyBtn.disabled = false;
        }
    }

    // ── Event bindings ─────────────────────────────────────────────────────
    selectModeBtn.addEventListener('click', () =>
        selectMode ? exitSelectMode() : enterSelectMode()
    );

    document.getElementById('cancel-select-btn').addEventListener('click', exitSelectMode);

    document.getElementById('bulk-read-btn').addEventListener('click', async () => {
        if (!selectedIds.size) return;
        await markSeen([...selectedIds], true);
        exitSelectMode();
    });

    document.getElementById('bulk-unread-btn').addEventListener('click', async () => {
        if (!selectedIds.size) return;
        await markSeen([...selectedIds], false);
        exitSelectMode();
    });

    document.getElementById('bulk-delete-btn').addEventListener('click', async () => {
        if (!selectedIds.size) return;
        if (!confirm(`Delete ${selectedIds.size} message(s)?`)) return;
        await deleteMessages([...selectedIds]);
        exitSelectMode();
    });

    detailBackBtn.addEventListener('click', closeDetail);

    detailToggleBtn.addEventListener('click', async () => {
        if (!currentMsg) return;
        const newSeen = !currentMsg.seen;
        await markSeen([currentMsg.id], newSeen);
        currentMsg.seen = newSeen;
        updateDetailReadBtn();
    });

    detailDeleteBtn.addEventListener('click', async () => {
        if (!currentMsg) return;
        if (!confirm('Delete this message?')) return;
        await deleteMessages([currentMsg.id]);
        closeDetail();
    });

    sendReplyBtn.addEventListener('click', sendReply);

    replyText.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendReply(); }
    });

    // Auto-grow reply textarea
    replyText.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    // Close detail on back button
    window.addEventListener('popstate', () => {
        if (detail.classList.contains('open')) closeDetail();
    });

    // ── Boot ───────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', loadMessages);
})();
</script>
@endpush