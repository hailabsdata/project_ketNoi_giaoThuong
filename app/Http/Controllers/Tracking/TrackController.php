<?php
namespace App\Http\Controllers\Tracking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Support\Correlation;

class TrackController extends Controller
{
    // POST /api/track/pageview
    public function pageview(Request $r) {
        $data = $r->validate([
            'company_id'  => 'required|integer',
            'path'        => 'required|string|max:255',
            'sid'         => 'nullable|string|max:64',      // session id từ FE (localStorage)
            'duration_ms' => 'nullable|integer|min:0',
        ]);

        DB::table('page_views')->insert([
            'company_id'    => $data['company_id'],
            'user_id'       => Auth::id(),
            'session_id'    => $data['sid'] ?? null,
            'path'          => $data['path'],
            'referrer'      => (string) $r->header('Referer', ''),
            'user_agent'    => substr((string) $r->userAgent(), 0, 255),
            'request_id'    => Correlation::requestId(),
            'correlation_id'=> Correlation::correlationId(),
            'duration_ms'   => $data['duration_ms'] ?? null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return response()->noContent();
    }

    // POST /api/track/ad
    public function ad(Request $r) {
        $data = $r->validate([
            'company_id'  => 'required|integer',
            'campaign_id' => 'required|string|max:64',
            'type'        => 'required|in:impression,click',
            'meta'        => 'array',
        ]);

        DB::table('ad_events')->insert([
            'company_id'    => $data['company_id'],
            'campaign_id'   => $data['campaign_id'],
            'type'          => $data['type'],
            'meta'          => json_encode($data['meta'] ?? []),
            'request_id'    => Correlation::requestId(),
            'correlation_id'=> Correlation::correlationId(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return response()->noContent();
    }
}
