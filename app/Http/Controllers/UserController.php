<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // GET /api/users
    public function index(Request $request)
    {
        // có thể thêm filter/search ở đây nếu cần
        $q = User::query()->orderByDesc('id');

        $paginator = $q->paginate($request->perPage()); // clamp 1..100, mặc định 20
        return response()->page($paginator);            // chuẩn JSON: data + meta + links
    }
}
