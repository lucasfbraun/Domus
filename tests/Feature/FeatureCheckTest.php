<?php

use App\Jobs\RunTestSuiteJob;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

test('non admin cannot access the feature checks page', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('admin.feature-checks.index'))
        ->assertForbidden();
});

test('admin sees the feature checks page with the catalog', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.feature-checks.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/FeatureChecks')
            ->has('features')
            ->has('runnerAvailable')
            ->has('running')
            ->has('lastRun'));
});

test('non admin cannot trigger a test run', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->post(route('admin.feature-checks.run'))
        ->assertForbidden();
});

test('admin cannot trigger a run when the runner is disabled', function () {
    config(['services.feature_checks.enabled' => false]);
    Bus::fake();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('admin.feature-checks.index'))
        ->post(route('admin.feature-checks.run'))
        ->assertRedirect(route('admin.feature-checks.index'))
        ->assertSessionHasErrors('run');

    Bus::assertNotDispatched(RunTestSuiteJob::class);
});

test('admin can trigger a run when the runner is enabled', function () {
    config(['services.feature_checks.enabled' => true]);
    Bus::fake();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.feature-checks.run'))
        ->assertRedirect();

    Bus::assertDispatched(RunTestSuiteJob::class);
    expect(Cache::get(RunTestSuiteJob::RUNNING_CACHE_KEY))->toBeTrue();
});

test('admin cannot trigger a second run while one is already in progress', function () {
    config(['services.feature_checks.enabled' => true]);
    Cache::put(RunTestSuiteJob::RUNNING_CACHE_KEY, true, now()->addMinutes(5));
    Bus::fake();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('admin.feature-checks.index'))
        ->post(route('admin.feature-checks.run'))
        ->assertRedirect(route('admin.feature-checks.index'))
        ->assertSessionHasErrors('run');

    Bus::assertNotDispatched(RunTestSuiteJob::class);
});

test('status endpoint reports whether a run is in progress and the last result', function () {
    $admin = User::factory()->admin()->create();
    Cache::put(RunTestSuiteJob::CACHE_KEY, ['status' => 'passed', 'summary' => '10 passed'], now()->addDay());

    $this->actingAs($admin)
        ->getJson(route('admin.feature-checks.status'))
        ->assertSuccessful()
        ->assertJson([
            'running' => false,
            'lastRun' => ['status' => 'passed', 'summary' => '10 passed'],
        ]);
});
