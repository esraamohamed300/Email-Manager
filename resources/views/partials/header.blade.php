<aside class="w-64 flex-shrink-0 border-r border-slate-200 flex flex-col h-full bg-white">

    {{-- Brand --}}
    <div class="flex items-center px-5 py-4 border-b border-slate-100">
        <img src="{{ asset('images/logo.png') }}" alt="EmailManager"
            class="h-10 w-auto max-w-[160px] object-contain"
            loading="eager" decoding="async">
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 px-3 pt-3 space-y-0.5">
        <a href="{{ route('inbox') }}"
            class="sidebar-link active flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-blue-600">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
            </svg>
            Inbox
            <span id="inboxBadge"
                class="ml-auto bg-blue-600 text-white text-[10px] font-semibold mono px-1.5 py-0.5 rounded-full {{ count($emails ?? []) === 0 ? 'hidden' : '' }}">
                {{ count($emails ?? []) }}
            </span>
        </a>
    </nav>



    {{-- User profile --}}
    <div class="flex items-center gap-3 px-4 py-3 border-t border-slate-100">
        @php
            $userName = Auth::user()->name ?? 'User';
            $initials = strtoupper(implode('', array_map(
                fn($w) => mb_substr($w, 0, 1),
                array_filter(explode(' ', trim($userName)))
            )));
        @endphp

        <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
            {{ $initials }}
        </div>
        <div class="flex-1 min-w-0">
            <div class="text-sm font-medium text-slate-800 truncate">{{ Auth::user()->name }}</div>
            <div class="text-xs text-slate-400 truncate">{{ Auth::user()->email }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-slate-400 hover:text-red-500 transition-colors" title="Logout">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H9m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h6a3 3 0 013 3v1"/>
                </svg>
            </button>
        </form>
    </div>

</aside>