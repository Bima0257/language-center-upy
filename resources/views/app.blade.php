<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php
            $landingComponents = ['Welcome', 'Auth/Login', 'Auth/Register', 'Auth/ConfirmPassword', 'Auth/ForgotPassword', 'Auth/ResetPassword', 'Auth/VerifyEmail'];
            $authComponents = ['Auth/Login', 'Auth/Register', 'Auth/ConfirmPassword', 'Auth/ForgotPassword', 'Auth/ResetPassword', 'Auth/VerifyEmail'];
            $isLanding = in_array($page['component'], $landingComponents);
            $isAuth = in_array($page['component'], $authComponents);
        @endphp
        @if($isLanding && !$isAuth)
        <script>
            if (sessionStorage.theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
        @elseif($isAuth)
        <script>
            document.documentElement.classList.remove('dark');
        </script>
        @else
        <script>
            if (localStorage.dashboardTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
        @endif

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
        <link href="https://fonts.bunny.net/css?family=lora:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onTurnstileLoad&render=explicit" async defer></script>
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
