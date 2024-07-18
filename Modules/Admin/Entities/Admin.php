<?php

namespace Modules\Admin\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Shetabit\Shopit\Modules\Admin\Database\factories\AdminFactory;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Modules\Permission\Entities\Role as ModelRole;

class Admin extends Authenticatable implements \Modules\Core\Contracts\Notifiable
{
    use HasFactory, HasApiTokens, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'password',
        'email',
        'mobile',
    ];
    public function getRole()
    {
        return $this->roles?->first();
    }
    protected $appends = ['role'];

    protected $hidden = ['roles', 'updater', 'password', 'remember_token'];

    public function isDeletable(): bool
    {
        return !$this->getRole() || $this->getRole()->name !== ModelRole::SUPER_ADMIN;
    }

    public function isSuperAdmin()
    {
        return $this->hasRole('super_admin');
    }

    public static function newFactory()
    {
        return AdminFactory::new();
    }
}
