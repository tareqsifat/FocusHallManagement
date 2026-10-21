<?php

namespace App\Providers;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        // Hall structure (blocks, rooms, seats) is not owned by anyone, so plain gates fit better than a policy.
        Gate::define('view-hall', fn (User $user) => $user->isHallAdmin());
        Gate::define('manage-hall', fn (User $user) => $user->hasRole(RoleName::OfficeStaff, RoleName::SuperAdmin));
    }
}
