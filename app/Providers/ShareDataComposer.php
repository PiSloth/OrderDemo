<?php

namespace App\Providers;

use App\Models\CommentPool;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ShareDataComposer extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            //Only relevant i meeting count for other user
            $view->with('relevantMeetingCount', CommentPool::where('completed', false)
                ->where('user_id', auth()->id())
                ->count());
            // $view->with('relevantMeetingCount', 0);
            // $view->with('agmMeetingCount', 0);


            //i meeting count for agm
            $view->with('agmMeetingCount', CommentPool::where('completed', false)
                ->count());
        });
    }
}
