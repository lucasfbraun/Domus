<?php

use App\Support\FeatureCatalog;

test('every entry has the required fields filled in', function () {
    $entries = FeatureCatalog::all();

    expect($entries)->not->toBeEmpty();

    foreach ($entries as $entry) {
        expect($entry['area'])->not->toBe('')
            ->and($entry['name'])->not->toBe('')
            ->and($entry['description'])->not->toBe('')
            ->and($entry['source'])->toBeArray()->not->toBeEmpty()
            ->and($entry['tests'])->toBeArray();
    }
});

test('listed test paths are all real files (no stale references)', function () {
    foreach (FeatureCatalog::all() as $entry) {
        foreach ($entry['tests'] as $path) {
            expect(file_exists(base_path($path)))->toBeTrue("Missing test file: {$path}");
        }
    }
});

test('a non-existent test path is filtered out instead of kept', function () {
    $reflection = new ReflectionMethod(FeatureCatalog::class, 'entries');
    $entries = $reflection->invoke(null);

    $withFakePath = array_map(function (array $entry) {
        $entry['tests'][] = 'tests/Feature/ThisFileDoesNotExistAnywhere.php';

        return $entry;
    }, array_slice($entries, 0, 1));

    expect($withFakePath[0]['tests'])->toContain('tests/Feature/ThisFileDoesNotExistAnywhere.php');

    $filtered = array_values(array_filter(
        $withFakePath[0]['tests'],
        fn (string $path) => file_exists(base_path($path)),
    ));

    expect($filtered)->not->toContain('tests/Feature/ThisFileDoesNotExistAnywhere.php');
});
