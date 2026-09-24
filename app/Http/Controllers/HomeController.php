<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Send each signed-in account type to its primary workspace.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        return in_array($request->user()->role, ['super_admin', 'admin', 'faculty'], true)
            ? redirect()->route('dashboard')
            : redirect()->route('feedback.create');
    }
}
