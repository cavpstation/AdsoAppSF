<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>{{ config('app.name', 'Laravel') }}</title>

    <script src="//cdn.tailwindcss.com"></script>

    <x-laravel-exceptions-renderer::styles :$exception />
</head>
<body class="font-sans antialiased bg-gray-200/80 dark:bg-gray-950/95">
    {{ $slot }}

    <x-laravel-exceptions-renderer::scripts :$exception />
</body>
</html>
