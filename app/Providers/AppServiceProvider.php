<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use App\Services\Payments\MockGateway;
use App\Services\Payments\PaymentService;
use App\Services\Payments\StripeGateway;
use App\Services\SiteContent;
use App\Support\Permissions;
use App\View\Composers\AdminComposer;
use App\View\Composers\SiteComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentService::class, fn ($app) => new PaymentService([
            'mock' => $app->make(MockGateway::class),
            'stripe' => $app->make(StripeGateway::class),
        ]));
    }

    public function boot(): void
    {
        // Fail loudly in development on N+1 lazy loading and silently-dropped attributes.
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Paginator::useBootstrapFive();

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->letters()->mixedCase()->numbers()->uncompromised()
            : Password::min(8)->letters()->numbers());

        $this->registerGates();
        $this->registerRateLimits();
        $this->applyBusinessSettings();

        View::composer(['components.layouts.site', 'site.*', 'account.*'], SiteComposer::class);
        View::composer('components.layouts.admin', AdminComposer::class);
    }

    private function registerGates(): void
    {
        // Deactivated accounts can do nothing, whatever their role.
        Gate::before(function (User $user) {
            if (! $user->is_active) {
                return false;
            }

            return $user->hasRole(Role::SuperAdmin) ? true : null;
        });

        foreach (array_keys(Permissions::MAP) as $ability) {
            Gate::define($ability, fn (User $user) => Permissions::allows($user->roleEnum(), $ability));
        }
    }

    /**
     * Settings saved by a Super Admin override the .env defaults for pricing. Customer
     * emails are sent in the café's name; APP_NAME stays the platform name (CafeFlow).
     */
    private function applyBusinessSettings(): void
    {
        config(['cafeflow.business_name' => SiteContent::DEFAULTS['business']['name']]);

        try {
            $business = $this->app->make(SiteContent::class)->get('business', []);
        } catch (\Throwable) {
            return; // database not ready yet (fresh install, migrations running)
        }

        if (filled($business['name'] ?? null)) {
            config(['cafeflow.business_name' => $business['name']]);
        }
        config(['mail.from.name' => config('cafeflow.business_name')]);

        foreach (['tax_rate', 'delivery_fee', 'free_delivery_over', 'min_delivery_order', 'low_stock_email'] as $key) {
            if (array_key_exists($key, $business) && $business[$key] !== null) {
                config(["cafeflow.{$key}" => $business[$key]]);
            }
        }
    }

    private function registerRateLimits(): void
    {
        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(60)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('auth', fn (Request $r) => [
            Limit::perMinute(10)->by('ip:'.$r->ip()),
            Limit::perMinute(5)->by('email:'.strtolower((string) $r->input('email')).'|'.$r->ip()),
        ]);
        RateLimiter::for('checkout', fn (Request $r) => Limit::perMinute(10)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('webhooks', fn (Request $r) => Limit::perMinute(120)->by($r->ip()));
        RateLimiter::for('cart', fn (Request $r) => Limit::perMinute(90)->by($r->session()?->getId() ?: $r->ip()));
    }
}
