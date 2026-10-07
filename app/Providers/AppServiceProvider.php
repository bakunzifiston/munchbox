<?php

namespace App\Providers;

use App\Models\Order;
use App\Policies\OrderPolicy;
use App\Services\CartService;
use App\View\Composers\CartComposer;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CartService::class);
    }

    public function boot(): void
    {
        Gate::policy(Order::class, OrderPolicy::class);

        Gate::define('access-admin', fn ($user) => $user->isStaff());
        Gate::define('manage-users', fn ($user) => $user->isAdmin());

        Blade::directive('rwf', function ($expression) {
            return "<?php echo \\App\\Support\\Money::format($expression); ?>";
        });

        View::composer('layouts.customer', CartComposer::class);
    }
}
