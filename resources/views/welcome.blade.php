<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Private Dental Clinic — your trusted partner in achieving excellent oral health and confident smiles.">

        <title>Private Dental Clinic</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            html {
                scroll-behavior: smooth;
            }

            body {
                font-family: 'Inter', 'Figtree', ui-sans-serif, system-ui, sans-serif;
            }

            [x-cloak] {
                display: none !important;
            }
        </style>
    </head>
    <body class="bg-clinic-50 font-sans text-slate-800 antialiased">
        @php
            $appointmentUrl = auth()->check() ? route('dashboard') : route('login');
        @endphp

        <!-- Navigation -->
        <header x-data="{ open: false }" class="sticky top-0 z-50 border-b border-clinic-950/5 bg-white/80 shadow-[0_10px_30px_-20px_rgba(10,47,37,0.35)] backdrop-blur-xl">
            <div class="mx-auto flex h-[76px] max-w-[1320px] items-center px-5 sm:px-8">
                <!-- Brand -->
                <a href="#home" class="flex items-center gap-3">
                    <x-clinic-logo class="h-10 w-10" />
                    <span class="text-[20px] font-bold tracking-tight text-clinic-950">Private Dental Clinic</span>
                </a>

                <div class="ml-auto hidden items-center gap-2 lg:flex">
                    <!-- Desktop links -->
                    <nav class="flex items-center gap-1">
                        <a href="#home" class="rounded-full px-4 py-2 text-[15px] font-medium text-slate-600 transition-colors hover:bg-clinic-100/70 hover:text-clinic-800">Home</a>
                        <a href="#about" class="rounded-full px-4 py-2 text-[15px] font-medium text-slate-600 transition-colors hover:bg-clinic-100/70 hover:text-clinic-800">About Us</a>
                        <a href="#services" class="rounded-full px-4 py-2 text-[15px] font-medium text-slate-600 transition-colors hover:bg-clinic-100/70 hover:text-clinic-800">Services</a>
                        <a href="#contact" class="rounded-full px-4 py-2 text-[15px] font-medium text-slate-600 transition-colors hover:bg-clinic-100/70 hover:text-clinic-800">Contact</a>
                    </nav>

                    <div class="ms-3">
                        @auth
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="rounded-full bg-clinic-600 px-6 py-2.5 text-[15px] font-semibold text-white shadow-[0_12px_25px_-12px_rgba(26,148,107,0.8)] transition-all hover:-translate-y-0.5 hover:bg-clinic-700 focus:outline-none focus:ring-2 focus:ring-clinic-500 focus:ring-offset-2">
                                    Logout
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="inline-block rounded-full bg-clinic-600 px-6 py-2.5 text-[15px] font-semibold text-white shadow-[0_12px_25px_-12px_rgba(26,148,107,0.8)] transition-all hover:-translate-y-0.5 hover:bg-clinic-700 focus:outline-none focus:ring-2 focus:ring-clinic-500 focus:ring-offset-2">
                                Login
                            </a>
                        @endauth
                    </div>
                </div>

                <!-- Mobile menu button -->
                <button type="button" @click="open = ! open" class="ms-auto inline-flex items-center justify-center rounded-xl p-2 text-clinic-800 transition-colors hover:bg-clinic-100 focus:outline-none focus:ring-2 focus:ring-clinic-500 lg:hidden" aria-label="Toggle navigation">
                    <svg x-show="! open" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-show="open" x-cloak class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Mobile navigation -->
            <div x-show="open" x-cloak x-transition class="border-t border-clinic-950/5 bg-white px-5 pb-6 pt-2 lg:hidden">
                <nav class="flex flex-col">
                    <a href="#home" @click="open = false" class="rounded-xl px-4 py-3 text-base font-medium text-slate-700 transition-colors hover:bg-clinic-50 hover:text-clinic-700">Home</a>
                    <a href="#about" @click="open = false" class="rounded-xl px-4 py-3 text-base font-medium text-slate-700 transition-colors hover:bg-clinic-50 hover:text-clinic-700">About Us</a>
                    <a href="#services" @click="open = false" class="rounded-xl px-4 py-3 text-base font-medium text-slate-700 transition-colors hover:bg-clinic-50 hover:text-clinic-700">Services</a>
                    <a href="#contact" @click="open = false" class="rounded-xl px-4 py-3 text-base font-medium text-slate-700 transition-colors hover:bg-clinic-50 hover:text-clinic-700">Contact</a>
                </nav>

                <div class="mt-3 px-4">
                    @auth
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full rounded-full bg-clinic-600 px-7 py-3 text-base font-semibold text-white transition-colors hover:bg-clinic-700">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="block w-full rounded-full bg-clinic-600 px-7 py-3 text-center text-base font-semibold text-white transition-colors hover:bg-clinic-700">
                            Login
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <!-- Hero -->
        <section id="home" class="relative scroll-mt-24 overflow-hidden rounded-b-[48px] bg-gradient-to-b from-white via-clinic-50 to-clinic-100 pb-24 pt-20 sm:pb-28">
            <!-- Decorative background -->
            <div class="pointer-events-none absolute -left-24 top-10 h-[420px] w-[420px] rounded-full bg-emerald-400/25 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-24 -top-10 h-[380px] w-[380px] rounded-full bg-teal-400/20 blur-3xl"></div>
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(#0a2f25_1px,transparent_1px)] [background-size:26px_26px] opacity-[0.05]"></div>

            <div class="relative mx-auto max-w-4xl px-5 text-center sm:px-8">
                <x-clinic-logo ring="ring-[6px] ring-white/90" class="mx-auto h-20 w-20 shadow-[0_25px_50px_-20px_rgba(16,146,106,0.55)]" />

                <h1 class="mt-8 text-[42px] font-extrabold leading-[1.06] tracking-tight text-clinic-950 sm:text-[54px] lg:text-[62px]">
                    Private
                    <span class="bg-gradient-to-r from-emerald-600 to-teal-500 bg-clip-text text-transparent">Dental Clinic</span>
                </h1>

                <p class="mx-auto mt-5 max-w-2xl text-[19px] font-light leading-relaxed text-slate-600 sm:text-[23px]">
                    Your smile, our passion. Expert care for a healthier you.
                </p>

                <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <a href="{{ $appointmentUrl }}" class="inline-flex w-full items-center justify-center rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 px-8 py-[18px] text-[17px] font-semibold text-white shadow-[0_20px_38px_-16px_rgba(16,146,106,0.65)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_24px_44px_-16px_rgba(16,146,106,0.75)] focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 sm:w-auto sm:text-[19px]">
                        Book an Appointment
                    </a>
                    <a href="#services" class="inline-flex w-full items-center justify-center rounded-full bg-white/80 px-8 py-[18px] text-[17px] font-semibold text-clinic-800 ring-1 ring-clinic-600/20 backdrop-blur transition-all duration-200 hover:-translate-y-0.5 hover:bg-white hover:ring-clinic-600/40 focus:outline-none focus:ring-2 focus:ring-clinic-500 focus:ring-offset-2 sm:w-auto sm:text-[19px]">
                        View Our Services
                    </a>
                </div>

                <ul class="mt-10 flex flex-wrap items-center justify-center gap-x-8 gap-y-3 text-[15px] font-medium text-clinic-950/70">
                    <li class="flex items-center gap-2">
                        <svg viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 shrink-0 text-emerald-600"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
                        Expert dentists
                    </li>
                    <li class="flex items-center gap-2">
                        <svg viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 shrink-0 text-emerald-600"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
                        Affordable prices
                    </li>
                    <li class="flex items-center gap-2">
                        <svg viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 shrink-0 text-emerald-600"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
                        Easy booking
                    </li>
                </ul>
            </div>
        </section>

        <!-- Last updated -->
        <p class="px-5 pb-10 pt-12 text-center text-[15px] text-clinic-950/50">
            Last updated: {{ now()->timezone('Asia/Manila')->format('F j, Y \a\t h:i:s A T') }}
        </p>

        <!-- About -->
        <section id="about" class="scroll-mt-24 px-5 pb-20 sm:px-8">
            <div class="mx-auto max-w-[820px]">
                <span class="mx-auto block w-fit rounded-full bg-white px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.22em] text-clinic-700 shadow-sm ring-1 ring-clinic-600/15">
                    About Us
                </span>

                <h2 class="mt-5 text-center text-[34px] font-bold tracking-tight text-clinic-950 sm:text-[40px]">
                    About Our Clinic
                </h2>

                <div class="mt-9 space-y-6 text-[17px] leading-[1.85] text-slate-600 sm:text-[18px]">
                    <p>
                        Welcome to Private Dental Clinic &ndash; your trusted partner in achieving excellent oral health
                        and confident smiles. We are dedicated to providing comprehensive, high-quality dental
                        care in a safe, comfortable, and friendly environment.
                    </p>

                    <p>
                        Our team of highly skilled and compassionate dental professionals combines expertise
                        with the latest technology to deliver personalized treatments that address your unique
                        needs. Whether you require routine check-ups, preventive care, cosmetic enhancements,
                        or advanced restorative procedures, we are committed to ensuring your experience is
                        both effective and stress-free.
                    </p>

                    <p>
                        At Private Dental Clinic, we believe that a healthy smile is the foundation of overall well-being.
                        Beyond treatment, we focus on patient education and preventive care, empowering
                        you to maintain your oral health for years to come.
                    </p>

                    <p>
                        With our patient-centered approach, modern facilities, and unwavering commitment to
                        excellence, we strive to make every visit a positive step toward a brighter, healthier smile.
                    </p>
                </div>
            </div>
        </section>

        <!-- Services -->
        <section id="services" class="scroll-mt-24 px-5 pb-20 sm:px-8">
            @php
                $services = [
                    [
                        'name' => 'Teeth Whitening',
                        'description' => 'Brighten your smile with our professional teeth whitening services.',
                        'price' => 300,
                        'image' => 'teeth-whitening.jpg',
                    ],
                    [
                        'name' => 'Teeth Cleanings',
                        'description' => 'Keep your teeth healthy and clean with our thorough dental cleanings.',
                        'price' => 100,
                        'image' => 'teeth-cleanings.jpg',
                    ],
                    [
                        'name' => 'Dental Braces',
                        'description' => "Get the smile you've always wanted with our orthodontic treatments.",
                        'price' => 5000,
                        'image' => 'dental-braces.jpg',
                    ],
                    [
                        'name' => 'Composite Filling',
                        'description' => 'Durable and aesthetic fillings to restore your teeth.',
                        'price' => 1500,
                        'image' => 'composite-filling.jpg',
                    ],
                    [
                        'name' => 'Tooth Extractions',
                        'description' => 'Safe and efficient extraction of problematic teeth.',
                        'price' => 150,
                        'image' => 'tooth-extractions.jpg',
                    ],
                    [
                        'name' => 'Amalgam Filling',
                        'description' => 'Strong and reliable fillings made from a blend of metals.',
                        'price' => 150,
                        'image' => 'amalgam-filling.jpg',
                    ],
                    [
                        'name' => 'Gold Crown',
                        'description' => 'Premium crowns that offer durability and aesthetics.',
                        'price' => 1200,
                        'image' => 'gold-crown.jpg',
                    ],
                    [
                        'name' => 'Jacket Crown',
                        'description' => 'Natural-looking crowns for a seamless smile.',
                        'price' => 1000,
                        'image' => 'jacket-crown.jpg',
                    ],
                    [
                        'name' => 'Root Canal Therapy',
                        'description' => 'Save your tooth with our effective root canal treatments.',
                        'price' => 800,
                        'image' => 'root-canal-therapy.jpg',
                    ],
                    [
                        'name' => 'Impacted Tooth',
                        'description' => 'Expert extraction, management of impacted teeth.',
                        'price' => 400,
                        'image' => 'impacted-tooth.jpg',
                    ],
                    [
                        'name' => 'Erupted Tooth',
                        'description' => 'Care for teeth that have fully emerged for optimal health.',
                        'price' => 200,
                        'image' => 'erupted-tooth.jpg',
                    ],
                    [
                        'name' => 'Veneer Crown',
                        'description' => 'Achieve a beautiful smile with our custom veneer crowns.',
                        'price' => 10000,
                        'image' => 'veneer-crown.jpg',
                    ],
                ];
            @endphp

            <div class="mx-auto max-w-[1320px]">
                <span class="mx-auto block w-fit rounded-full bg-white px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.22em] text-clinic-700 shadow-sm ring-1 ring-clinic-600/15">
                    Our Services
                </span>

                <h2 class="mt-5 text-center text-[34px] font-bold tracking-tight text-clinic-950 sm:text-[40px]">
                    Our Dental Services &amp; Prices
                </h2>

                <div class="mt-12 grid grid-cols-1 gap-7 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($services as $service)
                        <article class="group flex flex-col overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-[0_30px_60px_-28px_rgba(10,47,37,0.5)] hover:ring-emerald-500/30">
                            <div class="relative overflow-hidden">
                                <img
                                    src="{{ asset('images/services/'.$service['image']) }}"
                                    alt="{{ $service['name'] }} dental treatment"
                                    loading="lazy"
                                    class="aspect-[3/2] w-full object-cover transition-transform duration-500 group-hover:scale-[1.05]"
                                >
                                <span class="absolute left-4 top-4 rounded-full bg-white/85 px-3 py-1 text-xs font-bold text-clinic-700 shadow-sm backdrop-blur">
                                    &#8369;{{ $service['price'] }}
                                </span>
                            </div>

                            <div class="flex flex-1 flex-col px-6 pb-6 pt-5 text-center">
                                <h3 class="text-[20px] font-semibold leading-snug text-clinic-950">
                                    {{ $service['name'] }}
                                </h3>

                                <p class="mt-2 text-[15px] leading-6 text-slate-500">
                                    {{ $service['description'] }}
                                </p>

                                <p class="mt-auto pt-5">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-clinic-50 px-3.5 py-1.5 text-sm font-bold text-clinic-700 ring-1 ring-clinic-600/15">
                                        Estimated Cost: &#8369;{{ $service['price'] }}
                                    </span>
                                </p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- CTA banner -->
        <section class="px-5 pb-24 sm:px-8">
            <div class="relative mx-auto max-w-[1320px] overflow-hidden rounded-[36px] bg-gradient-to-br from-emerald-600 via-emerald-600 to-teal-600 px-6 py-16 text-center shadow-[0_35px_70px_-35px_rgba(16,146,106,0.75)] sm:px-12">
                <div class="pointer-events-none absolute -right-16 -top-24 h-72 w-72 rounded-full bg-white/10"></div>
                <div class="pointer-events-none absolute -bottom-28 -left-16 h-80 w-80 rounded-full bg-white/10"></div>

                <div class="relative">
                    <h2 class="text-[30px] font-bold tracking-tight text-white sm:text-[36px]">
                        Ready for a brighter smile?
                    </h2>
                    <p class="mx-auto mt-3 max-w-xl text-[16px] leading-7 text-emerald-50/85 sm:text-[17px]">
                        Book your appointment today and let our dental team take care of the rest.
                    </p>

                    <a href="{{ $appointmentUrl }}" class="mt-8 inline-flex items-center justify-center rounded-full bg-white px-8 py-4 text-[17px] font-semibold text-clinic-800 shadow-lg transition-all duration-200 hover:-translate-y-0.5 hover:bg-clinic-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-emerald-600 sm:text-[18px]">
                        Book an Appointment
                    </a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer id="contact" class="scroll-mt-24 bg-clinic-950 text-emerald-50/75">
            <div class="mx-auto grid max-w-[1320px] gap-10 px-5 py-16 sm:px-8 md:grid-cols-2 lg:grid-cols-[1.5fr_1.3fr_1fr]">
                <div>
                    <a href="#home" class="flex items-center gap-3">
                        <x-clinic-logo ring="ring-4 ring-white/10" class="h-11 w-11" />
                        <span class="text-[20px] font-bold tracking-tight text-white">Private Dental Clinic</span>
                    </a>
                    <p class="mt-5 max-w-sm text-[15px] leading-7">
                        Dedicated to providing exceptional dental care. Your healthy, confident smile is our priority.
                    </p>
                </div>

                <div>
                    <h3 class="text-[19px] font-semibold text-white">Contact Us</h3>
                    <ul class="mt-4 space-y-2 text-[15px] leading-7">
                        <li>
                            <a href="tel:+639171234567" class="transition-colors hover:text-white">Phone: (0917) 123-4567</a>
                        </li>
                        <li>
                            <a href="mailto:private@email.com" class="transition-colors hover:text-white">Email: private@email.com</a>
                        </li>
                        <li>123 Dental Lane, Panabo City</li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-[19px] font-semibold text-white">Quick Links</h3>
                    <ul class="mt-4 space-y-2 text-[15px] leading-7">
                        <li><a href="#about" class="transition-colors hover:text-white">About Us</a></li>
                        <li><a href="#services" class="transition-colors hover:text-white">Our Services</a></li>
                        <li><a href="{{ $appointmentUrl }}" class="transition-colors hover:text-white">Book Appointment</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-white/10 py-6 text-center text-[14px] text-emerald-100/50">
                &copy; {{ date('Y') }} Private Dental Clinic. All rights reserved.
            </div>
        </footer>
    </body>
</html>
