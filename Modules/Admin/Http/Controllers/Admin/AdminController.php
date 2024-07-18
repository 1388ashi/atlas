<?php
namespace Modules\Admin\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Entities\Admin;
use Modules\Admin\Http\Requests\AdminStoreRequest;
use Modules\Admin\Http\Requests\AdminUpdateRequest;
use Modules\Permission\Entities\Role;

class AdminController extends \Shetabit\Shopit\Modules\Admin\Http\Controllers\Admin\AdminController
{
    public function webIndex(): Renderable
    {
        $admins = Admin::query()->latest()->paginate();

        return view('admin::admin.index', compact('admins'));
    }
    public function webCreate(): Renderable
    {
        $roles = Role::select('id','name','label')->get();

        return view('admin::admin.create', compact('roles'));
    }
    public function webStore(AdminStoreRequest $request)
    {
        // dd($request->role);
        $admin = Admin::query()->create($request->all());
        $role =Role::findOrFail($request->role);

        $admin->assignRole($role);
        $data = [
            'status' => 'success',
            'message' => 'ادمین با موفقیت ثبت شد'
        ];

        return redirect()->route('admin.admins.index')
        ->with($data);
    }

    public function webEdit(Admin $admin): Renderable
    {
        $adminRolesName = $admin->getRoleNames()->first();

        if ($adminRolesName == 'super_admin') {
            $roles = Role::select('id','name','label')->where('name','super_admin')->get();
        }else{
            $roles = Role::select('id', 'name', 'label')->where('name', '!=', 'super_admin')->get();
        }
        return view('admin::admin.edit', compact('roles','adminRolesName','admin'));
    }
    public function webUpdate(AdminUpdateRequest $request, Admin $admin)
    {
        $password = filled($request->password) ? $request->password : $admin->password;

        $admin->update([
            'name' => $request->name,
            'username' => $request->username,
            'mobile' => $request->mobile,
            'password' => Hash::make($password),
        ]);
        $role =Role::findOrFail($request->role);
        $admin->assignRole($role);

        $data = [
            'status' => 'success',
            'message' => 'ادمین با موفقیت به روزرسانی شد'
        ];

        return redirect()->route('admin.admins.index')
        ->with($data);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function webDestroy($id)
    {
        $admin = Admin::findOrFail($id);
        $admin->roles()->detach();
        $admin->delete();

        $data = [
            'status' => 'success',
            'message' => 'ادمین با موفقیت حذف شد'
        ];

        return redirect()->route('admin.admins.index')
            ->with($data);
    }
}
