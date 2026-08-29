<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Manual database export/import (Admin -> Backups). Every action here is
 * destructive-adjacent (restore replaces the entire live database), so
 * restore requires the admin to type a literal confirmation phrase — see
 * {@see restore()}. Only the sqlite driver is supported; see
 * {@see DatabaseBackupService} and docs/adr/0005-sqlite-only-database-backup.md.
 */
class BackupController extends Controller
{
    private const RESTORE_CONFIRMATION_PHRASE = 'RESTAURAR BANCO';

    public function index(DatabaseBackupService $backups): Response
    {
        return Inertia::render('admin/backups/Index', [
            'supported' => $backups->isSupported(),
            'driver' => $backups->driver(),
            'backups' => $backups->list(),
        ]);
    }

    public function store(DatabaseBackupService $backups): RedirectResponse
    {
        try {
            $backups->create();
        } catch (RuntimeException $exception) {
            return back()->withErrors(['backup' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Backup gerado.']);

        return back();
    }

    public function download(string $filename, DatabaseBackupService $backups): BinaryFileResponse
    {
        try {
            $path = $backups->download($filename);
        } catch (\InvalidArgumentException|RuntimeException $exception) {
            abort(404, $exception->getMessage());
        }

        return response()->download($path, $filename);
    }

    /**
     * Wipes the live database and replaces it with a stored backup.
     * Requires `confirm` to exactly match RESTORE_CONFIRMATION_PHRASE —
     * a checkbox isn't enough friction for an operation this destructive.
     */
    public function restore(Request $request, string $filename, DatabaseBackupService $backups): RedirectResponse
    {
        $request->validate([
            'confirm' => ['required', 'string', 'in:'.self::RESTORE_CONFIRMATION_PHRASE],
        ], [
            'confirm.in' => 'Digite exatamente "'.self::RESTORE_CONFIRMATION_PHRASE.'" para confirmar.',
        ]);

        try {
            $safetyBackup = $backups->restore($filename);
        } catch (\InvalidArgumentException|RuntimeException $exception) {
            return back()->withErrors(['restore' => $exception->getMessage()]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Banco restaurado a partir de {$filename}. Backup do estado anterior salvo como {$safetyBackup}.",
        ]);

        return back();
    }

    public function destroy(string $filename, DatabaseBackupService $backups): RedirectResponse
    {
        $backups->delete($filename);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Backup removido.']);

        return back();
    }
}
