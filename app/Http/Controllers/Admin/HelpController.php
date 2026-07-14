<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HelpContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
