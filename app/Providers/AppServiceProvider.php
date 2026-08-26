<?php

namespace App\Providers;

use App\Models\Blocklist;
use App\Models\CompanyCollaboratorType;
use App\Models\CompanyJobTitle;
use App\Models\Employee;
use App\Models\Resident;
use App\Models\SecurityCompany;
use App\Models\SupervisorChecklistItem;
use App\Models\SupervisorShiftTemplate;
use App\Models\SupervisorZone;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Visitor;
use App\Policies\CompanyCollaboratorTypePolicy;
use App\Policies\CompanyJobTitlePolicy;
use App\Policies\EmployeePolicy;
use App\Policies\SecurityCompanyPolicy;
use App\Policies\SupervisorCompanyCatalogPolicy;
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
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(CompanyJobTitle::class, CompanyJobTitlePolicy::class);
        Gate::policy(CompanyCollaboratorType::class, CompanyCollaboratorTypePolicy::class);
        Gate::policy(SupervisorZone::class, SupervisorCompanyCatalogPolicy::class);
        Gate::policy(SupervisorShiftTemplate::class, SupervisorCompanyCatalogPolicy::class);
        Gate::policy(SupervisorChecklistItem::class, SupervisorCompanyCatalogPolicy::class);

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
