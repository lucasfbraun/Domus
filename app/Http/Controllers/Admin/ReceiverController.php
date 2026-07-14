<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReceiverRequest;
use App\Http\Requests\Admin\UpdateReceiverRequest;
use App\Models\Receiver;
use App\Models\User;
use App\Services\MercadoPagoService;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

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

    public function create(): Response
    {
        $this->authorize('create', Receiver::class);

        return Inertia::render('admin/receivers/Form', [
            'receiver' => null,
        ]);
    }

    public function store(StoreReceiverRequest $request): RedirectResponse
    {
        $this->authorize('create', Receiver::class);

        $userId = null;
        if ($request->filled('password')) {
            $user = User::query()->create([
                'name' => $request->string('name'),
                'email' => $request->string('email'),
                'password' => Hash::make($request->string('password')),
            ]);
            $user->assignRole(UserRole::Receiver);
            $userId = $user->id;
        }

        Receiver::query()->create([
            ...$request->safe()->only(['name', 'document', 'email', 'mercado_pago_account', 'active']),
            'user_id' => $userId,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Recebedor cadastrado.']);

        return to_route('admin.receivers.index');
    }

    public function edit(Receiver $receiver): Response
    {
        $this->authorize('update', $receiver);

        return Inertia::render('admin/receivers/Form', [
            'receiver' => $receiver->load('user'),
        ]);
    }

    public function update(UpdateReceiverRequest $request, Receiver $receiver): RedirectResponse
    {
        $this->authorize('update', $receiver);

        $receiver->update($request->safe()->only(['name', 'document', 'email', 'mercado_pago_account', 'active']));

        if ($request->filled('password')) {
            if (! $receiver->user_id) {
                $user = User::query()->create([
                    'name' => $receiver->name,
                    'email' => $receiver->email,
                    'password' => Hash::make($request->string('password')),
                ]);
                $user->assignRole(UserRole::Receiver);
                $receiver->update(['user_id' => $user->id]);
            } else {
                $receiver->user?->update(['password' => Hash::make($request->string('password'))]);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Recebedor atualizado.']);

        return to_route('admin.receivers.index');
    }

    public function destroy(Receiver $receiver): RedirectResponse
    {
        $this->authorize('delete', $receiver);

        $receiver->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Recebedor removido.']);

        return to_route('admin.receivers.index');
    }

    public function connectMercadoPago(Request $request, Receiver $receiver, MercadoPagoService $mercadoPago): RedirectResponse
    {
        $this->authorize('update', $receiver);

        $redirectUri = route('admin.receivers.mercadopago.callback');
        $url = $mercadoPago->getAuthorizationUrl($receiver, $redirectUri);

        return redirect()->away($url);
    }

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

        return to_route('admin.receivers.index');
    }
}
