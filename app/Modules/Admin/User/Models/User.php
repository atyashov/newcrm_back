<?php

namespace App\Modules\Admin\User\Models;

use App\Modules\Admin\Role\Models\Traits\UserRoles;
use App\Modules\Admin\Subdivisions\Models\Subdivision;
use App\Modules\Admin\Subdivisions\Models\UserSubdivision;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Contracts\OAuthenticatable;

class User extends Authenticatable implements OAuthenticatable
{
    use HasFactory, HasApiTokens, UserRoles;

    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'phone',
        'status',
    ];

    protected $hidden = [
        'password'
    ];

    public $subdivision_id;

    public function userSubdivisions(): HasMany
    {
        return $this->hasMany(UserSubdivision::class);
    }

    public function subdivisions(): BelongsToMany
    {
        return $this->belongsToMany(
            Subdivision::class,
            'users_subdivisions',
            'user_id',
            'subdivision_id'
        );
    }

    public function getAvailableSubdivisionIds(): array
    {
        if ($this->canDo('SUPER_ADMINISTRATOR')) {
            return Subdivision::query()->pluck('id')->map(static fn ($id) => (int) $id)->all();
        }

        return $this->subdivisions()
            ->pluck('subdivisions.id')
            ->map(static fn ($id) => (int) $id)
            ->all();
    }

    public function hasSubdivisionAccess(int|string|null $subdivisionId): bool
    {
        if (empty($subdivisionId)) {
            return false;
        }

        if ($this->canDo('SUPER_ADMINISTRATOR')) {
            return true;
        }

        return $this->subdivisions()
            ->where('subdivisions.id', (int) $subdivisionId)
            ->exists();
    }
}
