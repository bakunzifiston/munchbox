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

        // Staff run day-to-day operations; admins also manage the menu, money, customers and people.
        Gate::define('access-admin', fn ($user) => $user->isStaff());
        Gate::define('manage-orders', fn ($user) => $user->isStaff());
        Gate::define('manage-stock', fn ($user) => $user->isStaff());
        Gate::define('manage-catalog', fn ($user) => $user->isAdmin());
        Gate::define('view-financials', fn ($user) => $user->isAdmin());
        Gate::define('view-customers', fn ($user) => $user->isAdmin());
        Gate::define('manage-settings', fn ($user) => $user->isAdmin());
        Gate::define('manage-users', fn ($user) => $user->isAdmin());

        Blade::directive('rwf', function ($expression) {
            return "<?php echo \\App\\Support\\Money::format($expression); ?>";
        });

        View::composer('layouts.customer', CartComposer::class);
    }
}
