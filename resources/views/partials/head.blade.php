<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#0a9b4d">
<meta name="view-transition" content="same-origin">
<title>{{ isset($title) && $title ? $title.' · ' : '' }}{{ config('app.name', 'FreshMarket') }}</title>
{{-- Scroll-reveal styles only apply when JS runs, so content never stays hidden without it. --}}
<script>document.documentElement.classList.add('js')</script>
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
@vite(['resources/css/app.css', 'resources/js/app.js'])
