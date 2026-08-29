<?php

test('app shell exposes pwa metadata', function () {
    $response = $this->get(route('login'));

    $response
        ->assertSuccessful()
        ->assertSee('/manifest.webmanifest', false)
        ->assertSee('theme-color', false);
});

test('web manifest declares installable app metadata', function () {
    $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

    expect($manifest)
        ->toBeArray()
        ->toMatchArray([
            'name' => 'Domus',
            'short_name' => 'Domus',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#2563eb',
        ])
        ->and($manifest['icons'])
        ->toBeArray()
        ->not->toBeEmpty();

    $png = collect($manifest['icons'])->where('type', 'image/png');

    expect($png->where('purpose', 'any')->pluck('sizes'))
        ->toContain('192x192')
        ->toContain('512x512')
        ->and($png->where('purpose', 'maskable')->pluck('sizes'))
        ->toContain('192x192')
        ->toContain('512x512');
});

test('every manifest icon exists on disk', function () {
    $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

    foreach ($manifest['icons'] as $icon) {
        expect(public_path(ltrim($icon['src'], '/')))->toBeReadableFile();
    }
});

test('png manifest icons are squares of the declared size', function () {
    $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

    $icons = collect($manifest['icons'])
        ->where('type', 'image/png')
        ->reject(fn (array $icon) => $icon['sizes'] === 'any');

    expect($icons)->not->toBeEmpty();

    foreach ($icons as $icon) {
        [$width, $height] = getimagesize(public_path(ltrim($icon['src'], '/')));
        [$declaredWidth, $declaredHeight] = explode('x', $icon['sizes']);

        expect([$width, $height])
            ->toBe([(int) $declaredWidth, (int) $declaredHeight]);
    }
});

test('login page renders the pwa install card', function () {
    $loginPage = file_get_contents(resource_path('js/pages/auth/Login.vue'));

    expect($loginPage)
        ->toContain("import PwaInstallCard from '@/components/PwaInstallCard.vue';")
        ->toContain('<PwaInstallCard />');
});

test('pwa install card keeps an actionable fallback when native prompt is unavailable', function () {
    $installCard = file_get_contents(resource_path('js/components/PwaInstallCard.vue'));

    expect($installCard)
        ->toContain('data-test="pwa-install-button"')
        ->toContain('data-test="pwa-install-fallback"')
        ->toContain('showManualInstructions.value =');
});

test('beforeinstallprompt is captured by the entry bundle, not by a component', function () {
    $app = file_get_contents(resource_path('js/app.ts'));
    $pwa = file_get_contents(resource_path('js/lib/pwa.ts'));
    $installCard = file_get_contents(resource_path('js/components/PwaInstallCard.vue'));

    // Chrome fires the event once and never replays it, so a component mounting
    // later would miss it and the install button would go dead.
    expect($app)
        ->toContain("import '@/lib/pwa';")
        ->and($pwa)
        ->toContain("window.addEventListener('beforeinstallprompt'")
        ->toContain("window.addEventListener('appinstalled'")
        ->and($installCard)
        ->not->toContain('beforeinstallprompt');
});

test('frontend registers the service worker', function () {
    $app = file_get_contents(resource_path('js/app.ts'));

    expect($app)
        ->toContain("'serviceWorker' in navigator")
        ->toContain("navigator.serviceWorker.register('/sw.js'");
});

test('service worker serves an offline shell so the app stays installable', function () {
    $serviceWorker = file_get_contents(public_path('sw.js'));

    expect($serviceWorker)
        ->toContain("'install'")
        ->toContain("'fetch'")
        ->toContain('/icons/icon-192.png')
        ->toContain('/icons/icon-512.png')
        ->toContain("request.mode === 'navigate'")
        ->toContain('OFFLINE_URL')
        ->and(public_path('offline.html'))
        ->toBeReadableFile();
});
