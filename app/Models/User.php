<?php

namespace App\Models;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * status and rejection_reason are deliberately not fillable; they change only through the approval flow.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function hasRole(RoleName ...$roles): bool
    {
        return $this->roles->contains(fn (Role $role) => in_array($role->name, $roles, true));
    }

    public function assignRole(RoleName ...$roles): void
    {
        $ids = Role::whereIn('name', array_map(fn (RoleName $r) => $r->value, $roles))->pluck('id');
        $this->roles()->syncWithoutDetaching($ids);
        $this->unsetRelation('roles');
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /** Office Staff, Provost or Super Admin: the roles that see every record. */
    public function isHallAdmin(): bool
    {
        return $this->hasRole(RoleName::OfficeStaff, RoleName::Provost, RoleName::SuperAdmin);
    }
}
