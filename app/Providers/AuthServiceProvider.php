<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Modules\Admin\Clients\Models\Individual;
use App\Modules\Admin\Clients\Policies\IndividualPolicy;
use App\Modules\Admin\ClientTypes\Models\ClientType;
use App\Modules\Admin\ClientTypes\Policies\ClientTypePolicy;
use App\Modules\Admin\Products\Models\Category;
use App\Modules\Admin\Products\Models\Product;
use App\Modules\Admin\Products\Policies\ProductPolicy;
use App\Modules\Admin\Role\Models\Role;
use App\Modules\Admin\Role\Policies\RolePolicy;
use App\Modules\Admin\Role\Models\Permission;
use App\Modules\Admin\Role\Policies\PermissionPolicy;
use App\Modules\Admin\Subdivisions\Models\Subdivision;
use App\Modules\Admin\Subdivisions\Policies\SubdivisionPolicy;
use App\Modules\Admin\User\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Role::class => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
        User::class => UserPolicy::class,
        Subdivision::class => SubdivisionPolicy::class,
        Individual::class => IndividualPolicy::class,
        ClientType::class => ClientTypePolicy::class,
        Product::class => ProductPolicy::class,
        Category::class => ProductPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
