<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up - EmailManager</title>

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'DM Sans', sans-serif; }

        /* ── Entrance animations ── */
        @keyframes slideFromLeft {
            from { opacity: 0; transform: translateX(-70px); }
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
        @keyframes exitRight {
            from { opacity: 1; transform: translateX(0); }
            to   { opacity: 0; transform: translateX(80px); }
        }
        @keyframes exitUp {
            from { opacity: 1; transform: translateY(0); }
            to   { opacity: 0; transform: translateY(-60px); }
        }

        /* Desktop entrance */
        .card        { animation: fadeIn       0.4s ease both; }
        .image-panel { animation: slideFromLeft 0.6s  cubic-bezier(.22,.68,0,1.1) 0.05s both; }
        .form-panel  { animation: slideUp       0.55s cubic-bezier(.22,.68,0,1.1) 0.1s  both; }

        /* Mobile entrance */
        @media (max-width: 640px) {
            .image-panel { animation: slideFromTop 0.55s cubic-bezier(.22,.68,0,1.1) 0.05s both; }
        }

        /* Desktop exit */
        .card.exit-to-login .image-panel { animation: exitRight 0.35s cubic-bezier(.55,0,1,.45) both; }
        .card.exit-to-login .form-panel  { animation: exitRight 0.35s cubic-bezier(.55,0,1,.45) 0.05s both; }
        .card.exit-to-login              { animation: none; }

        /* Mobile exit */
        @media (max-width: 640px) {
            .card.exit-to-login .image-panel { animation: exitUp    0.3s cubic-bezier(.55,0,1,.45) both; }
            .card.exit-to-login .form-panel  { animation: exitRight 0.3s cubic-bezier(.55,0,1,.45) 0.05s both; }
        }
    </style>
</head>

<body class="bg-white min-h-screen flex items-center justify-center">

    <div class="card w-full min-h-screen sm:min-h-0 sm:max-w-[950px] sm:max-h-[620px]
                bg-white sm:rounded-[50px] sm:shadow-xl sm:border sm:border-slate-100
                flex flex-col sm:flex-row sm:p-3">

        {{-- ── Image panel ── --}}
        {{-- Mobile: full-width top | Desktop: left side --}}
       <div class="image-panel
            w-full sm:h-auto
            sm:flex-1 sm:self-stretch
            order-first
            px-4 pt-4 sm:p-0">
    <img src="{{ asset('images/bb3.svg') }}" alt=""
         class="w-full h-56 sm:h-full object-cover
                rounded-2xl sm:rounded-l-[47px]">
</div>

        {{-- ── Form panel ── --}}
        <div class="form-panel
                    flex flex-col
                    px-6 sm:px-8
                    pt-8 sm:pt-10
                    pb-10 sm:pb-8
                    sm:mr-15 sm:ml-10 flex-1 sm:flex-none">

            {{-- Heading --}}
            <div class="mb-6">
                <h1 class="text-2xl sm:text-3xl font-semibold text-slate-900 mb-2">
                    Create Account 👋
                </h1>
                <p class="text-sm text-slate-500 leading-relaxed">
                    Today is a new day. It's your day. You shape it.<br class="hidden sm:block">
                    Sign up to start managing your emails.
                </p>
            </div>

            @if ($errors->any())
                <div class="mb-4 bg-red-50 text-red-600 border border-red-200 text-sm rounded-xl px-4 py-3">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="space-y-3 sm:space-y-4">
                @csrf

                {{-- Full Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">Full Name</label>
                    <input
                        type="text" name="name" id="name" required autocomplete="name"
                        value="{{ old('name') }}"
                        class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all placeholder-slate-400"
                        placeholder="John Doe"
                    >
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email address</label>
                    <input
                        type="email" name="email" id="email" required autocomplete="email"
                        value="{{ old('email') }}"
                        class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all placeholder-slate-400"
                        placeholder="Example@email.com"
                    >
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                    <input
                        type="password" name="password" id="password" required autocomplete="new-password"
                        class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all placeholder-slate-400"
                        placeholder="At least 8 characters"
                    >
                </div>

                {{-- Confirm Password --}}
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">Confirm Password</label>
                    <input
                        type="password" name="password_confirmation" id="password_confirmation" required
                        class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all placeholder-slate-400"
                        placeholder="Confirm your password"
                    >
                </div>

                {{-- Submit --}}
                <button type="submit"
                        class="w-full rounded-xl bg-[#162D3A] px-4 py-3.5 text-sm font-semibold text-white shadow-md hover:bg-slate-800 active:scale-[.98] transition-all">
                    Sign up
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                Already have an account?
                <a href="{{ route('login') }}" id="to-login"
                   class="font-semibold text-[#4f8ef7] hover:underline focus:outline-none">
                    Log in
                </a>
            </p>

        </div>

    </div>

<script>
    document.getElementById('to-login').addEventListener('click', function (e) {
        e.preventDefault();
        const href = this.href;
        document.querySelector('.card').classList.add('exit-to-login');
        setTimeout(() => { window.location.href = href; }, 400);
    });
</script>

</body>
</html>