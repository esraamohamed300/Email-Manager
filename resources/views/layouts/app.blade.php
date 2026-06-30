<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EmailManager</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="bg-white text-slate-800 h-screen overflow-hidden">

<div class="flex h-screen">

    {{-- Sidebar --}}
    @include('partials.header')

    {{-- Main content — footer is included inside each view's @section('content') --}}
    <main class="flex-1 flex flex-col overflow-hidden bg-white">
        @yield('content')
    </main>
    @include('partials.footer')
</div>

@yield('scripts')

<script src="{{ asset('js/app.js') }}"></script>
<script src="{{ asset('js/API_Ops.js') }}"></script>

{{-- Reverb real-time --}}
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8/dist/web/pusher.min.js"></script>
<script>
    window.Echo = new (class {
        constructor() {
            this._pusher = new Pusher('{{ env('REVERB_APP_KEY') }}', {
                wsHost           : '{{ env('REVERB_HOST', '127.0.0.1') }}',
                wsPort           : {{ env('REVERB_PORT', 8080) }},
                wssPort          : {{ env('REVERB_PORT', 8080) }},
                forceTLS         : false,
                enabledTransports: ['ws'],
                cluster          : 'mt1',
                authEndpoint     : '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                }
            });
        }
        private(channel) {
            return this._pusher.subscribe('private-' + channel);
        }
    })();

    const userId      = {{ Auth::id() }};
    const userChannel = window.Echo.private('user.' + userId);

    userChannel.bind('new.email', function(data) {
        reloadEmails();

        if (typeof activeThreadId !== 'undefined' && activeThreadId == data.threadId) {
            const current = messageMap.get(String(data.messageId));
            if (current) renderThreadMessages(current);
        }

        const badge = document.getElementById('inboxBadge');
        if (badge) {
            badge.classList.add('bg-red-500');
            setTimeout(() => badge.classList.remove('bg-red-500'), 2000);
        }
    });
</script>

</body>
</html>