<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReceiverPortalController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $receiver = $user->receiver;
        abort_unless($receiver, 403);

        return Inertia::render('portal/Receiver', [
            'contracts' => Contract::query()
                ->with(['property', 'tenant'])
                ->where('receiver_id', $receiver->id)
                ->orderByDesc('starts_at')
                ->get(),
            'charges' => Charge::query()
                ->with(['contract.property', 'contract.tenant'])
                ->where('receiver_id', $receiver->id)
                ->orderByDesc('due_date')
                ->get(),
        ]);
    }
}
