<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Shared base for all HTTP controllers; wires in `authorize()`/`authorizeResource()`
 * via {@see AuthorizesRequests}.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
