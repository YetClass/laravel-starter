<?php

namespace Modules\Admin\Controllers;

use Illuminate\Http\Request;
use Modules\Admin\Utils\Admin;
use Modules\Admin\Entities\AdminRole;
use Modules\Admin\Entities\AdminUser;
use Modules\Admin\Filters\AdminUserFilter;
use Modules\Admin\Entities\AdminPermission;
use Modules\Admin\Requests\AdminUserRequest;
use Modules\Admin\Resources\AdminUserResource;
use Modules\Common\Controllers\BaseController;
use Modules\Admin\Requests\AdminUserProfileRequest;

class AdminUserController extends BaseController
{

    // 后台用户列表
    public function index(AdminUserFilter $filter)
    {
        $users = AdminUser::query()
            ->filter($filter)
            ->with(['roles', 'permissions'])
            ->orderByDesc('id')
            ->paginate();

        return $this->okObject(AdminUserResource::collection($users));
    }

    // 添加后台用户
    public function store(AdminUserRequest $request, AdminUser $user)
    {
        $inputs = $request->validated();
        $user = $user::createUser($inputs);

        if (!empty($q = $request->post('roles', []))) {
            $user->roles()->attach($q);
        }
        if (!empty($q = $request->post('permissions', []))) {
            $user->permissions()->attach($q);
        }

        return $this->created(AdminUserResource::make($user));
    }



    // 展示后台用户详情
    public function show(AdminUser $adminUser)
    {
        $adminUser->load(['roles', 'permissions']);

        return $this->okObject(AdminUserResource::make($adminUser));
    }


    public function update(AdminUserRequest $request, AdminUser $adminUser)
    {
        $inputs = $request->validated();
        $adminUser->updateUser($inputs);
        if (isset($inputs['roles'])) {
            $adminUser->roles()->sync($inputs['roles']);
        }
        if (isset($inputs['permissions'])) {
            $adminUser->permissions()->sync($inputs['permissions']);
        }

        return $this->created(AdminUserResource::make($adminUser));
    }

    public function destroy(AdminUser $adminUser)
    {
        $adminUser->delete();

        return $this->noContent();
    }

    public function edit(Request $request, AdminUser $adminUser)
    {
        $formData = $this->formData();

        $adminUser->load(['roles', 'permissions']);
        $adminUserData = AdminUserResource::make($adminUser)
            ->onlyRolePermissionIds()
            ->toArray($request);

        return $this->okList(array_merge($formData, [
            'admin_user' => $adminUserData,
        ]));
    }

    public function create()
    {
        return $this->okList($this->formData());
    }

    /**
     * 返回创建和编辑表单所需的选项数据.
     *
     * @return array
     */
    protected function formData()
    {
        $roles = AdminRole::query()
            ->orderByDesc('id')
            ->get();
        $permissions = AdminPermission::query()
            ->orderByDesc('id')
            ->get();

        return compact('roles', 'permissions');
    }
}
