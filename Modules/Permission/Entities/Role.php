<?php

namespace Modules\Permission\Entities;

use DateTimeInterface;
use Modules\Admin\Entities\Admin;
use Shetabit\Shopit\Modules\Core\Exceptions\ModelCannotBeDeletedException;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Guard;
use Modules\Core\Contracts\Role as RoleContract;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole implements RoleContract
{
    public const SUPER_ADMIN = 'super_admin';

    protected $fillable = [
        'name',
        'label',
        'guard_name'
    ];

    public array $sortable = [
        'id',
        'name',
        'label',
        'created_at',
    ];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public static function customFindOrCreate(string $name, string $label, ?string $guardName = null): RoleContract
    {
        $guardName = $guardName ?? Guard::getDefaultName(static::class);

        $role = static::where('name', $name)->where('guard_name', $guardName)->first();

        if (! $role) {
            return static::query()->create(['name' => $name, 'label' => $label, 'guard_name' => $guardName]);
        }

        return $role;
    }

	public static function booted(): void
	{
		static::deleting(function (Role $role) {
			$superAdmin = static::SUPER_ADMIN;
			if ($role->name === static::SUPER_ADMIN) {
				throw new ModelCannotBeDeletedException("نقش {$superAdmin} قابل حذف نمی باشد.");
			}
			if ($role->admins()->exists()) {
				throw new ModelCannotBeDeletedException("نقش {$role->label} به کاربر یا کاربرانی نسبت داده شده و قابل حذف نمی باشد.");
			}
		});
	}

	public function admins(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            Admin::class,
            'model_has_roles',
            'model_id',
            'role_id',
        );
    }

}
