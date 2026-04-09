<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'STT') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <style>
        * {
            box-sizing: border-box;
        }

        [x-cloak] {
            display: none !important;
        }

        :root {
            color-scheme: dark;
            --page-bg:
                radial-gradient(circle at top, rgba(255, 255, 255, 0.12), transparent 35%),
                linear-gradient(135deg, #141414, #050505 55%, #1f1f1f);
            --gold-1: rgb(66, 45, 6);
            --gold-2: rgb(128, 92, 16);
            --gold-3: rgb(196, 171, 112);
            --gold-4: rgb(150, 111, 24);
            --gold-5: rgb(71, 49, 7);
            --glass-bg: rgba(6, 6, 6, 0.52);
        }

        body {
            position: relative;
            margin: 0;
            min-height: 100vh;
            padding: 32px;
            overflow-x: hidden;
            overflow-y: auto;
            isolation: isolate;
            background: var(--page-bg);
            color: #fff;
            font-family: "Figtree", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        body::before,
        body::after {
            content: "";
            position: fixed;
            top: 0;
            width: 45px;
            height: 100vh;
            pointer-events: none;
            z-index: 0;
            background: linear-gradient(
                90deg,
                var(--gold-1) 0%,
                var(--gold-2) 16%,
                var(--gold-3) 50%,
                var(--gold-4) 72%,
                var(--gold-5) 100%
            );
            box-shadow:
                inset 1px 0 0 rgba(255, 244, 204, 0.18),
                inset -1px 0 0 rgba(120, 76, 8, 0.16),
                0 0 24px rgba(214, 173, 62, 0.12);
        }

        body::before {
            left: calc(50% - 95px);
        }

        body::after {
            left: calc(50% + 50px);
        }

        .gold-ring {
            position: fixed;
            width: 220px;
            height: 220px;
            pointer-events: none;
            z-index: 0;
            border-radius: 50%;
            background:
                linear-gradient(
                    140deg,
                    rgb(68, 47, 6) 0%,
                    rgb(118, 83, 14) 18%,
                    rgb(205, 183, 128) 36%,
                    rgb(166, 131, 46) 50%,
                    rgb(126, 88, 14) 68%,
                    rgb(64, 44, 6) 100%
                ),
                radial-gradient(
                    circle at 32% 28%,
                    rgb(213, 194, 143) 0%,
                    rgb(213, 194, 143) 18%,
                    transparent 40%
                ),
                radial-gradient(circle at 72% 74%, rgb(84, 56, 8) 0%, transparent 42%);
            box-shadow:
                inset 3px 3px 6px rgba(255, 248, 223, 0.24),
                inset -5px -6px 10px rgba(116, 74, 8, 0.18),
                inset 10px 0 16px rgba(255, 226, 140, 0.1),
                0 0 30px rgba(214, 173, 62, 0.12),
                0 18px 28px rgba(0, 0, 0, 0.08);
            -webkit-mask: radial-gradient(
                farthest-side,
                transparent calc(100% - 29px),
                #000 calc(100% - 28px)
            );
            mask: radial-gradient(
                farthest-side,
                transparent calc(100% - 29px),
                #000 calc(100% - 28px)
            );
            filter: saturate(1.08);
        }

        .ring-enter-left,
        .ring-enter-right {
            opacity: 0;
            transition:
                transform 1s ease,
                opacity 1s ease,
                filter 1s ease;
        }

        .ring-enter-left {
            transform: translateX(-140vw);
        }

        .ring-enter-right {
            transform: translateX(140vw);
        }

        .gold-ring.is-visible {
            opacity: 1;
            transform: translateX(0);
        }

        .ring-right {
            top: 100px;
            right: -110px;
        }

        .ring-left {
            top: 300px;
            left: -66px;
        }

        .ring-top-left-large {
            width: 400px;
            height: 400px;
            top: -132px;
            left: -140px;
            filter: saturate(1.08) blur(3px);
        }

        .ring-top-left-small {
            width: 240px;
            height: 240px;
            top: 74px;
            left: 70px;
            filter: saturate(1.08) blur(2px);
        }

        .scene-decor {
            position: fixed;
            bottom: -20px;
            width: 320px;
            height: 320px;
            pointer-events: none;
            z-index: 1;
            opacity: 0;
            filter: drop-shadow(0 14px 24px rgba(130, 190, 255, 0.12));
            transition:
                transform 1s ease,
                opacity 1s ease,
                filter 1s ease;
        }

        .decor-left {
            left: -24px;
            transform: translate(-400px, 400px);
        }

        .decor-right {
            right: -24px;
            transform: translate(400px, 400px);
        }

        .scene-decor.is-visible {
            opacity: 1;
            transform: translate(0, 0) rotate(0deg);
        }

        .shape {
            position: absolute;
            display: block;
            transform: rotate(-360deg);
        }

        .scene-decor.is-visible .shape {
            animation: shape-spin-in 1s ease forwards;
        }

        .cube {
            border-radius: 0;
            box-shadow:
                0 14px 24px rgba(0, 0, 0, 0.18),
                10px 10px 0 rgba(170, 216, 255, 0.06);
        }

        .cube.solid {
            background: linear-gradient(
                135deg,
                rgba(255, 255, 255, 0.2),
                rgba(255, 255, 255, 0.04) 36%,
                rgba(150, 210, 255, 0.14) 100%
            );
            border: 1px solid rgba(255, 255, 255, 0.22);
            box-shadow:
                inset 1px 1px 0 rgba(255, 255, 255, 0.28),
                inset -10px -10px 18px rgba(90, 150, 210, 0.12),
                inset 10px 10px 18px rgba(255, 255, 255, 0.04),
                0 14px 24px rgba(0, 0, 0, 0.18),
                10px 10px 0 rgba(170, 216, 255, 0.06);
        }

        .cube.outline {
            background:
                linear-gradient(
                        135deg,
                        rgba(255, 255, 255, 0.08),
                        rgba(160, 210, 255, 0.03) 60%,
                        rgba(120, 180, 235, 0.08)
                    ) center / 100% 100% no-repeat,
                linear-gradient(rgba(228, 244, 255, 0.24), rgba(228, 244, 255, 0.24)) top left / 22px 2px no-repeat,
                linear-gradient(rgba(228, 244, 255, 0.24), rgba(228, 244, 255, 0.24)) top left / 2px 22px no-repeat,
                linear-gradient(rgba(228, 244, 255, 0.24), rgba(228, 244, 255, 0.24)) top right / 22px 2px no-repeat,
                linear-gradient(rgba(228, 244, 255, 0.24), rgba(228, 244, 255, 0.24)) top right / 2px 22px no-repeat,
                linear-gradient(rgba(228, 244, 255, 0.24), rgba(228, 244, 255, 0.24)) bottom left / 22px 2px no-repeat,
                linear-gradient(rgba(228, 244, 255, 0.24), rgba(228, 244, 255, 0.24)) bottom left / 2px 22px no-repeat,
                linear-gradient(rgba(228, 244, 255, 0.24), rgba(228, 244, 255, 0.24)) bottom right / 22px 2px no-repeat,
                linear-gradient(rgba(228, 244, 255, 0.24), rgba(228, 244, 255, 0.24)) bottom right / 2px 22px no-repeat;
            border: 1px solid rgba(205, 232, 255, 0.08);
            background-color: rgba(198, 229, 255, 0.04);
            box-shadow:
                inset 1px 1px 0 rgba(255, 255, 255, 0.2),
                inset -8px -8px 16px rgba(110, 165, 220, 0.08),
                0 14px 24px rgba(0, 0, 0, 0.16),
                10px 10px 0 rgba(170, 216, 255, 0.04);
        }

        .triangle {
            width: 0;
            height: 0;
            filter: drop-shadow(0 10px 16px rgba(0, 0, 0, 0.18));
        }

        .triangle.white {
            border-left: 16px solid transparent;
            border-right: 16px solid transparent;
            border-bottom: 28px solid rgba(255, 255, 255, 0.16);
        }

        .triangle.blue {
            border-left: 18px solid transparent;
            border-right: 18px solid transparent;
            border-bottom: 34px solid rgba(178, 224, 255, 0.14);
        }

        .page-heading {
            position: relative;
            top: auto;
            left: auto;
            margin: 0;
            display: flex;
            align-items: flex-end;
            gap: 0.18em;
            font-size: clamp(1.95rem, 3.6vw, 3.05rem);
            font-weight: 900;
            line-height: 0.95;
            letter-spacing: 0.01em;
            z-index: 3;
            opacity: 0;
            transform: translateX(-130%);
            transition:
                transform 2s ease,
                opacity 2s ease;
        }

        .page-heading.is-visible {
            opacity: 1;
            transform: translateX(0);
        }

        .page-heading .welcome {
            color: #fff;
            text-shadow:
                0 0 36px rgba(214, 173, 62, 0.32),
                0 8px 22px rgba(92, 59, 7, 0.38);
        }

        .page-heading .to {
            color: rgb(214, 182, 88);
            text-shadow:
                0 0 40px rgba(232, 196, 92, 0.56),
                0 8px 22px rgba(92, 59, 7, 0.44);
        }

        .content-stage {
            position: relative;
            z-index: 2;
            padding-top: 18px;
        }

        .intro-row {
            width: min(100%, 920px);
            margin: 0 auto 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 22px;
            flex-wrap: wrap;
        }

        .logo-shell {
            opacity: 0;
            transform: translateY(calc(-100vh - 220px));
            transition:
                transform 2s ease,
                opacity 2s ease;
        }

        .logo-shell.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .theme-card {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(166, 131, 46, 0.48);
            background:
                linear-gradient(160deg, rgba(255, 255, 255, 0.07), rgba(255, 255, 255, 0.01) 35%),
                var(--glass-bg);
            color: inherit;
            box-shadow:
                0 18px 34px rgba(0, 0, 0, 0.34),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .theme-card::before {
            content: "";
            position: absolute;
            top: -150%;
            left: 0;
            width: 42px;
            height: 600%;
            background: linear-gradient(
                180deg,
                rgba(255, 255, 255, 0.04) 0%,
                rgba(255, 255, 255, 0.18) 18%,
                rgba(220, 242, 255, 0.13) 52%,
                rgba(162, 214, 255, 0.05) 100%
            );
            transform: translate(calc(100% + 240px), -46%) rotate(34deg);
            box-shadow:
                inset 1px 0 0 rgba(255, 255, 255, 0.16),
                inset -1px 0 0 rgba(170, 219, 255, 0.08),
                0 0 18px rgba(173, 217, 255, 0.08);
            pointer-events: none;
            will-change: transform;
        }

        body.reflections-active .theme-card::before {
            animation: reflection-sweep 4s linear infinite;
        }

        .theme-card > * {
            position: relative;
            z-index: 1;
        }

        .theme-logo {
            width: 12.6rem;
            height: 12.6rem;
            border: 4px solid rgb(228, 200, 104);
            background: linear-gradient(135deg, #fefce8 0%, #fffbeb 48%, #fef9c3 100%);
            box-shadow:
                0 14px 26px rgba(0, 0, 0, 0.28),
                inset 0 1px 0 rgba(255, 255, 255, 0.12);
        }

        .welcome-title {
            color: #fff;
            font-size: clamp(1.55rem, 2.3vw, 1.7rem);
            text-shadow:
                0 0 24px rgba(214, 173, 62, 0.16),
                0 10px 24px rgba(0, 0, 0, 0.34);
        }

        .welcome-copy {
            font-size: 0.92rem;
            color: rgba(255, 255, 255, 0.84);
            text-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .cards-grid {
            width: min(100%, 50rem);
            gap: 1rem;
        }

        .nav-heading {
            font-size: 0.96rem;
            line-height: 1.25;
            color: #fff;
        }

        .nav-copy {
            font-size: 0.76rem;
            line-height: 1.55;
            color: rgba(255, 255, 255, 0.68);
        }

        .theme-nav-card {
            padding: 1.05rem;
            transition:
                transform 0.2s ease,
                background 0.2s ease,
                box-shadow 0.2s ease,
                border-color 0.2s ease;
        }

        .theme-nav-card:hover {
            transform: translateY(-2px);
            border-color: rgba(214, 182, 88, 0.82);
            background:
                linear-gradient(160deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.02) 35%),
                rgba(10, 10, 10, 0.58);
            box-shadow:
                0 22px 36px rgba(0, 0, 0, 0.38),
                inset 0 1px 0 rgba(255, 255, 255, 0.12);
        }

        .theme-login {
            border-color: rgba(214, 182, 88, 0.72);
            background:
                linear-gradient(135deg, rgba(214, 182, 88, 0.22), rgba(96, 68, 12, 0.22)),
                rgba(8, 8, 8, 0.5);
            color: #fff;
            box-shadow:
                0 18px 30px rgba(0, 0, 0, 0.32),
                inset 0 1px 0 rgba(255, 255, 255, 0.16);
            position: fixed;
            top: 20px;
            right: 24px;
            z-index: 5;
            margin: 0;
        }

        .theme-login:hover {
            background:
                linear-gradient(135deg, rgba(214, 182, 88, 0.3), rgba(96, 68, 12, 0.26)),
                rgba(8, 8, 8, 0.58);
        }

        .login-slot {
            width: 100%;
            height: 4.5rem;
        }

        .theme-footer {
            color: rgba(255, 255, 255, 0.74);
            text-shadow: 0 8px 18px rgba(0, 0, 0, 0.28);
        }

        .heartbeat {
            animation: heartbeat 1.5s infinite;
        }

        @keyframes heartbeat {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }
        }

        @keyframes reflection-sweep {

            0% {
                transform: translate(calc(100% + 240px), -46%) rotate(34deg);
            }

            12.5% {
                transform: translate(-240px, -46%) rotate(34deg);
            }

            100% {
                transform: translate(-240px, -46%) rotate(34deg);
            }
        }

        @keyframes shape-spin-in {
            0% {
                transform: rotate(-360deg);
            }

            100% {
                transform: var(--final-transform, rotate(0deg));
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .ring-enter-left,
            .ring-enter-right,
            .scene-decor,
            .page-heading,
            .logo-shell,
            .shape {
                transition: none !important;
                animation: none !important;
            }

            .heartbeat {
                animation: none;
            }
        }

        @media (max-width: 960px) {
            body {
                padding: 24px 18px 32px;
            }

            .page-heading {
                font-size: clamp(2.3rem, 7vw, 3.4rem);
            }

            .content-stage {
                padding-top: 0;
            }

            .intro-row {
                gap: 20px;
            }

            .theme-logo {
                width: 9.4rem;
                height: 9.4rem;
            }
        }

        @media (max-width: 640px) {
            body::before,
            body::after {
                opacity: 0.55;
            }

            .content-stage {
                padding-top: 78px;
            }

            .login-slot {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 64px;
                display: flex;
                align-items: center;
                justify-content: flex-end;
                padding: 10px 16px;
                z-index: 8;
                background:
                    radial-gradient(circle at top, rgba(255, 255, 255, 0.18), transparent 42%),
                    linear-gradient(135deg, #1c1c1c, #0c0c0c 55%, #262626);
                box-shadow:
                    0 10px 26px rgba(0, 0, 0, 0.34),
                    inset 0 1px 0 rgba(255, 255, 255, 0.08);
            }

            .theme-login {
                position: static;
                top: auto;
                right: auto;
                margin: 0;
                z-index: auto;
            }
        }
    </style>
</head>
<body>
    <div class="gold-ring ring-top-left-large ring-enter-left" aria-hidden="true"></div>
    <div class="gold-ring ring-top-left-small ring-enter-left" aria-hidden="true"></div>
    <div class="gold-ring ring-right ring-enter-right" aria-hidden="true"></div>
    <div class="gold-ring ring-left ring-enter-left" aria-hidden="true"></div>

    <div class="scene-decor decor-left" aria-hidden="true" id="decorLeft">
        <span class="shape cube solid" style="width: 98px; height: 98px; left: 10px; bottom: 12px; --final-transform: rotate(-12deg);"></span>
        <span class="shape cube outline" style="width: 76px; height: 76px; left: 92px; bottom: 56px; --final-transform: rotate(11deg);"></span>
        <span class="shape cube solid" style="width: 58px; height: 58px; left: 58px; bottom: 138px; --final-transform: rotate(23deg);"></span>
        <span class="shape cube outline" style="width: 88px; height: 88px; left: 160px; bottom: 22px; --final-transform: rotate(-8deg);"></span>
        <span class="shape cube solid" style="width: 52px; height: 52px; left: 212px; bottom: 118px; --final-transform: rotate(17deg);"></span>

        <span class="shape triangle blue" style="left: 26px; bottom: 124px; --final-transform: rotate(18deg) scale(1.05);"></span>
        <span class="shape triangle white" style="left: 138px; bottom: 126px; --final-transform: rotate(-24deg) scale(0.72);"></span>
        <span class="shape triangle blue" style="left: 108px; bottom: 192px; --final-transform: rotate(32deg) scale(0.85);"></span>
        <span class="shape triangle white" style="left: 186px; bottom: 82px; --final-transform: rotate(12deg) scale(1.1);"></span>
        <span class="shape triangle blue" style="left: 222px; bottom: 166px; --final-transform: rotate(-14deg) scale(0.68);"></span>
        <span class="shape triangle white" style="left: 78px; bottom: 24px; --final-transform: rotate(42deg) scale(0.9);"></span>
        <span class="shape triangle blue" style="left: 250px; bottom: 60px; --final-transform: rotate(8deg) scale(0.78);"></span>
    </div>

    <div class="scene-decor decor-right" aria-hidden="true" id="decorRight">
        <span class="shape cube outline" style="width: 94px; height: 94px; right: 12px; bottom: 18px; --final-transform: rotate(9deg);"></span>
        <span class="shape cube solid" style="width: 74px; height: 74px; right: 112px; bottom: 72px; --final-transform: rotate(-14deg);"></span>
        <span class="shape cube outline" style="width: 56px; height: 56px; right: 78px; bottom: 162px; --final-transform: rotate(22deg);"></span>
        <span class="shape cube solid" style="width: 84px; height: 84px; right: 180px; bottom: 24px; --final-transform: rotate(13deg);"></span>
        <span class="shape cube outline" style="width: 64px; height: 64px; right: 222px; bottom: 112px; --final-transform: rotate(-18deg);"></span>

        <span class="shape triangle white" style="right: 32px; bottom: 126px; --final-transform: rotate(-16deg) scale(0.92);"></span>
        <span class="shape triangle blue" style="right: 126px; bottom: 140px; --final-transform: rotate(28deg) scale(0.76);"></span>
        <span class="shape triangle white" style="right: 98px; bottom: 204px; --final-transform: rotate(-34deg) scale(0.7);"></span>
        <span class="shape triangle blue" style="right: 196px; bottom: 92px; --final-transform: rotate(14deg) scale(1.08);"></span>
        <span class="shape triangle white" style="right: 230px; bottom: 154px; --final-transform: rotate(38deg) scale(0.74);"></span>
        <span class="shape triangle blue" style="right: 70px; bottom: 34px; --final-transform: rotate(-10deg) scale(0.88);"></span>
        <span class="shape triangle white" style="right: 176px; bottom: 14px; --final-transform: rotate(21deg) scale(0.84);"></span>
    </div>

    <div class="content-stage flex flex-col items-center justify-start min-h-screen px-6 py-12"
        x-data="{ ready: false, loginReady: false, footerReady: false, reduceMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches }"
        x-init="if (reduceMotion) {
            ready = true;
            loginReady = true;
            footerReady = true;
        } else {
            setTimeout(() => ready = true, 1500);
            setTimeout(() => loginReady = true, 2500);
            setTimeout(() => footerReady = true, 3000);
        }">

        <div class="intro-row">
            <h1 class="page-heading" id="pageHeading">
                <span class="welcome">Welcome</span>
                <span class="to">to</span>
            </h1>

            <div class="logo-shell" id="heroLogo">
                <img src="{{ url('images/logo.png') }}" alt="Logo"
                    class="theme-logo rounded-2xl object-contain p-2">
            </div>
        </div>

        <div x-cloak x-show="ready" x-transition:enter="transition ease-out duration-700"
            x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            class="mb-8 text-center">
            <h1 class="welcome-title mb-3 font-bold">JewelTrack</h1>
            <p class="welcome-copy">Manage luxurious jewelry orders with elegance and efficiency.</p>
        </div>

        <div x-cloak x-show="ready" x-transition:enter="transition ease-out duration-700"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            class="cards-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
            <a href="#" class="theme-card theme-nav-card rounded-2xl backdrop-blur-md">
                <h2 class="nav-heading mb-2 font-semibold">View Order Histories</h2>
                <p class="nav-copy">Browse all past and recent orders.</p>
            </a>

            <a href="#" class="theme-card theme-nav-card rounded-2xl backdrop-blur-md">
                <h2 class="nav-heading mb-2 font-semibold">Create New Order</h2>
                <p class="nav-copy">Place a new order in the system.</p>
            </a>

            <a href="#" class="theme-card theme-nav-card rounded-2xl backdrop-blur-md">
                <h2 class="nav-heading mb-2 font-semibold">Order Status</h2>
                <p class="nav-copy">Track pending and arrived orders.</p>
            </a>

            <a href="#" class="theme-card theme-nav-card rounded-2xl backdrop-blur-md">
                <h2 class="nav-heading mb-2 font-semibold">Branch Performance</h2>
                <p class="nav-copy">Analyze how each branch performs.</p>
            </a>

            <a href="#" class="theme-card theme-nav-card rounded-2xl backdrop-blur-md">
                <h2 class="nav-heading mb-2 font-semibold">Sales Rate</h2>
                <p class="nav-copy">Visualize sales trends over time.</p>
            </a>

            <a href="#" class="theme-card theme-nav-card rounded-2xl backdrop-blur-md">
                <h2 class="nav-heading mb-2 font-semibold">Top Products</h2>
                <p class="nav-copy">View most demanded jewelry pieces.</p>
            </a>
        </div>

        <div class="login-slot">
            <a href="{{ route('login') }}" x-cloak x-show="loginReady" x-transition:enter="transition ease-out duration-700"
                x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100" :class="reduceMotion ? '' : 'heartbeat'"
                class="theme-card theme-login inline-flex items-center justify-center rounded-full px-6 py-2 text-lg font-semibold backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-yellow-200/60 focus:ring-offset-2 focus:ring-offset-transparent">
                Login
            </a>
        </div>

        <div x-cloak x-show="footerReady" x-transition:enter="transition ease-out duration-700"
            x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            class="theme-footer mt-10 text-sm">
            Invented by <span style="color: cyan;">IT Department</span> • Shwe Tatar
        </div>
    </div>

    <script>
        window.addEventListener("load", () => {
            const pageBody = document.body;
            const leftRings = document.querySelectorAll(".ring-enter-left");
            const rightRing = document.querySelector(".ring-enter-right");
            const leftDecor = document.getElementById("decorLeft");
            const rightDecor = document.getElementById("decorRight");
            const heading = document.getElementById("pageHeading");
            const heroLogo = document.getElementById("heroLogo");
            const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

            if (reduceMotion) {
                leftRings.forEach((ring) => ring.classList.add("is-visible"));
                if (rightRing) rightRing.classList.add("is-visible");
                if (leftDecor) leftDecor.classList.add("is-visible");
                if (rightDecor) rightDecor.classList.add("is-visible");
                if (heading) heading.classList.add("is-visible");
                if (heroLogo) heroLogo.classList.add("is-visible");
                if (pageBody) pageBody.classList.add("reflections-active");
                return;
            }

            window.setTimeout(() => {
                leftRings.forEach((ring) => ring.classList.add("is-visible"));
                if (rightRing) {
                    rightRing.classList.add("is-visible");
                }
            }, 100);

            window.setTimeout(() => {
                if (leftDecor) {
                    leftDecor.classList.add("is-visible");
                }
                if (rightDecor) {
                    rightDecor.classList.add("is-visible");
                }
            }, 1500);

            window.setTimeout(() => {
                if (heading) {
                    heading.classList.add("is-visible");
                }
                if (heroLogo) {
                    heroLogo.classList.add("is-visible");
                }
            }, 500);

            window.setTimeout(() => {
                if (pageBody) {
                    pageBody.classList.add("reflections-active");
                }
            }, 2300);
        });
    </script>
</body>
</html>
