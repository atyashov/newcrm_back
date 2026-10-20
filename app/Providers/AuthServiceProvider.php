<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Modules\Admin\User\Models\User;
use App\Modules\Admin\Borrowings\Models\Borrowing;
use App\Modules\Admin\Borrowings\Policies\BorrowingPolicy;
use App\Modules\Admin\CashBoxes\Models\CashBox;
use App\Modules\Admin\CashBoxes\Policies\CashBoxPolicy;
use App\Modules\Admin\Clients\Models\Individual;
use App\Modules\Admin\Clients\Policies\IndividualPolicy;
use App\Modules\Admin\ClientTypes\Models\ClientType;
use App\Modules\Admin\ClientTypes\Policies\ClientTypePolicy;
use App\Modules\Admin\ExpenseGroups\Models\ExpenseGroup;
use App\Modules\Admin\ExpenseGroups\Policies\ExpenseGroupPolicy;
use App\Modules\Admin\Expenses\Models\Expense;
use App\Modules\Admin\Expenses\Policies\ExpensePolicy;
use App\Modules\Admin\OutlayGroupNames\Models\OutlayGroupName;
use App\Modules\Admin\OutlayGroupNames\Policies\OutlayGroupNamePolicy;
use App\Modules\Admin\Outlays\Models\Outlay;
use App\Modules\Admin\Outlays\Policies\OutlayPolicy;
use App\Modules\Admin\Products\Models\Category;
use App\Modules\Admin\Products\Models\Product;
use App\Modules\Admin\Products\Policies\ProductPolicy;
use App\Modules\Admin\Role\Models\Permission;
use App\Modules\Admin\Role\Models\Role;
use App\Modules\Admin\Role\Policies\PermissionPolicy;
use App\Modules\Admin\Role\Policies\RolePolicy;
use App\Modules\Admin\Statuses\Models\Status;
use App\Modules\Admin\Statuses\Policies\StatusPolicy;
use App\Modules\Admin\Stores\Models\Store;
use App\Modules\Admin\Stores\Policies\StorePolicy;
use App\Modules\Admin\Subdivisions\Models\Subdivision;
use App\Modules\Admin\Subdivisions\Policies\SubdivisionPolicy;
use App\Modules\Admin\User\Policies\UserPolicy;
use App\Modules\Admin\Warehouses\Models\ProductStock;
use App\Modules\Admin\Warehouses\Policies\ProductStockPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

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
        Status::class => StatusPolicy::class,
        Store::class => StorePolicy::class,
        Individual::class => IndividualPolicy::class,
        ClientType::class => ClientTypePolicy::class,
        Product::class => ProductPolicy::class,
        Category::class => ProductPolicy::class,
        ExpenseGroup::class => ExpenseGroupPolicy::class,
        Expense::class => ExpensePolicy::class,
        OutlayGroupName::class => OutlayGroupNamePolicy::class,
        CashBox::class => CashBoxPolicy::class,
        Borrowing::class => BorrowingPolicy::class,
        Outlay::class => OutlayPolicy::class,
        ProductStock::class => ProductStockPolicy::class,
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
