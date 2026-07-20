<?php

test('theme uses white background and blue primary', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('--background: hsl(0 0% 100%);')
        ->toContain('--primary: hsl(221 83% 53%);')
        ->toContain('--sidebar-background: hsl(0 0% 100%);')
        ->toContain('--sidebar-foreground: hsl(0 0% 45%);')
        ->not->toContain('--primary: hsl(175 72% 28%);')
        ->not->toContain('--primary: hsl(217 72% 40%);')
        ->not->toContain('--background: hsl(160 18% 97%);');
});

test('app shell bootstraps with white html background', function () {
    $blade = file_get_contents(resource_path('views/app.blade.php'));

    expect($blade)
        ->toContain('background-color: hsl(0 0% 100%);')
        ->toContain('--primary: hsl(221 83% 53%);')
        ->toContain('--sidebar-foreground: hsl(0 0% 45%);')
        ->not->toContain('background-color: hsl(160 18% 97%);');
});
