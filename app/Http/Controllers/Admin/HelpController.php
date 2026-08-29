<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HelpContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Backs the in-app help/search widget with static help content served by
 * {@see HelpContent}. No authorization checks: available to
 * any authenticated admin-area user.
 */
class HelpController extends Controller
{
    public function search(Request $request, HelpContent $helpContent): JsonResponse
    {
        $query = (string) $request->query('q', '');

        return response()->json([
            'results' => $helpContent->search($query),
        ]);
    }
}
