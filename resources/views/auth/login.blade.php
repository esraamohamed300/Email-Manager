<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EmailManager</title>

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'DM Sans', sans-serif; }

        /* ── Entrance animations ── */
        @keyframes slideFromRight {
            from { opacity: 0; transform: translateX(70px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes slideFromTop {
            from { opacity: 0; transform: translateY(-50px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        /* ── Exit animations ── */
        @keyframes exitLeft {
            from { opacity: 1; transform: translateX(0); }
            to   { opacity: 0; transform: translateX(-80px); }
        }
        @keyframes exitUp {
            from { opacity: 1; transform: translateY(0); }
            to   { opacity: 0; transform: translateY(-60px); }
        }

        /* ── Desktop: side-by-side ── */
        .card        { animation: fadeIn        0.4s ease both; }
        .form-panel  { animation: slideUp       0.55s cubic-bezier(.22,.68,0,1.1) 0.1s  both; }
        .image-panel { animation: slideFromRight 0.6s  cubic-bezier(.22,.68,0,1.1) 0.05s both; }

        /* ── Mobile: stacked ── */
        @media (max-width: 640px) {
            .image-panel { animation: slideFromTop 0.55s cubic-bezier(.22,.68,0,1.1) 0.05s both; }
        }

        /* Desktop exit */
        .card.exit-to-register .form-panel  { animation: exitLeft 0.35s cubic-bezier(.55,0,1,.45) both; }
        .card.exit-to-register .image-panel { animation: exitLeft 0.35s cubic-bezier(.55,0,1,.45) 0.05s both; }
        .card.exit-to-register              { animation: none; }

        /* Mobile exit */
        @media (max-width: 640px) {
            .card.exit-to-register .image-panel { animation: exitUp   0.3s cubic-bezier(.55,0,1,.45) both; }
            .card.exit-to-register .form-panel  { animation: exitLeft 0.3s cubic-bezier(.55,0,1,.45) 0.05s both; }
        }
    </style>
</head>

<body class="bg-white min-h-screen flex items-center justify-center">

    {{-- ════════════════════════════════════════════
         MOBILE  (<640px): full-screen stacked layout
         DESKTOP (≥640px): centered card side-by-side
    ════════════════════════════════════════════ --}}

    <div class="card w-full min-h-screen sm:min-h-0 sm:max-w-[950px] sm:max-h-[550px]
                bg-white sm:rounded-[50px] sm:shadow-xl sm:border sm:border-slate-100
                flex flex-col sm:flex-row sm:p-3">

        {{-- ── Image panel ── --}}
        {{-- Mobile: full-width top image, no radius
             Desktop: right panel, rounded --}}
<div class="image-panel
            w-full sm:h-auto
            sm:flex-1 sm:ml-auto sm:self-stretch
            order-first sm:order-last
            px-4 pt-4 sm:p-0">   {{-- padding creates the "box" gap on mobile --}}
    <img src="{{ asset('images/bb.svg') }}" alt=""
         class="w-full h-56 sm:h-full object-cover
                rounded-2xl sm:rounded-r-[47px]">  {{-- rounded-2xl on mobile, original on desktop --}}
</div>

        {{-- ── Form panel ── --}}
        <div class="form-panel order-last sm:order-first
                    flex flex-col
                    px-6 sm:px-8
                    pt-8 sm:pt-10
                    pb-10 sm:pb-8
                    sm:ml-20 flex-1 sm:flex-none">

            {{-- Heading --}}
            <div class="mb-7 sm:mb-8">
                <h1 class="text-2xl sm:text-3xl font-semibold text-slate-900 mb-2">
                    Welcome Back 👋
                </h1>
                <p class="text-sm text-slate-500 leading-relaxed">
                    Today is a new day. It's your day. You shape it.<br class="hidden sm:block">
                    Sign in to start managing your projects.
                </p>
            </div>

            @if ($errors->any())
                <div class="mb-4 bg-red-50 text-red-600 border border-red-200 text-sm rounded-xl px-4 py-3">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Email
                    </label>
                    <input
                        type="email" name="email" id="email" required autocomplete="email"
                        value="{{ old('email') }}"
                        class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all placeholder-slate-400"
                        placeholder="Example@email.com"
                    >
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Password
                    </label>
                    <input
                        type="password" name="password" id="password" required autocomplete="current-password"
                        class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all placeholder-slate-400"
                        placeholder="At least 8 characters"
                    >
                    <div class="text-right mt-2">
                        <a href="{{ route('password.request') }}"
                           class="text-sm font-medium text-[#4f8ef7] hover:underline">
                            Forgot Password?
                        </a>
                    </div>
                </div>

                {{-- Submit --}}
                <button type="submit"
                        class="w-full rounded-xl bg-[#162D3A] px-4 py-3.5 text-sm font-semibold text-white shadow-md hover:bg-slate-800 active:scale-[.98] transition-all">
                    Sign in
                </button>
            </form>

            <p class="mt-8 text-center text-sm text-slate-500">
                Don't you have an account?
                <a href="{{ route('register') }}" id="to-register"
                   class="font-semibold text-[#4f8ef7] hover:underline focus:outline-none">
                    Sign up
                </a>
            </p>

        </div>

    </div>

<script>
    document.getElementById('to-register').addEventListener('click', function (e) {
        e.preventDefault();
        const href = this.href;
        document.querySelector('.card').classList.add('exit-to-register');
        setTimeout(() => { window.location.href = href; }, 400);
    });
</script>

</body>
</html>