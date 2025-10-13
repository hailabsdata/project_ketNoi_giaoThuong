<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */

    public function boot()
    {
        Paginator::useBootstrapFive();

        // Clamp per_page (1..100), mặc định 20
        Request::macro('perPage', function () {
            $n = (int) request('per_page', 20);
            return max(1, min(100, $n));
        });

        // Macro trả về trang chuẩn: data + meta + links
        Response::macro('page', function ($p) {
            return response()->json([
                'data'  => $p->items(),
                'meta'  => [
                    'current_page' => $p->currentPage(),
                    'per_page'     => $p->perPage(),
                    'total'        => $p->total(),
                    'last_page'    => $p->lastPage(),
                ],
                'links' => [
                    'next' => $p->nextPageUrl(),
                    'prev' => $p->previousPageUrl(),
                ],
            ]);
        });
    }
}
