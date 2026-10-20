<?php

namespace App\Modules\Admin\User\Services;

use App\Core\Filters\UsersFilter;
use App\Core\Traits\HasSorting;
use App\Modules\Admin\Role\Models\Role;
use App\Modules\Admin\User\Models\User;
use App\Modules\Admin\User\Requests\ListUsersRequest;
use App\Modules\Admin\User\Requests\UserRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    use HasSorting;

    public function listUsers(ListUsersRequest $request, UsersFilter $filter): LengthAwarePaginator
    {
        $query = User::filter($filter)->with(['roles:id,title', 'subdivisions:id,title']);
        $this->applySorting($query, $request->input('sort') ?: 'id');

        return $query->orderBy('id')->paginate($request->integer('page_size', 20))
            ->through(fn (User $user) => $this->withDetails($user));
    }

    public function withDetails(User $user): User
    {
        $user->loadMissing(['roles:id,title', 'subdivisions:id,title']);
        $user->role_id = $user->roles->first()?->id;
        $user->rolename = $user->roles->first()?->title ?? '';
        $user->subdivision_ids = $user->subdivisions->modelKeys();

        return $user;
    }

    public function getUsers($status = false)
    {
        $usersBuilder = User::with('roles');
        if ($status) {
            $usersBuilder->where('status', (string) $status);
        }
        $users = $usersBuilder->get();
        $users->transform(function ($item) {
            $item->rolename = '';
            if (isset($item->roles)) {
                $item->rolename = isset($item->roles->first()->title) ? $item->roles->first()->title : '';
            }

            return $item;
        });

        return $users;
    }

    public function save(UserRequest $request, User $user)
    {
        return DB::transaction(function () use ($request, $user) {
            $passwordReset = $user->exists && $request->filled('password');
            $user->fill($request->only($user->getFillable()));

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            if (! $user->exists && ! $request->has('status')) {
                $user->status = '1';
            }

            $user->save();

            $role = Role::findOrFail($request->role_id);
            $user->roles()->sync([$role->id]);
            if ($request->exists('subdivision_ids')) {
                $user->subdivisions()->sync($request->input('subdivision_ids'));
            }

            if ($passwordReset) {
                DB::table('oauth_refresh_tokens')
                    ->whereIn('access_token_id', $user->tokens()->select('id'))
                    ->update(['revoked' => true]);
                $user->tokens()->update(['revoked' => true]);
            }

            $user->rolename = $role->title;

            return $this->withDetails($user->unsetRelation('roles')->unsetRelation('subdivisions'));
        });
    }
}
