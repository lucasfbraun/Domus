<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Models\Receiver;
use App\Services\IncomeReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin-only "Informe de Rendimentos": monthly breakdown of net income
 * actually received (see {@see IncomeReportService}), filterable by year
 * (required, defaults to the current year), month (optional — omitting it
 * returns every month of that year), owner and receiver. No dedicated
 * Policy; gated only by the `role:admin` route middleware, same as
 * DashboardController — this is a read-only report over existing data,
 * not a resource with its own authorization rules.
 */
class IncomeReportController extends Controller
{
    public function index(Request $request, IncomeReportService $reportService): Response
    {
        $filters = $this->parseFilters($request);
        $report = $reportService->summarize($filters['year'], $filters['month'], $filters['ownerId'], $filters['receiverId']);

        return Inertia::render('admin/reports/IncomeReport', [
            'filters' => $filters,
            'months' => $report['months'],
            'total' => $report['total'],
            'payments' => $report['payments'],
            'owners' => Owner::query()->orderBy('name')->get(['id', 'name']),
            'receivers' => Receiver::query()->orderBy('name')->get(['id', 'name']),
            'availableYears' => $reportService->availableYears(),
        ]);
    }

    public function pdf(Request $request, IncomeReportService $reportService): HttpResponse
    {
        $filters = $this->parseFilters($request);
        $report = $reportService->summarize($filters['year'], $filters['month'], $filters['ownerId'], $filters['receiverId']);

        $pdf = Pdf::loadView('pdf.income-report', [
            'year' => $filters['year'],
            'month' => $filters['month'],
            'months' => $report['months'],
            'total' => $report['total'],
            'payments' => $report['payments'],
            'owner' => $filters['ownerId'] ? Owner::query()->find($filters['ownerId']) : null,
            'receiver' => $filters['receiverId'] ? Receiver::query()->find($filters['receiverId']) : null,
        ]);

        $fileName = 'informe-rendimentos-'.$filters['year']
            .($filters['month'] ? '-'.str_pad((string) $filters['month'], 2, '0', STR_PAD_LEFT) : '')
            .'.pdf';

        return $pdf->download($fileName);
    }

    /**
     * @return array{year: int, month: int|null, ownerId: int|null, receiverId: int|null}
     */
    private function parseFilters(Request $request): array
    {
        $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'owner_id' => ['nullable', 'integer', 'exists:owners,id'],
            'receiver_id' => ['nullable', 'integer', 'exists:receivers,id'],
        ]);

        return [
            'year' => $request->filled('year') ? (int) $request->integer('year') : (int) now()->year,
            'month' => $request->filled('month') ? (int) $request->integer('month') : null,
            'ownerId' => $request->filled('owner_id') ? (int) $request->integer('owner_id') : null,
            'receiverId' => $request->filled('receiver_id') ? (int) $request->integer('receiver_id') : null,
        ];
    }
}
