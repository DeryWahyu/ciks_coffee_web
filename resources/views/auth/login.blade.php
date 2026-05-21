<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Ciks Coffee - Staff Management Portal Login">
    <title>Login - Ciks Coffee</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Custom animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .animate-slide-in-left {
            animation: slideInLeft 0.6s ease-out forwards;
        }

        .animate-delay-1 { animation-delay: 0.1s; opacity: 0; }
        .animate-delay-2 { animation-delay: 0.2s; opacity: 0; }
        .animate-delay-3 { animation-delay: 0.3s; opacity: 0; }
        .animate-delay-4 { animation-delay: 0.4s; opacity: 0; }
        .animate-delay-5 { animation-delay: 0.5s; opacity: 0; }
        .animate-delay-6 { animation-delay: 0.6s; opacity: 0; }

        /* Input focus animation */
        .input-line {
            position: relative;
        }
        .input-line::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: #3E2723;
            transition: width 0.3s ease;
        }
        .input-line:focus-within::after {
            width: 100%;
        }

        /* Button shimmer */
        .btn-shimmer {
            position: relative;
            overflow: hidden;
        }
        .btn-shimmer::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            transition: left 0.5s ease;
        }
        .btn-shimmer:hover::after {
            left: 100%;
        }

        /* Image overlay gradient */
        .image-overlay {
            background: linear-gradient(
                to right,
                rgba(27, 15, 11, 0.3) 0%,
                rgba(27, 15, 11, 0.1) 50%,
                rgba(27, 15, 11, 0.4) 100%
            );
        }
    </style>
</head>
<body class="min-h-screen bg-cream-light font-sans antialiased">
    <div class="flex min-h-screen">
        {{-- Left Side - Coffee Shop Image --}}
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
            <img
                src="{{ asset('images/coffee-shop-bg.png') }}"
                alt="Ciks Coffee Interior"
                class="absolute inset-0 w-full h-full object-cover"
                id="login-bg-image"
            >
            <div class="image-overlay absolute inset-0"></div>

            {{-- Floating brand on image --}}
            <div class="relative z-10 flex flex-col justify-end p-12 pb-16">
                <div class="animate-slide-in-left animate-delay-2">
                    <p class="text-white/70 text-sm font-medium tracking-[0.3em] uppercase mb-3">
                        Est. 2026
                    </p>
                    <h2 class="text-white text-4xl font-bold tracking-tight font-sans">
                        Ciks Coffee
                    </h2>
                    <div class="w-12 h-0.5 bg-caramel mt-4 mb-4"></div>
                    <p class="text-white/60 text-sm max-w-xs leading-relaxed">
                        Menyajikan kopi terbaik dengan cinta dan dedikasi untuk setiap cangkir.
                    </p>
                </div>
            </div>
        </div>

        {{-- Right Side - Login Form --}}
        <div class="w-full lg:w-1/2 flex items-center justify-center px-6 py-12 lg:px-16 xl:px-24">
            <div class="w-full max-w-md">
                {{-- Logo & Brand --}}
                <div class="mb-10 animate-fade-in-up animate-delay-1">
                    <div class="flex items-center gap-3 mb-8">
                        <svg class="w-9 h-9 text-espresso" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M8.5 2c-.3 1 .3 2 0 3" opacity="0.4"/>
                            <path d="M11.5 2c-.3 1 .3 2 0 3" opacity="0.55"/>
                            <path d="M14.5 2c-.3 1 .3 2 0 3" opacity="0.4"/>
                            <path d="M4.5 7h13v5c0 3.3-2.7 6-6 6h-1c-3.3 0-6-2.7-6-6V7z"/>
                            <path d="M17.5 9.5h1a3 3 0 010 6h-1"/>
                            <path d="M3 21h16"/>
                        </svg>
                        <h1 class="text-xl font-extrabold text-espresso tracking-[0.15em] uppercase">
                            Ciks Coffee
                        </h1>
                    </div>

                    <h2 class="text-2xl font-bold text-espresso font-sans">
                        Welcome Back
                    </h2>
                    <p class="text-caramel-dark mt-2 text-sm">
                        Sign in to access the management portal.
                    </p>
                </div>

                {{-- Error Messages --}}
                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl animate-fade-in-up" id="error-alert">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-red-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                @foreach ($errors->all() as $error)
                                    <p class="text-red-700 text-sm">{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Login Form --}}
                <form method="POST" action="{{ route('login') }}" class="space-y-6" id="login-form">
                    @csrf

                    {{-- Email Field --}}
                    <div class="animate-fade-in-up animate-delay-2">
                        <label for="email" class="block text-xs font-semibold text-espresso tracking-[0.15em] uppercase mb-3">
                            Email Address
                        </label>
                        <div class="input-line">
                            <div class="flex items-center gap-3 pb-3 border-b border-latte">
                                <svg class="w-5 h-5 text-caramel shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                </svg>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    placeholder="barista@cikscoffee.com"
                                    class="w-full bg-transparent text-espresso placeholder-caramel-light text-sm focus:outline-none"
                                    required
                                    autofocus
                                    autocomplete="email"
                                >
                            </div>
                        </div>
                    </div>

                    {{-- Password Field --}}
                    <div class="animate-fade-in-up animate-delay-3">
                        <div class="flex items-center justify-between mb-3">
                            <label for="password" class="block text-xs font-semibold text-espresso tracking-[0.15em] uppercase">
                                Password
                            </label>
                            {{-- Forgot Password link (for future implementation) --}}
                            {{-- <a href="#" class="text-xs text-caramel-dark hover:text-espresso transition-colors duration-200">
                                Forgot Password?
                            </a> --}}
                        </div>
                        <div class="input-line">
                            <div class="flex items-center gap-3 pb-3 border-b border-latte">
                                <svg class="w-5 h-5 text-caramel shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                                </svg>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    placeholder="••••••••"
                                    class="w-full bg-transparent text-espresso placeholder-caramel-light text-sm focus:outline-none"
                                    required
                                    autocomplete="current-password"
                                >
                                <button type="button" id="toggle-password" class="text-caramel hover:text-espresso transition-colors duration-200" onclick="togglePassword()">
                                    <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <svg id="eye-off-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Remember Me --}}
                    <div class="flex items-center gap-3 animate-fade-in-up animate-delay-4">
                        <div class="relative">
                            <input
                                type="checkbox"
                                id="remember"
                                name="remember"
                                class="peer sr-only"
                                {{ old('remember') ? 'checked' : '' }}
                            >
                            <label for="remember" class="flex items-center justify-center w-5 h-5 border-2 border-latte rounded cursor-pointer transition-all duration-200 peer-checked:bg-espresso peer-checked:border-espresso">
                                <svg class="w-3 h-3 text-cream opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </label>
                        </div>
                        <label for="remember" class="text-sm text-caramel-dark cursor-pointer select-none">
                            Remember me for 30 days
                        </label>
                    </div>

                    {{-- Submit Button --}}
                    <div class="pt-4 animate-fade-in-up animate-delay-5">
                        <button
                            type="submit"
                            id="login-button"
                            class="btn-shimmer w-full flex items-center justify-center gap-3 bg-espresso hover:bg-espresso-light text-cream py-4 px-6 text-sm font-semibold tracking-[0.2em] uppercase rounded-xl transition-all duration-300 hover:shadow-lg hover:shadow-espresso/20 active:scale-[0.98]"
                        >
                            <span>Sign In</span>
                            <svg class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                            </svg>
                        </button>
                    </div>
                </form>

                {{-- Footer --}}
                <div class="mt-10 pt-6 border-t border-latte animate-fade-in-up animate-delay-6">
                    <p class="text-xs text-caramel text-center">
                        Authorized personnel only.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            const eyeOffIcon = document.getElementById('eye-off-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.add('hidden');
                eyeOffIcon.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('hidden');
                eyeOffIcon.classList.add('hidden');
            }
        }

        // Auto-dismiss error alert after 5 seconds
        const errorAlert = document.getElementById('error-alert');
        if (errorAlert) {
            setTimeout(() => {
                errorAlert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                errorAlert.style.opacity = '0';
                errorAlert.style.transform = 'translateY(-10px)';
                setTimeout(() => errorAlert.remove(), 500);
            }, 5000);
        }
    </script>
</body>
</html>
