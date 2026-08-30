<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunTestSuiteJob;
use App\Support\FeatureCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin-only "Funcionalidades" page: a curated catalog of the app's
 * features cross-referenced against real test files (see
 * {@see FeatureCatalog}), plus a button to actually run the test suite and
 * see it pass or fail.
 *
 * Running is only available in local dev (see {@see isAvailable()}):
 * production ships without dev dependencies (`composer install --no-dev`,
 * see the root Dockerfile) so Pest isn't even present there, and a full
 * run takes several minutes — unsuitable for a synchronous HTTP request
 * and not something to expose on a live production instance. See
 * docs/adr/0008-feature-checks-page.md.
 */
class FeatureCheckController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/FeatureChecks', [
            'features' => FeatureCatalog::all(),
            'runnerAvailable' => $this->isAvailable(),
            'running' => (bool) Cache::get(RunTestSuiteJob::RUNNING_CACHE_KEY),
            'lastRun' => Cache::get(RunTestSuiteJob::CACHE_KEY),
        ]);
    }

    public function run(): RedirectResponse
    {
        if (! $this->isAvailable()) {
            return back()->withErrors([
                'run' => 'A execucao de testes so esta disponivel em ambiente local, com as dependencias de desenvolvimento instaladas.',
            ]);
        }

        if (Cache::get(RunTestSuiteJob::RUNNING_CACHE_KEY)) {
            return back()->withErrors(['run' => 'Ja existe uma execucao em andamento.']);
        }

        Cache::put(RunTestSuiteJob::RUNNING_CACHE_KEY, true, now()->addMinutes(15));
        RunTestSuiteJob::dispatch();

        return back();
    }

    public function status(): JsonResponse
    {
        return response()->json([
            'running' => (bool) Cache::get(RunTestSuiteJob::RUNNING_CACHE_KEY),
            'lastRun' => Cache::get(RunTestSuiteJob::CACHE_KEY),
        ]);
    }

    private function isAvailable(): bool
    {
        return config('services.feature_checks.enabled') === true
            && File::exists(base_path('vendor/bin/pest'));
    }
}
