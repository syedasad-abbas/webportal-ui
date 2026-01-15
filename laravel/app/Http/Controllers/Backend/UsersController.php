<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Enums\ActionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\RolesService;
use App\Services\UserService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly RolesService $rolesService
    ) {}

    public function index(): Renderable
    {
        $this->checkAuthorization(Auth::user(), ['user.view']);

        $filters = [
            'search' => request('search'),
            'role' => request('role'),
            'sort_field' => null,
            'sort_direction' => null,
        ];

        $currentUser = Auth::user();

        return view('backend.pages.users.index', [
            'users' => $this->userService->getUsers($filters, $currentUser),
            'roles' => $this->rolesService->getRolesDropdown(),
            'breadcrumbs' => [
                'title' => __('Users'),
            ],
        ]);
    }

    public function create(): Renderable
    {
        $this->checkAuthorization(Auth::user(), ['user.create']);

        ld_do_action('user_create_page_before');

        // NEW: these are needed by the new UI fields
        // If you already have models, swap these to Group::... / Carrier::...
        $groups = method_exists($this->userService, 'getGroupsDropdown')
            ? $this->userService->getGroupsDropdown()
            : collect();

        $carriers = method_exists($this->userService, 'getCarriersDropdown')
            ? $this->userService->getCarriersDropdown()
            : collect();

        return view('backend.pages.users.create', [
            // kept for compatibility (you might still use it elsewhere)
            'roles' => $this->rolesService->getRolesDropdown(),

            // NEW for UI
            'groups' => $groups,
            'carriers' => $carriers,

            'breadcrumbs' => [
                'title' => __('New User'),
                'items' => [
                    [
                        'label' => __('Users'),
                        'url' => route('admin.users.index'),
                    ],
                ],
            ],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        // Your StoreUserRequest should validate fullName/email/password/groupId/carrierId
        // (update that request accordingly)
        $user = new User();

        // Map new UI fields into your User model
        $user->external_name = $request->input('fullName'); // show name
        $user->email = $request->input('email');
        $user->password = Hash::make($request->input('password'));

        // Optional fields (if columns exist)
        if ($request->filled('groupId')) {
            $user->group_id = $request->input('groupId');
        }

        if ($request->filled('carrierId')) {
            $user->carrier_id = $request->input('carrierId');
        }

        // If your create form doesn't have recording, it will default false/0
        if ($request->has('recording_enabled') || $request->has('recordingEnabled')) {
            $user->recording_enabled = $request->boolean('recording_enabled')
                ?: $request->boolean('recordingEnabled');
        }

        // Keep existing hooks
        $user = ld_apply_filters('user_store_before_save', $user, $request);
        $user->save();
        /** @var User $user */
        $user = ld_apply_filters('user_store_after_save', $user, $request);

        // Roles removed from UI, so do nothing unless you still want to support it
        // If you want to keep it:
        // if ($request->roles) { ... }

        $this->storeActionLog(ActionType::CREATED, ['user' => $user]);
        session()->flash('success', __('User has been created.'));

        ld_do_action('user_store_after', $user);

        return redirect()->route('admin.users.index');
    }

    public function edit(int $id): Renderable
    {
        $this->checkAuthorization(Auth::user(), ['user.edit']);

        $user = User::findOrFail($id);

        ld_do_action('user_edit_page_before');
        $user = ld_apply_filters('user_edit_page_before_with_user', $user);

        // NEW: reference data for dropdowns
        $groups = method_exists($this->userService, 'getGroupsDropdown')
            ? $this->userService->getGroupsDropdown()
            : collect();

        $carriers = method_exists($this->userService, 'getCarriersDropdown')
            ? $this->userService->getCarriersDropdown()
            : collect();

        return view('backend.pages.users.edit', [
            'user' => $user,

            // kept for compatibility
            'roles' => $this->rolesService->getRolesDropdown(),

            // NEW for UI
            'groups' => $groups,
            'carriers' => $carriers,

            'breadcrumbs' => [
                'title' => __('Edit User'),
                'items' => [
                    [
                        'label' => __('Users'),
                        'url' => route('admin.users.index'),
                    ],
                ],
            ],
        ]);
    }

    public function update(UpdateUserRequest $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $this->preventSuperAdminModification($user);

        // Agents/support: keep your original rule (password-only)
        if (auth()->user()->hasRole('Agent') || auth()->user()->hasRole('support')) {
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);

                $user = ld_apply_filters('user_update_before_save', $user, $request);
                $user->save();

                $user = ld_apply_filters('user_update_after_save', $user, $request);
                ld_do_action('user_update_after', $user);

                $this->storeActionLog(ActionType::UPDATED, ['user' => $user]);
                session()->flash('success', __('Password has been updated.'));

                return back();
            }

            session()->flash('info', __('No changes were made.'));
            return back();
        }

        // Admin: update using NEW UI fields
        $user->external_name = $request->input('fullName');
        $user->email = $request->input('email');

        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
        }

        // Optional dropdown values
        $user->group_id = $request->input('groupId') ?: null;
        $user->carrier_id = $request->input('carrierId') ?: null;

        // Recording: accept either recording_enabled or recordingEnabled
        if ($request->has('recording_enabled') || $request->has('recordingEnabled')) {
            $user->recording_enabled = $request->boolean('recording_enabled')
                ?: $request->boolean('recordingEnabled');
        }

        $user = ld_apply_filters('user_update_before_save', $user, $request);
        $user->save();

        $user = ld_apply_filters('user_update_after_save', $user, $request);
        ld_do_action('user_update_after', $user);

        // Roles removed from UI: do nothing unless you still need it
        // If you want to keep role updates, uncomment:
        /*
        $user->roles()->detach();
        if ($request->roles) {
            $roles = array_filter($request->roles);
            $user->assignRole($roles);
        }
        */

        $this->storeActionLog(ActionType::UPDATED, ['user' => $user]);
        session()->flash('success', __('User has been updated.'));

        return back();
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->checkAuthorization(Auth::user(), ['user.delete']);
        $user = $this->userService->getUserById($id);

        $this->preventSuperAdminModification($user);

        if (Auth::id() === $user->id) {
            session()->flash('error', __('You cannot delete your own account.'));
            return back();
        }

        $user = ld_apply_filters('user_delete_before', $user);
        $user->delete();
        $user = ld_apply_filters('user_delete_after', $user);

        session()->flash('success', __('User has been deleted.'));
        $this->storeActionLog(ActionType::DELETED, ['user' => $user]);
        ld_do_action('user_delete_after', $user);

        return back();
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $this->checkAuthorization(Auth::user(), ['user.delete']);

        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('admin.users.index')
                ->with('error', __('No users selected for deletion'));
        }

        if (in_array(Auth::id(), $ids)) {
            $ids = array_filter($ids, fn ($id) => $id != Auth::id());
            session()->flash('error', __('You cannot delete your own account. Other selected users will be processed.'));

            if (empty($ids)) {
                return redirect()->route('admin.users.index')
                    ->with('error', __('No users were deleted.'));
            }
        }

        $users = User::whereIn('id', $ids)->get();
        $deletedCount = 0;

        foreach ($users as $user) {
            if ($user->hasRole('superadmin')) {
                continue;
            }

            $user = ld_apply_filters('user_delete_before', $user);
            $user->delete();
            ld_apply_filters('user_delete_after', $user);

            $this->storeActionLog(ActionType::DELETED, ['user' => $user]);
            ld_do_action('user_delete_after', $user);

            $deletedCount++;
        }

        if ($deletedCount > 0) {
            session()->flash('success', __(':count users deleted successfully', ['count' => $deletedCount]));
        } else {
            session()->flash('error', __('No users were deleted. Selected users may include protected accounts.'));
        }

        return redirect()->route('admin.users.index');
    }
}
