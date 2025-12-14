<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) abort(401);
        $assets = $user->assets()->get(['symbol','amount','locked_amount']);
        
        return response()->json([
            'usd_balance' => $user->balance,
            'assets' => $assets
        ]);
    }
}
