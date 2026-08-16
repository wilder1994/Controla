<?php

namespace App\Providers;

use App\Models\Blocklist;
use App\Models\Resident;
use App\Models\SecurityCompany;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Visitor;
use App\Policies\SecurityCompanyPolicy;
use App\Policies\UserPolicy;
use App\Support\Tenancy\TenantContext;
use App\View\Composers\CompanyLayoutComposer;
use App\View\Composers\OperateReturnLayoutComposer;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
    }

    public function boot(): void
    {
        Relation::morphMap([
            Blocklist::TYPE_VISITOR => Visitor::class,
            Blocklist::TYPE_VEHICLE => Vehicle::class,
            Blocklist::TYPE_RESIDENT => Resident::class,
        ]);

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(SecurityCompany::class, SecurityCompanyPolicy::class);

        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->hasRole('super-admin')) {
                return true;
            }

            return null;
        });

        View::composer('layouts.company', CompanyLayoutComposer::class);
        View::composer(['layouts.access', 'layouts.client'], OperateReturnLayoutComposer::class);
    }
}
