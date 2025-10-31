<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ContentAdminController extends Controller
{
    public function listTradePosts(Request $request)
    {
        $q = DB::table('trade_posts');
        if ($status = $request->input('status')) {
            $q->where('status', $status);
        }
        if ($authorId = $request->input('author_id')) {
            $q->where('author_id', (int)$authorId);
        }
        $perPage = max(1, min(100, (int)$request->input('per_page', 20)));
        $rows = $q->orderBy('created_at', 'desc')->paginate($perPage);
        return response()->json($rows);
    }

    public function approveTradePost(Request $request, int $id)
    {
        $adminId = (int)($request->user()->id ?? 0);
        $updated = DB::table('trade_posts')->where('id', $id)->update(['status' => 'approved']);
        if ($updated) {
            DB::table('moderation_logs')->insert([
                'admin_user_id' => $adminId,
                'target_type'   => 'trade_post',
                'target_id'     => $id,
                'action'        => 'approved',
                'reason'        => null,
                'created_at'    => now(),
            ]);
        }
        return response()->json(['approved' => (bool)$updated, 'id' => $id]);
    }

    public function rejectTradePost(Request $request, int $id)
    {
        $data = $request->validate([
            'reason' => ['required','string','max:1000'],
        ]);
        $adminId = (int)($request->user()->id ?? 0);
        $updated = DB::table('trade_posts')->where('id', $id)->update(['status' => 'rejected']);
        if ($updated) {
            DB::table('moderation_logs')->insert([
                'admin_user_id' => $adminId,
                'target_type'   => 'trade_post',
                'target_id'     => $id,
                'action'        => 'rejected',
                'reason'        => $data['reason'],
                'created_at'    => now(),
            ]);
        }
        return response()->json(['rejected' => (bool)$updated, 'id' => $id]);
    }

    public function listProducts(Request $request)
    {
        $q = DB::table('products');
        if ($status = $request->input('status')) {
            $q->where('status', $status);
        }
        if ($shopId = $request->input('shop_id')) {
            $q->where('shop_id', (int)$shopId);
        }
        $perPage = max(1, min(100, (int)$request->input('per_page', 20)));
        $rows = $q->orderBy('updated_at', 'desc')->paginate($perPage);
        return response()->json($rows);
    }

    public function updateProductStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['draft','active','inactive'])],
            'reason' => ['nullable','string','max:1000'],
        ]);
        $adminId = (int)($request->user()->id ?? 0);
        $updated = DB::table('products')->where('id', $id)->update(['status' => $data['status']]);
        if ($updated) {
            DB::table('moderation_logs')->insert([
                'admin_user_id' => $adminId,
                'target_type'   => 'product',
                'target_id'     => $id,
                'action'        => 'status:' . $data['status'],
                'reason'        => $data['reason'] ?? null,
                'created_at'    => now(),
            ]);
        }
        return response()->json(['updated' => (bool)$updated, 'id' => $id, 'status' => $data['status']]);
    }
}

