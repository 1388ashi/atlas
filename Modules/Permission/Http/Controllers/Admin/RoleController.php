<?php

namespace Modules\Permission\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Modules\Permission\Entities\Role;
use Modules\Permission\Http\Requests\Admin\RoleStoreRequest;
use Modules\Permission\Http\Requests\Admin\RoleUpdateRequest;
use Shetabit\Shopit\Modules\Permission\Http\Controllers\Admin\RoleController as BaseRoleController;
use Illuminate\Database\Eloquent\Collection;
use Modules\Admin\Entities\Admin;
use Modules\Core\Entities\Permission;

use Illuminate\Contracts\Support\Renderable;

class RoleController extends BaseRoleController
{
    private function permissions(): Collection
    {
        return Permission::query()
            ->oldest('id')
            ->select(['id', 'name', 'label'])
            ->get();
    }
    public function webIndex()
    {
        $roles = Role::query()
        ->latest('id')
        ->select(['id', 'name', 'label', 'created_at'])
        ->with('permissions')
        ->paginate(15);

        return view('permission::admin.role.index', compact('roles'));
    }
    public function webCreate(): Renderable
    {
        $permissions = $this->permissions();

        return view('permission::admin.role.create', compact('permissions'));
    }
    /**
     * Store a newly created resource in storage.
     * @param RoleStoreRequest $request
     */
    public function webStore(RoleStoreRequest $request) : RedirectResponse
    {
        $role = Role::query()->create($request->only('name', 'label', 'guard_name'));
        $role->givePermissionTo($request->permissions);

        return redirect()->route('admin.roles.index')
        ->with('success', 'نقش با موفقیت ثبت شد.');
    }
    public function webEdit(Role $role)
    {
        // if ($role->name == 'super_admin') {
        //     Auth::logout();
        //     return redirect('/login');
        // }
        $permissions = $this->permissions();

        return view('permission::admin.role.edit', compact('permissions', 'role'));
    }

    /**
     * Update the specified resource in storage.
     * @param RoleUpdateRequest $request
     * @param int $id
     */
    public function webUpdate(RoleUpdateRequest $request,Role $role)
    {
        $role->update($request->only('name', 'label', 'guard_name'));
        $role->syncPermissions($request->permissions);

        return redirect()->route('admin.roles.index')
        ->with('success', 'نقش با موفقیت بروزرسانی شد.');
    }
    public function show(){}

    /**
     * Remove the specified resource from storage.
     * @param int $id
     */
    public function webDestroy(Role $role)
    {
        if (!empty($role->admins['items'])) {
            $data = [
                'status' => 'danger',
                'message' => 'نقش به ادمینی وصل هست'
            ];
            return redirect()->route('admin.roles.index')->with($data);
        }
        $permissions = $role->permissions;
        if ($role->delete()) {
            foreach ($permissions as $permission) {
                $role->revokePermissionTo($permission);
            }
        }

        return redirect()->route('admin.roles.index')
        ->with('success', 'نقش با موفقیت حذف شد.');
    }
}
