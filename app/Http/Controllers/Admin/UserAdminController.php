<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserAdminController extends Controller
{
    public function index(Request $request)
    {
        $q = DB::table('users');

        if ($search = trim((string)$request->input('q'))) {
            $q->where(function ($sub) use ($search) {
                $like = "%" . str_replace(['%', '_'], ['\\%', '\\_'], $search) . "%";
                $sub->where('email', 'like', $like)
                    ->orWhere('full_name', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }

        if ($role = $request->input('role')) {
            $q->where('role', $role);
        }

        if ($status = $request->input('status')) {
            // Optional column; only filter if exists
            try {
                $q->where('status', $status);
            } catch (\Throwable $e) {
                // ignore if status column doesn't exist
            }
        }

        $perPage = max(1, min(100, (int)$request->input('per_page', 20)));
        $users = $q->orderBy('created_at', 'desc')->paginate($perPage);
        return response()->json($users);
    }

    public function updateRole(Request $request, int $id)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['admin','seller','buyer'])],
        ]);

        $updated = DB::table('users')->where('id', $id)->update(['role' => $data['role']]);
        return response()->json([
            'updated' => (bool)$updated,
            'id' => $id,
            'role' => $data['role'],
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active','suspended'])],
        ]);

        try {
            $updated = DB::table('users')->where('id', $id)->update(['status' => $data['status']]);
        } catch (\Throwable $e) {
            return response()->json([
                'updated' => false,
                'error' => 'users.status column missing. Add it or run migrations.',
            ], 400);
        }

        return response()->json([
            'updated' => (bool)$updated,
            'id' => $id,
            'status' => $data['status'],
        ]);
    }
}

