<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComplaintAdminController extends Controller
{
    public function index(Request $request)
    {
        $q = DB::table('complaints');
        if ($status = $request->input('status')) {
            $q->where('status', $status);
        }
        if ($type = $request->input('type')) {
            $q->where('type', $type);
        }
        if ($targetType = $request->input('target_type')) {
            $q->where('target_type', $targetType);
        }
        $perPage = max(1, min(100, (int)$request->input('per_page', 20)));
        $rows = $q->orderBy('created_at','desc')->paginate($perPage);
        return response()->json($rows);
    }

    public function show(int $id)
    {
        $c = DB::table('complaints')->where('id', $id)->first();
        if (!$c) return response()->json(['message' => 'Not found'], 404);
        return response()->json($c);
    }

    public function resolve(Request $request, int $id)
    {
        $data = $request->validate([
            'resolution' => ['required','string','max:2000'],
            'action'     => ['nullable','string','max:191'],
        ]);
        $adminId = (int)($request->user()->id ?? 0);
        $updated = DB::table('complaints')->where('id', $id)->update([
            'status' => 'resolved',
            'resolution' => $data['resolution'],
            'resolved_by_admin_id' => $adminId,
            'updated_at' => now(),
        ]);
        if ($updated) {
            DB::table('moderation_logs')->insert([
                'admin_user_id' => $adminId,
                'target_type'   => 'complaint',
                'target_id'     => $id,
                'action'        => 'resolved' . (!empty($data['action']) ? ':' . $data['action'] : ''),
                'reason'        => $data['resolution'],
                'created_at'    => now(),
            ]);
        }
        return response()->json(['resolved' => (bool)$updated, 'id' => $id]);
    }

    public function reject(Request $request, int $id)
    {
        $data = $request->validate([
            'reason' => ['required','string','max:1000'],
        ]);
        $adminId = (int)($request->user()->id ?? 0);
        $updated = DB::table('complaints')->where('id', $id)->update([
            'status' => 'rejected',
            'resolution' => $data['reason'],
            'resolved_by_admin_id' => $adminId,
            'updated_at' => now(),
        ]);
        if ($updated) {
            DB::table('moderation_logs')->insert([
                'admin_user_id' => $adminId,
                'target_type'   => 'complaint',
                'target_id'     => $id,
                'action'        => 'rejected',
                'reason'        => $data['reason'],
                'created_at'    => now(),
            ]);
        }
        return response()->json(['rejected' => (bool)$updated, 'id' => $id]);
    }
}

