<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportsController extends Controller
{
    // GET /api/reports/overview?company_id=1&from=2025-11-01&to=2025-11-09
    public function overview(Request $r)
    {
        $v = $r->validate([
            'company_id' => 'required|integer',
            'from'       => 'nullable|date',
            'to'         => 'nullable|date',
        ]);

        $from = Carbon::parse($v['from'] ?? now()->subDays(7))->startOfDay();
        $to   = Carbon::parse($v['to']   ?? now())->endOfDay();

        // Visits (time series)
        $visits = DB::table('page_views')
            ->selectRaw('DATE(created_at) d, COUNT(*) pageviews, COUNT(DISTINCT session_id) unique_visits')
            ->where('company_id', $v['company_id'])
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('d')->orderBy('d')->get();

        // Orders & revenue (time series)
        $orders = DB::table('transactions')
            ->selectRaw('DATE(created_at) d, COUNT(*) orders, SUM(amount_cents) revenue_cents')
            ->where('company_id', $v['company_id'])
            ->whereIn('status', ['paid','completed'])
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('d')->orderBy('d')->get();

        // Ads (time series)
        $ads = DB::table('ad_events')
            ->selectRaw('DATE(created_at) d,
                         SUM(CASE WHEN type="impression" THEN 1 ELSE 0 END) impressions,
                         SUM(CASE WHEN type="click" THEN 1 ELSE 0 END) clicks')
            ->where('company_id', $v['company_id'])
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('d')->orderBy('d')->get();

        // Totals
        $totalPageviews = DB::table('page_views')
            ->where('company_id', $v['company_id'])
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $uniqueVisits = DB::table('page_views')
            ->where('company_id', $v['company_id'])
            ->whereBetween('created_at', [$from, $to])
            ->distinct()->count('session_id');

        $ordersTotal = DB::table('transactions')
            ->where('company_id', $v['company_id'])
            ->whereIn('status',['paid','completed'])
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $revenue = (int) DB::table('transactions')
            ->where('company_id', $v['company_id'])
            ->whereIn('status',['paid','completed'])
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount_cents');

        $impr = DB::table('ad_events')
            ->where('company_id', $v['company_id'])
            ->where('type','impression')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $clicks = DB::table('ad_events')
            ->where('company_id', $v['company_id'])
            ->where('type','click')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        return response()->json([
            'range' => ['from'=>$from->toDateString(),'to'=>$to->toDateString()],
            'totals' => [
                'pageviews'       => $totalPageviews,
                'unique_visits'   => $uniqueVisits,
                'orders'          => $ordersTotal,
                'revenue_cents'   => $revenue,
                'ad_impressions'  => $impr,
                'ad_clicks'       => $clicks,
                'ctr'             => $impr ? round($clicks/$impr, 4) : 0,
                'conversion_rate' => $uniqueVisits ? round($ordersTotal/$uniqueVisits, 4) : 0,
                'arpu_cents'      => $uniqueVisits ? intdiv($revenue, $uniqueVisits) : 0,
            ],
            'timeseries' => [
                'visits' => $visits,
                'orders' => $orders,
                'ads'    => $ads,
            ],
        ]);
    }

    // GET /api/reports/top-pages?company_id=1&limit=10
    public function topPages(Request $r)
    {
        $v = $r->validate([
            'company_id' => 'required|integer',
            'from'       => 'nullable|date',
            'to'         => 'nullable|date',
            'limit'      => 'nullable|integer',
        ]);

        $from  = Carbon::parse($v['from'] ?? now()->subDays(7))->startOfDay();
        $to    = Carbon::parse($v['to']   ?? now())->endOfDay();
        $limit = $v['limit'] ?? 10;

        $rows = DB::table('page_views')
            ->selectRaw('path, COUNT(*) pageviews, COUNT(DISTINCT session_id) unique_visits, AVG(duration_ms) avg_duration_ms')
            ->where('company_id', $v['company_id'])
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('path')
            ->orderByDesc('pageviews')
            ->limit($limit)
            ->get();

        return response()->json($rows);
    }

    // GET /api/reports/funnel?company_id=1
    public function funnel(Request $r)
    {
        $v = $r->validate([
            'company_id' => 'required|integer',
            'from'       => 'nullable|date',
            'to'         => 'nullable|date',
        ]);

        $from = Carbon::parse($v['from'] ?? now()->subDays(7))->startOfDay();
        $to   = Carbon::parse($v['to']   ?? now())->endOfDay();

        $impr   = DB::table('ad_events')->where('company_id',$v['company_id'])
                    ->where('type','impression')->whereBetween('created_at',[$from,$to])->count();
        $clicks = DB::table('ad_events')->where('company_id',$v['company_id'])
                    ->where('type','click')->whereBetween('created_at',[$from,$to])->count();
        $visits = DB::table('page_views')->where('company_id',$v['company_id'])
                    ->whereBetween('created_at',[$from,$to])->distinct()->count('session_id');
        $orders = DB::table('transactions')->where('company_id',$v['company_id'])
                    ->whereIn('status',['paid','completed'])
                    ->whereBetween('created_at',[$from,$to])->count();

        return response()->json([
            'impressions'     => $impr,
            'clicks'          => $clicks,
            'visits'          => $visits,
            'orders'          => $orders,
            'ctr'             => $impr ? round($clicks/$impr,4) : 0,
            'visit_rate'      => $clicks ? round($visits/$clicks,4) : 0,
            'conversion_rate' => $visits ? round($orders/$visits,4) : 0,
        ]);
    }
}
