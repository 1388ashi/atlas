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

class AdminController extends Controller
{
    public function index(): Renderable
    {
        $admins = Admin::query()->latest()->paginate();

        return view('admin::admin.index', compact('admins'));
    }
    public function create(): Renderable
    {
        $roles = Role::select('id','name','label')->get();

        return view('admin::admin.create', compact('roles'));
    }
    public function store(AdminStoreRequest $request)
    {
        $admin = Admin::query()->create($request->all());
        $admin->assignRole($request->role);
        $data = [
            'status' => 'success',
            'message' => 'ادمین با موفقیت ثبت شد'
        ];

        return redirect()->route('admin.admins.index')
        ->with($data);
    }

    public function edit(Admin $admin): Renderable
    {
        $adminRolesName = $admin->getRoleNames()->first();

        if ($adminRolesName == 'super_admin') {
            $roles = Role::select('id','name','label')->where('name','super_admin')->get();
        }else{
            $roles = Role::select('id','name','label')->whereNot('name','super_admin')->get();

        }
        return view('admin::admin.edit', compact('roles','adminRolesName','admin'));
    }
    public function update(AdminUpdateRequest $request, Admin $admin)
    {
        if (is_null($request->status)) {
            $request->status = false;
        }

        $password = filled($request->password) ? $request->password : $admin->password;

        $admin->update([
            'name' => $request->name,
            'mobile' => $request->mobile,
            'password' => Hash::make($password),
            'status' => $request->status
        ]);
        $admin->assignRole($request->role);

        Auth::logout();
        return redirect('/login');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Admin $admin)
    {
        $role = $admin->getRoleNames()->first();
        $admin->removeRole($role);
        $admin->delete();

        $data = [
            'status' => 'success',
            'message' => 'ادمین با موفقیت حذف شد'
        ];

        return redirect()->route('admin.admins.index')
            ->with($data);
    }
}
