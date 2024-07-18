<?php

namespace Modules\Admin\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Modules\Core\Helpers\Helpers;
use Modules\Permission\Entities\Role;
use Shetabit\Shopit\Modules\Admin\Database\factories\AdminFactory;
use Shetabit\Shopit\Modules\Customer\Entities\Customer;
use Spatie\Permission\Traits\HasRoles;
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

    protected static function booted()
    {
        parent::booted();
        static::updating(function($admin){
            if (auth()->user() && !auth()->user()->hasRole('super_admin') && $admin->hasRole('super_admin')){
                return throw Helpers::makeValidationException('شما مجاز به ویرایش سوپر ادمین نمیباشید');
            }
        });
    }
    public function isDeletable(): bool
    {
        return !$this->getRole() || $this->getRole()->name !== ModelRole::SUPER_ADMIN;
    }
    public function setPasswordAttribute($value)
    {
        if ($value != null){
            $this->attributes['password'] = bcrypt($value);
        }
    }

    public function getRoleAttribute()
    {
        $roles = $this->roles;
        if (empty($roles)) {
            return null;
        }
        return  $roles->first();
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
