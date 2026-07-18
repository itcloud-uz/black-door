<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Models\Obj;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Gates configuration
        Gate::define('access-finance', function (User $user) {
            return $user->role === UserRole::SuperAdmin || $user->role === UserRole::Financier;
        });

        Gate::define('access-admin', function (User $user) {
            return $user->role === UserRole::SuperAdmin;
        });

        Gate::define('access-manager', function (User $user) {
            return $user->role === UserRole::Manager;
        });

        Gate::define('access-employee', function (User $user) {
            return $user->role === UserRole::Employee;
        });

        Gate::define('manage-object', function (User $user, Obj $object) {
            if ($user->role === UserRole::SuperAdmin) {
                return true;
            }

            if ($user->role === UserRole::Manager) {
                // Check if this manager is assigned to the object
                $managerAssignment = $object->currentManager;
                return $managerAssignment && (int)$managerAssignment->user_id === (int)$user->id;
            }

            if ($user->role === UserRole::Employee) {
                // Check if employee belongs to this object
                $employeeAssignment = $user->objectEmployee;
                return $employeeAssignment && (int)$employeeAssignment->object_id === (int)$object->id;
            }

            return false;
        });
    }
}
