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
    <body class="bg-white text-slate-800 antialiased">
        @php
            $appointmentUrl = auth()->check() ? route('dashboard') : route('login');
        @endphp

        <!-- Navigation -->
        <header x-data="{ open: false }" class="sticky top-0 z-50 bg-white/95 shadow-sm backdrop-blur">
            <div class="mx-auto flex h-[88px] max-w-[1320px] items-center px-5 sm:px-8">
                <!-- Brand -->
                <a href="#home" class="flex items-center gap-3">
                    <x-clinic-logo class="h-10 w-10" />
                    <span class="text-[20px] font-bold tracking-tight text-[#0b1f44]">Private Dental Clinic</span>
                </a>

                <div class="ml-auto hidden items-center gap-8 lg:flex">
                    <!-- Desktop links -->
                    <nav class="flex items-center gap-10">
                        <a href="#home" class="text-[16px] font-medium text-slate-700 transition-colors hover:text-[#0fb9a8]">Home</a>
                        <a href="#about" class="text-[16px] font-medium text-slate-700 transition-colors hover:text-[#0fb9a8]">About Us</a>
                        <a href="#services" class="text-[16px] font-medium text-slate-700 transition-colors hover:text-[#0fb9a8]">Services</a>
                        <a href="#contact" class="text-[16px] font-medium text-slate-700 transition-colors hover:text-[#0fb9a8]">Contact</a>
                    </nav>

                    <div>
                        @auth
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="rounded-full bg-[#ef2b2d] px-7 py-3 text-[16px] font-semibold text-white shadow-sm transition-colors hover:bg-[#d92022] focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                    Logout
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="inline-block rounded-full bg-[#ef2b2d] px-7 py-3 text-[16px] font-semibold text-white shadow-sm transition-colors hover:bg-[#d92022] focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                Login
                            </a>
                        @endauth
                    </div>
                </div>

                <!-- Mobile menu button -->
                <button type="button" @click="open = ! open" class="inline-flex items-center justify-center rounded-lg p-2 text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#0fb9a8] lg:hidden" aria-label="Toggle navigation">
                    <svg x-show="! open" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-show="open" x-cloak class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Mobile navigation -->
            <div x-show="open" x-cloak x-transition class="border-t border-slate-100 bg-white px-5 pb-6 pt-2 lg:hidden">
                <nav class="flex flex-col">
                    <a href="#home" @click="open = false" class="rounded-lg px-4 py-3 text-base font-medium text-slate-700 hover:bg-slate-50 hover:text-[#0fb9a8]">Home</a>
                    <a href="#about" @click="open = false" class="rounded-lg px-4 py-3 text-base font-medium text-slate-700 hover:bg-slate-50 hover:text-[#0fb9a8]">About Us</a>
                    <a href="#services" @click="open = false" class="rounded-lg px-4 py-3 text-base font-medium text-slate-700 hover:bg-slate-50 hover:text-[#0fb9a8]">Services</a>
                    <a href="#contact" @click="open = false" class="rounded-lg px-4 py-3 text-base font-medium text-slate-700 hover:bg-slate-50 hover:text-[#0fb9a8]">Contact</a>
                </nav>

                <div class="mt-3 px-4">
                    @auth
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full rounded-full bg-[#ef2b2d] px-7 py-3 text-base font-semibold text-white transition-colors hover:bg-[#d92022]">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="block w-full rounded-full bg-[#ef2b2d] px-7 py-3 text-center text-base font-semibold text-white transition-colors hover:bg-[#d92022]">
                            Login
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <!-- Hero -->
        <section id="home" class="scroll-mt-24 rounded-b-[40px] bg-[#101b34] px-5 pb-[72px] pt-[90px] text-center shadow-[0_24px_40px_-32px_rgba(2,12,27,0.8)] sm:px-8">
            <div class="mx-auto max-w-4xl">
                <x-clinic-logo ring="ring-4 ring-white/90" class="mx-auto h-16 w-16" />

                <h1 class="mt-16 text-[42px] font-bold leading-[1.08] tracking-tight text-white sm:text-[54px] lg:text-[60px]">
                    Private Dental Clinic
                </h1>

                <p class="mx-auto mt-2 max-w-2xl text-[19px] font-light leading-relaxed text-slate-300 sm:text-[24px]">
                    Your smile, our passion. Expert care for a healthier you.
                </p>

                <a href="{{ $appointmentUrl }}" class="mt-6 inline-flex items-center justify-center rounded-full bg-[#0fb9a8] px-8 py-[18px] text-[17px] font-semibold text-white shadow-[0_10px_22px_-12px_rgba(15,185,168,0.65)] transition-all duration-200 hover:-translate-y-0.5 hover:bg-[#0aa596] focus:outline-none focus:ring-2 focus:ring-[#0fb9a8] focus:ring-offset-2 focus:ring-offset-[#101b34] sm:text-[19px]">
                    Book an Appointment
                </a>
            </div>
        </section>

        <!-- Last updated -->
        <p class="px-5 pb-8 pt-12 text-center text-[15px] text-slate-500">
            Last updated: {{ now()->timezone('Asia/Manila')->format('F j, Y \a\t h:i:s A T') }}
        </p>

        <!-- About -->
        <section id="about" class="scroll-mt-24 px-5 pb-20 pt-12 sm:px-8">
            <div class="mx-auto max-w-[800px]">
                <h2 class="text-center text-[36px] font-bold tracking-tight text-[#0b1f44] sm:text-[40px]">
                    About Our Clinic
                </h2>

                <div class="mt-10 space-y-6 text-[17px] leading-[1.85] text-slate-700 sm:text-[18px]">
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
        <section id="services" class="scroll-mt-24 px-5 pb-24 sm:px-8">
            <h2 class="text-center text-[36px] font-bold tracking-tight text-[#0b1f44] sm:text-[40px]">
                Our Dental Services &amp; Prices
            </h2>

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

            <div class="mx-auto mt-12 grid max-w-[1264px] grid-cols-1 gap-7 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($services as $service)
                    <article class="group overflow-hidden rounded-xl bg-white shadow-[0_12px_30px_-16px_rgba(15,35,75,0.28)] ring-1 ring-slate-100 transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_20px_40px_-18px_rgba(15,35,75,0.35)] hover:ring-[#0fb9a8]/30">
                        <div class="overflow-hidden">
                            <img
                                src="{{ asset('images/services/'.$service['image']) }}"
                                alt="{{ $service['name'] }} dental treatment"
                                loading="lazy"
                                class="aspect-[3/2] w-full object-cover transition-transform duration-500 group-hover:scale-[1.04]"
                            >
                        </div>

                        <div class="px-6 pb-6 pt-5 text-center">
                            <h3 class="text-[20px] font-semibold leading-snug text-[#12284d]">
                                {{ $service['name'] }}
                            </h3>

                            <p class="mt-2 text-[15px] leading-6 text-slate-500">
                                {{ $service['description'] }}
                            </p>

                            <p class="mt-4 text-[16px] font-bold text-[#0fb9a8]">
                                Estimated Cost: &#8369;{{ $service['price'] }}
                            </p>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <!-- Footer -->
        <footer id="contact" class="scroll-mt-24 bg-[#101b34] text-slate-300">
            <div class="mx-auto grid max-w-[1320px] gap-10 px-5 py-14 sm:px-8 md:grid-cols-2 lg:grid-cols-[1.5fr_1.3fr_1fr]">
                <div>
                    <h3 class="text-[20px] font-semibold text-white">Private Dental Clinic</h3>
                    <p class="mt-4 max-w-sm text-[15px] leading-7 text-slate-300">
                        Dedicated to providing exceptional dental care. Your healthy, confident smile is our priority.
                    </p>
                </div>

                <div>
                    <h3 class="text-[20px] font-semibold text-white">Contact Us</h3>
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
                    <h3 class="text-[20px] font-semibold text-white">Quick Links</h3>
                    <ul class="mt-4 space-y-2 text-[15px] leading-7">
                        <li><a href="#about" class="transition-colors hover:text-white">About Us</a></li>
                        <li><a href="#services" class="transition-colors hover:text-white">Our Services</a></li>
                        <li><a href="{{ $appointmentUrl }}" class="transition-colors hover:text-white">Book Appointment</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-white/10 py-6 text-center text-[14px] text-slate-400">
                &copy; {{ date('Y') }} Private Dental Clinic. All rights reserved.
            </div>
        </footer>
    </body>
</html>
