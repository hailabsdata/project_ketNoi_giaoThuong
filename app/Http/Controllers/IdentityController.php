<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IdentityController extends Controller
{
    // GET /api/identity/profile  (yêu cầu auth:sanctum)
    public function profile(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }
}
