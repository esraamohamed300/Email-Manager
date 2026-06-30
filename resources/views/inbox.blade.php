@extends('layouts.app')

@section('content')

@php
    $avatarColors = [
        'bg-violet-500',
        'bg-blue-500',
        'bg-rose-500',
        'bg-teal-500',
        'bg-amber-500',
        'bg-emerald-500',
        'bg-pink-500',
        'bg-indigo-500',
    ];
@endphp

<div class="flex flex-col h-full md:hidden">

 {{-- Mobile header: title + weather + sign out --}}
    <div class="flex items-center justify-between px-4 pt-4 pb-3 border-b border-slate-100 flex-shrink-0">
        <div class="flex items-center gap-2">
            <div id="weatherBoxMobile" class="flex items-center gap-1 text-xs text-slate-400">
                <span class="opacity-50">–</span>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-500 shadow-sm active:bg-slate-50 transition-all">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Sign out
            </button>
        </form>
    </div>

    {{-- Search bar --}}
    <div class="px-3 pt-2 pb-2 flex-shrink-0">
        <div class="flex items-center gap-2 bg-slate-100 rounded-2xl px-4 py-2.5 shadow-sm">
            <svg class="text-slate-500 flex-shrink-0" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" id="searchInputMobile" placeholder="Search in messages"
                class="flex-1 bg-transparent text-sm text-slate-700 placeholder-slate-400 outline-none"
                oninput="filterTableMobile()">
        </div>
    </div>
    {{-- Email list --}}
    <div class="flex-1 overflow-y-auto" id="mobileEmailList">
        @foreach ($emails as $index => $message)
            @php
                $avatarColor     = $avatarColors[$index % count($avatarColors)];
                $isSent          = ($message['type'] ?? 'received') === 'sent';
                $contactName     = $isSent ? ($message['to']         ?? '') : ($message['from']      ?? '');
                $contactEmail    = $isSent ? ($message['toEmail']    ?? '') : ($message['fromEmail'] ?? '');
                $contactInitials = $isSent ? ($message['toInitials'] ?? '') : ($message['fromInitials'] ?? '');
                $previewBody     = (strlen($message['body']) > 55) ? substr($message['body'], 0, 55) . '…' : $message['body'];
                $type            = $message['type'] ?? 'received';
            @endphp

            <div class="mobile-email-row flex items-center gap-3 px-4 py-3 border-b border-slate-100 active:bg-slate-50 cursor-pointer"
                 data-type="{{ $type }}"
                 data-subject="{{ strtolower($message['subject']) }}"
                 data-from="{{ strtolower($contactName) }}"
                 data-body="{{ strtolower($message['body']) }}"
                 onclick="handleRowClick(event, '{{ $message['messageId'] }}')"
                 data-id="{{ $message['messageId'] }}"
                 data-thread-id="{{ $message['threadId'] }}">

                {{-- Avatar circle --}}
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-full {{ $avatarColor }} flex items-center justify-center text-white text-sm font-semibold select-none">
                        {{ $contactInitials }}
                    </div>
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    {{-- Row 1: Name + Time --}}
                    <div class="flex items-center justify-between mb-0.5">
                        <span class="text-sm font-semibold text-slate-900 truncate">{{ $contactName }}</span>
                        <span class="text-xs text-slate-400 flex-shrink-0 ml-2">{{ $message['time'] }}</span>
                    </div>
                    {{-- Row 2: Subject --}}
                    <div class="text-sm font-medium text-slate-700 truncate">{{ $message['subject'] }}</div>
                    {{-- Row 3: Preview --}}
                    <div class="text-xs text-slate-400 truncate mt-0.5">
                        @if($isSent)<span class="text-slate-500">You: </span>@endif{{ $previewBody }}
                    </div>
                </div>

            </div>
        @endforeach

        {{-- Empty state --}}
        <div id="mobileEmptyState" class="hidden flex-col items-center justify-center py-24 text-slate-400">
            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24" class="mb-3 opacity-30">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
            </svg>
            <p class="text-sm">No messages here.</p>
        </div>
    </div>

    {{-- Compose FAB (mobile) --}}
    <button type="button" onclick="openComposeModal()"
        class="fixed bottom-6 right-5 z-30 flex items-center gap-2 rounded-2xl bg-blue-50 border border-blue-100 px-5 py-3 text-sm font-semibold text-blue-700 shadow-lg active:scale-95 transition-all">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path d="M12 5v14"/><path d="M5 12h14"/>
        </svg>
        Compose
    </button>

</div>


<div class="hidden md:flex md:flex-col md:h-full">

    {{-- Page title --}}
    <div class="flex items-center justify-between px-8 pt-6 pb-2 border-b border-slate-100 flex-shrink-0">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Messages</h1>
            <p class="text-sm text-slate-400 mt-0.5" id="conv-count">{{ count($emails) }} messages</p>
        </div>
        <div class="flex items-center">
            <div id="weatherBox" class="border-t border-slate-100 py-1">
                <div class="flex items-center gap-2 text-xs text-slate-400 px-3 py-2">Loading weather...</div>
            </div>
        </div>
    </div>

    {{-- Search bar --}}
    <div class="flex items-center gap-3 px-8 py-3 border-b border-slate-100 flex-shrink-0">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" id="searchInput" placeholder="Search conversations..."
                class="rounded-lg border border-slate-200 pl-8 pr-4 py-2 text-sm text-slate-600 placeholder-slate-400 outline-none focus:border-blue-300 w-64 transition-colors"
                oninput="filterTable()">
        </div>
    </div>

    {{-- Email table --}}
    <div class="flex-1 overflow-y-auto">
        <table class="w-full text-sm border-collapse" id="emailTable">
            <thead>
                <tr class="border-b border-slate-100 bg-white sticky top-0 z-10">
                    <th class="w-10 px-4 py-3 text-left">
                        <input type="checkbox" class="check-row rounded" onchange="toggleAll(this)">
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider w-40">Contact</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider flex-1 w-0">Subject & Message</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider w-24">Time</th>
                </tr>
            </thead>
            <tbody id="emailBody">
                @foreach ($emails as $index => $message)
                    @php
                        $avatarClass     = ['bg-violet-100 text-violet-700','bg-blue-100 text-blue-700','bg-rose-100 text-rose-700','bg-teal-100 text-teal-700','bg-amber-100 text-amber-700','bg-emerald-100 text-emerald-700'][$index % 6];
                        $isSent          = ($message['type'] ?? 'received') === 'sent';
                        $contactName     = $isSent ? ($message['to']         ?? '') : ($message['from']      ?? '');
                        $contactEmail    = $isSent ? ($message['toEmail']    ?? '') : ($message['fromEmail'] ?? '');
                        $contactInitials = $isSent ? ($message['toInitials'] ?? '') : ($message['fromInitials'] ?? '');
                        $previewBody     = (strlen($message['body']) > 60) ? substr($message['body'], 0, 60) . '...' : $message['body'];
                    @endphp

                    <tr class="row-hover border-b border-slate-100 cursor-pointer transition-colors hover:bg-slate-50"
                        data-id="{{ $message['messageId'] }}"
                        data-thread-id="{{ $message['threadId'] }}"
                        data-subject="{{ strtolower($message['subject']) }}"
                        data-from="{{ strtolower($contactName) }}"
                        data-body="{{ strtolower($message['body']) }}"
                        onclick="handleRowClick(event, '{{ $message['messageId'] }}')">

                        <td class="px-4 py-4 w-10" onclick="event.stopPropagation()">
                            <input type="checkbox" class="check-row rounded" onchange="toggleRow('{{ $message['messageId'] }}', this)">
                        </td>
                        <td class="px-4 py-4 w-40">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold flex-shrink-0 {{ $avatarClass }}">
                                    {{ $contactInitials }}
                                </div>
                                <div class="min-w-0">
                                    <span class="font-medium text-slate-800 text-sm truncate block">{{ $contactName }}</span>
                                    <div class="text-xs text-slate-400 truncate">{{ $contactEmail }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4 flex-1">
                            <div class="font-medium text-slate-800 text-sm truncate mb-1">{{ $message['subject'] }}</div>
                            <div class="text-sm text-slate-600 truncate">
                                @if($isSent)<span class="text-slate-400 font-medium">You: </span>@endif
                                {{ $previewBody }}
                            </div>
                        </td>
                        <td class="px-4 py-4 w-24">
                            <span class="text-xs text-slate-400">{{ $message['time'] }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div id="emptyState" class="hidden flex-col items-center justify-center py-24 text-slate-400">
            <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" class="mb-3 opacity-40">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
            </svg>
            <p class="text-sm">No conversations match your filters.</p>
        </div>
    </div>

    {{-- Desktop Compose FAB --}}
    <button type="button" onclick="openComposeModal()"
        class="fixed bottom-7 right-7 z-30 inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-2xl transition hover:-translate-y-0.5 hover:bg-slate-700">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M12 5v14"/><path d="M5 12h14"/>
        </svg>
        Compose
    </button>

</div>

{{-- Compose Modal --}}
<dialog id="composeDialog" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-slate-900/45 backdrop:backdrop-blur-sm">
    <div class="flex min-h-full items-end justify-end p-4 sm:p-7">
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
                <h3 class="text-sm font-semibold text-slate-800">New Message</h3>
                <button type="button" onclick="closeComposeModal()" class="rounded-md border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Close</button>
            </div>
            <form id="composeForm" class="space-y-3 px-4 py-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">To</label>
                    <input id="composeEmail" name="composeEmail" type="email" required placeholder="name@example.com"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none focus:border-slate-400">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</label>
                    <input id="composeSubject" name="composeSubject" type="text" required placeholder="Write subject"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none focus:border-slate-400">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Message</label>
                    <textarea id="composeBody" name="composeBody" rows="5" placeholder="Write your message..."
                        class="w-full resize-none rounded-lg border border-slate-200 px-3 py-2 text-sm leading-6 text-slate-700 outline-none focus:border-slate-400"></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Attachment <span class="normal-case font-normal text-slate-400">(optional · JPG/PNG/GIF/PDF · max 5 MB)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer w-fit">
                        <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 transition-colors">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                            Attach file
                        </span>
                        <input id="composeFile" type="file" accept=".jpg,.jpeg,.png,.gif,.pdf" class="hidden"
                            onchange="previewAttachment(this,'composeFilePreview')">
                    </label>
                    <div id="composeFilePreview" class="mt-1.5 text-xs text-slate-500"></div>
                </div>
                <div class="flex items-center justify-between pt-1">
                    <p class="text-xs text-slate-400">Sends as you · appears in inbox.</p>
                    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Send</button>
                </div>
            </form>
        </div>
    </div>
</dialog>

{{-- Conversation Modal --}}
<dialog id="messageDialog" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-slate-900/45 backdrop:backdrop-blur-sm">
    <div class="flex min-h-full items-end justify-center p-3 sm:items-center sm:p-6">
        <div class="relative w-full max-w-5xl overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-2xl">
            <div class="border-b border-slate-200 bg-gradient-to-b from-slate-50 to-white px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Conversation</p>
                        <h3 id="messageDialogTitle" class="mt-1 truncate text-xl font-semibold text-slate-900">Message</h3>
                        <p id="messageDialogMeta" class="mt-1 truncate text-sm text-slate-600"></p>
                        <p id="messageDialogSubMeta" class="mt-1 text-xs text-slate-400"></p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button type="button" onclick="document.getElementById('replyInput')?.focus()"
                            class="rounded-md border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Reply</button>
                        <button type="button" onclick="closeConversationModal()"
                            class="rounded-md bg-slate-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-700">Close</button>
                    </div>
                </div>
            </div>
            <div id="messageThread" class="max-h-[50vh] space-y-4 overflow-y-auto bg-slate-100/70 px-6 py-5"></div>
            <form id="replyForm" class="border-t border-slate-200 bg-white px-6 py-4" onsubmit="sendReply(event)">
                @csrf
                <label class="mb-2 block text-sm font-medium text-slate-700">Reply</label>
                <textarea id="replyInput" rows="3" placeholder="Write your reply..."
                    class="w-full resize-y rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-700 outline-none transition focus:border-slate-400 focus:bg-white"></textarea>
                <div class="mt-2">
                    <label class="flex items-center gap-2 cursor-pointer w-fit">
                        <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 transition-colors">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                            Attach file
                        </span>
                        <input id="replyFile" type="file" accept=".jpg,.jpeg,.png,.gif,.pdf" class="hidden"
                            onchange="previewAttachment(this,'replyFilePreview')">
                    </label>
                    <div id="replyFilePreview" class="mt-1 text-xs text-slate-500"></div>
                </div>
                <div class="mt-3 flex items-center justify-between">
                    <p class="text-xs text-slate-400">Your reply appears instantly in this thread.</p>
                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Send Reply</button>
                </div>
            </form>
        </div>
    </div>
</dialog>


@endsection

@section('scripts')

<script>
    window.emailData  = {!! json_encode($emails) !!};
    window.userEmail  = {!! json_encode(Auth::user()->email) !!};
    window.uploadsUrl = '{{ asset('uploads') }}/';
    window.routes = {
        read    : '{{ route('emails.read') }}',
        store   : '{{ route('emails.store') }}',
        destroy : '{{ url('emails') }}',
        reply   : '{{ url('emails') }}',
        upload  : '{{ route('upload.store') }}',
        weather : '{{ route('weather.fetch') }}',
    };

    /* ── Mobile search filter ── */
    function filterTableMobile() {
        const q = document.getElementById('searchInputMobile').value.toLowerCase();
        document.querySelectorAll('.mobile-email-row').forEach(row => {
            const hit = !q ||
                row.dataset.subject.includes(q) ||
                row.dataset.from.includes(q) ||
                row.dataset.body.includes(q);
            row.style.display = hit ? 'flex' : 'none';
        });
        checkMobileEmpty();
    }

    function checkMobileEmpty() {
        const anyVisible = [...document.querySelectorAll('.mobile-email-row')]
            .some(r => r.style.display !== 'none');
        const empty = document.getElementById('mobileEmptyState');
        if (empty) empty.style.display = anyVisible ? 'none' : 'flex';
    }

    /* ── Weather: populate both desktop and mobile boxes ── */
    const origWeatherBox   = document.getElementById('weatherBox');
    const mobileWeatherBox = document.getElementById('weatherBoxMobile');

    const weatherObserver = new MutationObserver(() => {
        if (mobileWeatherBox && origWeatherBox) {
            mobileWeatherBox.innerHTML = origWeatherBox.innerHTML;
        }
    });
    if (origWeatherBox) {
        weatherObserver.observe(origWeatherBox, { childList: true, subtree: true });
    }


    const authId = {{ auth()->id() }};

    // Track which chat is currently open in the modal
    let activeChatId = null;

    window.Echo.private('App.Models.User.' + authId)
        .listen('EmailSent', (e) => {
            // Skip any self-notifications and keep the optimistic UI untouched.
            if (e.sender_id === authId) return;

            if (String(activeChatId) === String(e.chat_id)) {
                appendToThread(e);
            }

            updateInboxPreview(e);

            if (typeof reloadEmails === 'function') {
                reloadEmails();
            }
        });


    function appendToThread(e) {
        const thread = document.getElementById('messageThread');
        if (!thread) return;

        const html = `
            <div class="flex justify-start">
                <div class="max-w-[70%] rounded-2xl rounded-tl-sm bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500 mb-1">${e.sender_name}</p>
                    <p class="text-sm text-slate-800 leading-relaxed">${e.message}</p>
                    <p class="text-xs text-slate-400 mt-1 text-right">${e.created_at}</p>
                </div>
            </div>`;

        thread.insertAdjacentHTML('beforeend', html);
        thread.scrollTop = thread.scrollHeight;
    }

   
    function updateInboxPreview(e) {
        // Desktop row
        const desktopRow = document.querySelector(`tr[data-thread-id="${e.chat_id}"]`);
        if (desktopRow) {
            const bodyCell = desktopRow.querySelector('td:nth-child(4) .text-sm.text-slate-600');
            if (bodyCell) bodyCell.textContent = e.message.substring(0, 60);
        }

        // Mobile row
        const mobileRow = document.querySelector(`.mobile-email-row[data-thread-id="${e.chat_id}"]`);
        if (mobileRow) {
            const preview = mobileRow.querySelector('.text-xs.text-slate-400.truncate');
            if (preview) preview.textContent = e.message.substring(0, 55);
        }
    }

   
    function setActiveChatId(chatId) {
        activeChatId = String(chatId);
    }

    function clearActiveChatId() {
        activeChatId = null;
    }
</script>

@endsection