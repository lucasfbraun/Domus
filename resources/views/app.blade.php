<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light">

        <style>
            html {
                background-color: hsl(0 0% 100%);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])

        {{-- Brand tokens after Vite so Cloudflare/browser CSS cache cannot keep the old teal palette --}}
        <style>
            :root {
                --background: hsl(0 0% 100%);
                --foreground: hsl(0 0% 9%);
                --card: hsl(0 0% 100%);
                --card-foreground: hsl(0 0% 9%);
                --popover: hsl(0 0% 100%);
                --popover-foreground: hsl(0 0% 9%);
                --primary: hsl(221 83% 53%);
                --primary-foreground: hsl(0 0% 100%);
                --secondary: hsl(0 0% 96%);
                --secondary-foreground: hsl(0 0% 15%);
                --muted: hsl(0 0% 96%);
                --muted-foreground: hsl(0 0% 40%);
                --accent: hsl(214 95% 93%);
                --accent-foreground: hsl(221 70% 35%);
                --border: hsl(0 0% 90%);
                --input: hsl(0 0% 90%);
                --ring: hsl(221 83% 53%);
                --chart-1: hsl(221 83% 53%);
                --sidebar-background: hsl(0 0% 100%);
                --sidebar-foreground: hsl(0 0% 45%);
                --sidebar-primary: hsl(221 83% 53%);
                --sidebar-primary-foreground: hsl(0 0% 100%);
                --sidebar-accent: hsl(214 95% 95%);
                --sidebar-accent-foreground: hsl(221 83% 53%);
                --sidebar-border: hsl(0 0% 91%);
                --sidebar-ring: hsl(221 83% 53%);
                --sidebar: hsl(0 0% 100%);
                --surface-tint: hsl(0 0% 98%);
            }
        </style>

        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
