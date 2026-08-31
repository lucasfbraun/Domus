<?php

use App\Enums\UserRole;
use App\Jobs\GenerateMonthlyChargesJob;
use App\Jobs\RunReminderSweepJob;
use App\Jobs\RunScheduledBackupJob;
use App\Jobs\SyncPendingPixPaymentsJob;
use App\Models\Owner;
use App\Models\Receiver;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PortalAccountService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * One-off remediation for records edited before PortalAccountService::sync()
 * started keeping an exclusive login's name/email in step with its
 * Tenant/Receiver/Owner (see docs/adr/0006-shared-login-across-roles.md
 * addendum) — those edits never touched the login, so it's still showing
 * whatever it had before. Safe to run any time, including repeatedly: it
 * only touches logins isExclusiveLogin() confirms are dedicated to a single
 * record, and only writes when something actually differs.
 */
Artisan::command('portal-accounts:sync-logins', function (PortalAccountService $portalAccounts) {
    $sync = function (?int $userId, UserRole $role, string $name, ?string $email) use ($portalAccounts): bool {
        if ($userId === null || ! filled($email) || ! $portalAccounts->isExclusiveLogin($userId, $role)) {
            return false;
        }

        $user = User::query()->find($userId);

        if ($user === null || ($user->name === $name && $user->email === $email)) {
            return false;
        }

        $user->update(['name' => $name, 'email' => $email]);

        return true;
    };

    $synced = 0;

    Tenant::query()->whereNotNull('user_id')->each(function (Tenant $tenant) use ($sync, &$synced) {
        $synced += (int) $sync($tenant->user_id, UserRole::Tenant, $tenant->name, $tenant->email);
    });

    Receiver::query()->whereNotNull('user_id')->each(function (Receiver $receiver) use ($sync, &$synced) {
        $synced += (int) $sync($receiver->user_id, UserRole::Receiver, $receiver->name, $receiver->email);
    });

    Owner::query()->whereNotNull('user_id')->each(function (Owner $owner) use ($sync, &$synced) {
        $synced += (int) $sync($owner->user_id, UserRole::Owner, $owner->name, $owner->email);
    });

    $this->info("Login(s) sincronizado(s): {$synced}.");
})->purpose('Sincroniza nome/e-mail de logins dedicados de inquilino/recebedor/proprietario com o cadastro, para registros editados antes da correção do bug de sincronismo');

Schedule::job(new GenerateMonthlyChargesJob)->dailyAt('09:00')->timezone('America/Sao_Paulo');
Schedule::job(new RunReminderSweepJob)->dailyAt('10:00')->timezone('America/Sao_Paulo');

// Runs every hour but only actually creates a backup when one is due —
// see BackupScheduleService::runIfDue() and Admin -> Configurações.
Schedule::job(new RunScheduledBackupJob)->hourly();

// Stand-in for a Mercado Pago webhook: polls pending Pix orders instead of
// waiting for a push notification. Safe to keep running alongside a real
// webhook too (it's idempotent — see SyncPendingPixPaymentsJob). Ticks
// every minute but only actually polls Mercado Pago when the admin-
// configured interval on PixSyncSetting is due — see
// PixSyncScheduleService::isDue() and Admin -> Configurações.
Schedule::job(new SyncPendingPixPaymentsJob)->everyMinute();
