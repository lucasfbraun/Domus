<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReceiverRequest;
use App\Http\Requests\Admin\UpdateReceiverRequest;
use App\Models\Receiver;
use App\Policies\ReceiverPolicy;
use App\Services\MercadoPagoService;
use App\Services\PortalAccountService;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Manages Receiver records (payment recipients for charges) and their
 * Mercado Pago OAuth connection. See {@see ReceiverPolicy}:
 * admin-only — a receiver's own portal access (viewing their charges, etc.)
 * is governed elsewhere. A receiver's portal login can be a brand-new
 * dedicated account, or an existing one (e.g. the same login already used
 * as Admin) — see {@see PortalAccountService}.
 */
class ReceiverController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Receiver::class);

        return Inertia::render('admin/receivers/Index', [
            'receivers' => Receiver::query()
                ->with('user')
                ->orderBy('name')
                ->paginate(Pagination::PER_PAGE)
                ->withQueryString(),
        ]);
    }

    public function create(PortalAccountService $portalAccounts): Response
    {
        $this->authorize('create', Receiver::class);

        return Inertia::render('admin/receivers/Form', [
            'receiver' => null,
            'users' => $portalAccounts->linkableUsers(),
        ]);
    }

    public function store(StoreReceiverRequest $request, PortalAccountService $portalAccounts): RedirectResponse
    {
        $this->authorize('create', Receiver::class);

        $receiver = Receiver::query()->create([
            ...$request->safe()->only(['name', 'document', 'email', 'mercado_pago_account', 'active']),
            'user_id' => null,
        ]);

        $userId = $portalAccounts->sync(
            role: UserRole::Receiver,
            currentUserId: null,
            existingUserId: $request->integer('existing_user_id') ?: null,
            name: $receiver->name,
            email: $receiver->email,
            password: $request->string('password')->toString(),
        );

        if ($userId !== null) {
            $receiver->update(['user_id' => $userId]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Recebedor cadastrado.']);

        return to_route('admin.receivers.index');
    }

    public function edit(Receiver $receiver, PortalAccountService $portalAccounts): Response
    {
        $this->authorize('update', $receiver);

        $receiver->load('user.roles');

        return Inertia::render('admin/receivers/Form', [
            'receiver' => [
                ...$receiver->toArray(),
                'user' => $receiver->user ? [
                    'id' => $receiver->user->id,
                    'name' => $receiver->user->name,
                    'email' => $receiver->user->email,
                    'roles' => $receiver->user->roles->pluck('name')->all(),
                ] : null,
            ],
            'users' => $portalAccounts->linkableUsers(),
        ]);
    }

    public function update(UpdateReceiverRequest $request, Receiver $receiver, PortalAccountService $portalAccounts): RedirectResponse
    {
        $this->authorize('update', $receiver);

        $receiver->update($request->safe()->only(['name', 'document', 'email', 'mercado_pago_account', 'active']));

        $oldUserId = $receiver->user_id;
        $userId = $portalAccounts->sync(
            role: UserRole::Receiver,
            currentUserId: $oldUserId,
            existingUserId: $request->integer('existing_user_id') ?: null,
            name: $receiver->name,
            email: $receiver->email,
            password: $request->string('password')->toString(),
        );

        if ($userId !== $oldUserId) {
            $receiver->update(['user_id' => $userId]);

            if ($oldUserId !== null) {
                $portalAccounts->detach($oldUserId, UserRole::Receiver);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Recebedor atualizado.']);

        return to_route('admin.receivers.index');
    }

    public function destroy(Receiver $receiver, PortalAccountService $portalAccounts): RedirectResponse
    {
        $this->authorize('delete', $receiver);

        $userId = $receiver->user_id;

        $receiver->delete();

        if ($userId !== null) {
            $portalAccounts->detach($userId, UserRole::Receiver);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Recebedor removido.']);

        return to_route('admin.receivers.index');
    }

    /**
     * Starts the Mercado Pago OAuth Connect flow for this receiver: builds
     * the authorization URL (with a signed state binding it to this
     * receiver) and does a full browser redirect via `Inertia::location`
     * rather than an Inertia visit, since the destination is an external
     * origin.
     */
    public function connectMercadoPago(Request $request, Receiver $receiver, MercadoPagoService $mercadoPago): SymfonyResponse
    {
        $this->authorize('update', $receiver);

        $redirectUri = route('admin.receivers.mercadopago.callback');
        $url = $mercadoPago->getAuthorizationUrl($receiver, $redirectUri);

        return Inertia::location($url);
    }

    /**
     * OAuth redirect target for Mercado Pago Connect. Verifies the signed
     * `state` to recover which receiver initiated the flow, exchanges the
     * authorization `code` for tokens, and persists the connection on that
     * receiver.
     */
    public function mercadoPagoCallback(Request $request, MercadoPagoService $mercadoPago): RedirectResponse
    {
        $receiverId = $mercadoPago->verifyConnectState((string) $request->query('state'));
        $receiver = Receiver::query()->findOrFail($receiverId);

        $token = $mercadoPago->exchangeCodeForTokens(
            (string) $request->query('code'),
            route('admin.receivers.mercadopago.callback'),
        );

        $mercadoPago->saveReceiverConnection($receiver, $token);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mercado Pago conectado.']);

        return to_route('admin.receivers.edit', $receiver);
    }

    public function disconnectMercadoPago(Request $request, Receiver $receiver, MercadoPagoService $mercadoPago): RedirectResponse
    {
        $this->authorize('update', $receiver);

        $mercadoPago->clearReceiverConnection($receiver);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mercado Pago desconectado.']);

        return to_route('admin.receivers.edit', $receiver);
    }
}
