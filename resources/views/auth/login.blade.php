<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Masuk ke portal pengelolaan Ciks Coffee untuk pemilik dan karyawan.">
    <title>Masuk - Ciks Coffee</title>
    <x-brand-icons />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('components.user-messages')
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
                rgba(27, 15, 11, 0.6) 0%,
                rgba(27, 15, 11, 0.45) 50%,
                rgba(27, 15, 11, 0.7) 100%
            );
        }
    </style>
</head>
<body class="min-h-screen bg-white font-sans antialiased">
    <div class="flex min-h-screen">
        {{-- Left Side - Coffee Shop Image --}}
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
            <img
                src="{{ asset('images/CiksCoffee.jpeg') }}"
                alt="Interior Ciks Coffee"
                class="absolute inset-0 w-full h-full object-cover"
                id="login-bg-image"
            >
            <div class="image-overlay absolute inset-0"></div>

            {{-- Floating brand on image --}}
            <div class="relative z-10 flex flex-col justify-end p-12 pb-16">
                <div class="animate-slide-in-left animate-delay-2">
                    <p class="text-white/70 text-sm font-medium tracking-[0.3em] uppercase mb-3">
                        Sejak 2025
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
        <div class="w-full lg:w-1/2 flex items-center justify-center bg-white px-6 py-12 sm:px-10 xl:px-14">
            <div class="w-full max-w-[34rem]">
                {{-- Logo & Brand --}}
                <div class="mb-9 text-center animate-fade-in-up animate-delay-1">
                    <div class="flex items-center justify-center mb-6">
                        <x-brand-logo :wordmark="true" class="w-36 h-16" />
                        <h1 class="sr-only">
                            Ciks Coffee
                        </h1>
                    </div>

                    <h2 class="text-2xl sm:text-3xl font-bold text-espresso font-sans tracking-tight leading-tight">
                        Selamat datang kembali.
                    </h2>
                    <p class="text-caramel-dark mt-3 text-sm sm:text-base leading-relaxed">
                        Masuk untuk mengakses portal pengelolaan Ciks Coffee.
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
                <form method="POST" action="{{ route('login') }}" class="space-y-5" id="login-form">
                    @csrf

                    {{-- Email Field --}}
                    <div class="animate-fade-in-up animate-delay-2">
                        <label for="email" class="block text-sm font-medium text-espresso mb-2">
                            Email<span class="text-red-500 ml-0.5" aria-hidden="true">*</span>
                        </label>
                        <div class="rounded-2xl border border-latte bg-white transition-all duration-200 focus-within:border-espresso focus-within:ring-4 focus-within:ring-espresso/5">
                            <div class="flex min-h-14 items-center gap-3 px-4">
                                <svg class="w-5 h-5 text-caramel shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                </svg>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    placeholder="nama@email.com"
                                    class="min-w-0 w-full bg-transparent py-4 text-espresso placeholder-caramel-light text-base focus:outline-none"
                                    required
                                    autocomplete="email"
                                    autocapitalize="none"
                                    spellcheck="false"
                                >
                            </div>
                        </div>
                    </div>

                    {{-- Password Field --}}
                    <div class="animate-fade-in-up animate-delay-3">
                        <div class="mb-2">
                            <label for="password" class="block text-sm font-medium text-espresso">
                                Password<span class="text-red-500 ml-0.5" aria-hidden="true">*</span>
                            </label>
                        </div>
                        <div class="rounded-2xl border border-latte bg-white transition-all duration-200 focus-within:border-espresso focus-within:ring-4 focus-within:ring-espresso/5">
                            <div class="flex min-h-14 items-center gap-3 pl-4 pr-1">
                                <svg class="w-5 h-5 text-caramel shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                                </svg>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    placeholder="Masukkan password"
                                    class="min-w-0 w-full bg-transparent py-4 text-espresso placeholder-caramel-light text-base focus:outline-none"
                                    required
                                    autocomplete="current-password"
                                >
                                <button type="button" id="toggle-password" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-caramel hover:text-espresso transition-colors duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-espresso" aria-label="Tampilkan password" aria-controls="password" aria-pressed="false" onclick="togglePassword()">
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
                    <div class="flex items-center gap-2.5 animate-fade-in-up animate-delay-4">
                        <input
                            type="checkbox"
                            id="remember"
                            name="remember"
                            class="h-4 w-4 rounded border-latte accent-espresso cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-espresso"
                            {{ old('remember') ? 'checked' : '' }}
                        >
                        <label for="remember" class="text-sm text-caramel-dark cursor-pointer select-none">
                            Ingat saya
                        </label>
                    </div>

                    {{-- Submit Button --}}
                    <div class="pt-2 animate-fade-in-up animate-delay-5">
                        <button
                            type="submit"
                            id="login-button"
                            class="btn-shimmer w-full flex min-h-14 items-center justify-center bg-espresso hover:bg-espresso-light text-white py-3.5 px-6 text-base font-semibold rounded-full shadow-lg shadow-espresso/10 transition-all duration-200 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-espresso active:scale-[0.98]"
                        >
                            <span>Masuk</span>
                        </button>
                    </div>
                </form>

                {{-- Footer --}}
                <div class="mt-8 animate-fade-in-up animate-delay-6">
                    <p class="text-sm text-caramel-dark text-center">
                        Khusus pemilik dan karyawan.
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
            const toggle = document.getElementById('toggle-password');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.add('hidden');
                eyeOffIcon.classList.remove('hidden');
                toggle.setAttribute('aria-label', 'Sembunyikan password');
                toggle.setAttribute('aria-pressed', 'true');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('hidden');
                eyeOffIcon.classList.add('hidden');
                toggle.setAttribute('aria-label', 'Tampilkan password');
                toggle.setAttribute('aria-pressed', 'false');
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
