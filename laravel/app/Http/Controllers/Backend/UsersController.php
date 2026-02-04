<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Enums\ActionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Models\Carrier;
use App\Services\RolesService;
use App\Services\UserService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UsersController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly RolesService $rolesService
    ) {
    }

    public function index(): Renderable
    {
        return $this->renderUserIndex();
    }

    public function active(): Renderable
    {
        return $this->renderUserIndex(['status_filter' => 'active']);
    }

    public function offline(): Renderable
    {
        return $this->renderUserIndex(['status_filter' => 'offline']);
    }

    protected function renderUserIndex(array $options = []): Renderable
    {
        $this->checkAuthorization(Auth::user(), ['user.view']);

        $sort = (string) request('sort', '');
        $sortField = null;
        $sortDirection = null;

        if (!empty($sort)) {
            $sortDirection = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $sortField = ltrim($sort, '-');

            $allowed = ['external_name', 'email', 'user_id', 'is_active', 'created_at'];
            if (!in_array($sortField, $allowed, true)) {
                $sortField = null;
                $sortDirection = null;
            }
        }

        $statusFilter = $options['status_filter'] ?? request('filter');

        $filters = [
            'search' => request('search'),
            'role' => request('role'),
            'sort_field' => $sortField,
            'sort_direction' => $sortDirection,
        ];

        if ($statusFilter === 'active') {
            $filters['is_active'] = true;
        } elseif ($statusFilter === 'offline') {
            $filters['is_active'] = false;
        }

        $currentUser = Auth::user();

        return view('backend.pages.users.index', [
            'users' => $this->userService->getUsers($filters, $currentUser),
            'roles' => $this->rolesService->getRolesDropdown(),
            'statusFilter' => $statusFilter,
            'breadcrumbs' => [
                'title' => __('Users'),
            ],
        ]);
    }

    public function create(Request $request): Renderable
    {
        $this->checkAuthorization(Auth::user(), ['user.create']);

        ld_do_action('user_create_page_before');

        $carriers = $this->getCarriersForUserForms($request);

        return view('backend.pages.users.create', [
            'roles' => $this->rolesService->getRolesDropdown(),
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
        $this->checkAuthorization(Auth::user(), ['user.create']);

        $user = new User();

        // Full Name removed - use only external_name
        $user->external_name = $request->input('external_name');
        $user->internal_name = $request->input('internal_name');
        $user->email = $request->input('email');

        // Status
        $user->is_active = $request->boolean('is_active', true);

        // Carrier (optional) - unchanged
        if ($request->filled('carrierId')) {
            $user->carrier_id = $this->resolveCarrierId($request->input('carrierId'));
        } else {
            $user->carrier_id = null;
        }

        // Recording (optional) - unchanged
        if ($request->has('recording_enabled')) {
            $user->recording_enabled = $request->boolean('recording_enabled');
        }

        $user->password = Hash::make($request->input('password'));

        $user = ld_apply_filters('user_store_before_save', $user, $request);
        $user->save();
        /** @var User $user */
        $user = ld_apply_filters('user_store_after_save', $user, $request);
        $this->persistSipCredentials($request, $user);

        // Roles assignment
        if ($request->filled('roles')) {
            $roles = array_filter((array) $request->input('roles', []));
            if (!empty($roles)) {
                $user->assignRole($roles);
            }
        }

        $this->storeActionLog(ActionType::CREATED, ['user' => $user]);
        session()->flash('success', __('User has been created.'));

        ld_do_action('user_store_after', $user);
        $this->syncBackendUser($request, $user, $request->input('password'));

        return redirect()->route('admin.users.index');
    }

    public function edit(Request $request, int $id): Renderable
    {
        $this->checkAuthorization(Auth::user(), ['user.edit']);

        $user = User::findOrFail($id);

        ld_do_action('user_edit_page_before');
        $user = ld_apply_filters('user_edit_page_before_with_user', $user);

        $carriers = $this->getCarriersForUserForms($request);

        return view('backend.pages.users.edit', [
            'user' => $user,
            'roles' => $this->rolesService->getRolesDropdown(),
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
        $this->checkAuthorization(Auth::user(), ['user.edit']);

        $user = User::findOrFail($id);

        $this->preventSuperAdminModification($user);

        // Agent/support: password-only updates
        if (auth()->user()->hasRole('Agent') || auth()->user()->hasRole('support')) {
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);

                $user = ld_apply_filters('user_update_before_save', $user, $request);
                $user->save();
                $user = ld_apply_filters('user_update_after_save', $user, $request);

                ld_do_action('user_update_after', $user);

                $this->storeActionLog(ActionType::UPDATED, ['user' => $user]);
                session()->flash('success', __('Password has been updated.'));
                $this->syncBackendUser($request, $user, $request->password);

                return back();
            }

            session()->flash('info', __('No changes were made.'));
            return back();
        }

        // Admin/other roles: update everything
        // Full Name removed - use only external_name
        $user->external_name = $request->input('external_name');
        $user->internal_name = $request->input('internal_name');
        $user->email = $request->input('email');

        // Status
        if ($request->has('is_active')) {
            $user->is_active = $request->boolean('is_active');
        }

        // Carrier - unchanged
        if ($request->has('carrierId')) {
            $user->carrier_id = $request->filled('carrierId')
                ? $this->resolveCarrierId($request->input('carrierId'))
                : null;
        }

        // Recording - unchanged
        if ($request->has('recording_enabled')) {
            $user->recording_enabled = $request->boolean('recording_enabled');
        }

        // Password optional
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user = ld_apply_filters('user_update_before_save', $user, $request);
        $user->save();
        $user = ld_apply_filters('user_update_after_save', $user, $request);
        $this->persistSipCredentials($request, $user);

        ld_do_action('user_update_after', $user);

        // Role update
        $user->roles()->detach();
        if ($request->filled('roles')) {
            $roles = array_filter((array) $request->input('roles', []));
            if (!empty($roles)) {
                $user->assignRole($roles);
            }
        }

        $this->storeActionLog(ActionType::UPDATED, ['user' => $user]);
        session()->flash('success', __('User has been updated.'));
        $this->syncBackendUser($request, $user, $request->filled('password') ? $request->password : null);

        return back();
    }

    protected function backendClient(Request $request, bool $allowInternal = false)
    {
        $token = $request->session()->get('admin_token');
        if (! $token) {
            if (! $allowInternal) {
                return null;
            }
            $internalToken = config('services.backend.internal_token');
            if (! $internalToken) {
                return null;
            }
            return Http::withHeaders(['x-internal-token' => $internalToken])
                ->baseUrl(config('services.backend.url'));
        }
        return Http::withToken($token)->baseUrl(config('services.backend.url'));
    }

    protected function normalizeBackendRole(?string $role): ?string
    {
        if (! $role) {
            return null;
        }
        $normalized = strtolower(trim($role));
        $normalized = preg_replace('/[^a-z0-9]+/i', '_', $normalized) ?? $normalized;
        return trim($normalized, '_');
    }

    protected function syncBackendUser(Request $request, User $user, ?string $plainPassword): void
    {
        $client = $this->backendClient($request, true);
        if (! $client) {
            Log::warning('Backend user sync skipped: admin token missing.', ['email' => $user->email]);
            return;
        }

        $roleName = $user->getRoleNames()->first();
        $carrierExternalId = null;
        if ($user->carrier_id) {
            $carrier = Carrier::find($user->carrier_id);
            if ($carrier) {
                $carrierExternalId = $carrier->external_id ?: $carrier->id;
            }
        }
        $payload = [
            'fullName' => $user->external_name ?: ($user->name ?: $user->email),
            'email' => $user->email,
            'role' => $this->normalizeBackendRole($roleName),
            'carrierId' => $carrierExternalId,
            'recordingEnabled' => $user->recording_enabled,
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
        ];

        if ($plainPassword) {
            $payload['password'] = $plainPassword;
        }

        try {
            $response = $client->post('/admin/users/sync', $payload);
            if ($response->failed()) {
                Log::warning('Backend user sync failed', [
                    'email' => $user->email,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Backend user sync exception: '.$e->getMessage(), ['email' => $user->email]);
        }
    }

    protected function persistSipCredentials(Request $request, User $user): void
    {
        if (! $request->filled('sip_username') && ! $request->filled('sip_password')) {
            return;
        }

        $payload = [];
        if ($request->filled('sip_username')) {
            $payload['sip_username'] = $request->input('sip_username');
        }
        if ($request->filled('sip_password')) {
            $payload['sip_password'] = $request->input('sip_password');
        }

        if (empty($payload)) {
            return;
        }

        $user->sipCredential()->updateOrCreate(
            ['user_id' => $user->id],
            $payload
        );
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

    private function getCarriersForUserForms(Request $request)
    {
        $this->syncCarriersFromBackend($request);

        $carriers = Carrier::orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($carrier) => [
                'id' => $carrier->id,
                'name' => $carrier->name
            ]);

        if ($carriers->isEmpty() && method_exists($this->userService, 'getCarriersDropdown')) {
            return $this->userService->getCarriersDropdown();
        }

        return $carriers;
    }

    private function syncCarriersFromBackend(Request $request): void
    {
        $token = $request->session()->get('admin_token');
        if (!$token) {
            return;
        }

        try {
            $response = Http::withToken($token)
                ->baseUrl(config('services.backend.url'))
                ->get('/admin/carriers');

            $data = $response->json();
            if (!$response->ok() || !is_array($data)) {
                return;
            }

            foreach ($data as $carrier) {
                if (empty($carrier['id']) || empty($carrier['sip_domain'])) {
                    continue;
                }

                Carrier::updateOrCreate(
                    ['external_id' => $carrier['id']],
                    [
                        'name' => $carrier['name'] ?? __('Carrier #:id', ['id' => $carrier['id']]),
                        'default_caller_id' => $carrier['default_caller_id'] ?? null,
                        'caller_id_required' => (bool) ($carrier['caller_id_required'] ?? false),
                        'sip_domain' => $carrier['sip_domain'],
                        'sip_port' => $carrier['sip_port'] ?? null,
                        'transport' => $carrier['transport'] ?? 'udp',
                        'outbound_proxy' => $carrier['outbound_proxy'] ?? null,
                        'registration_required' => (bool) ($carrier['registration_required'] ?? false),
                        'registration_username' => $carrier['registration_username'] ?? null,
                        'registration_password' => $carrier['registration_password'] ?? null,
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to sync carriers from backend', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function resolveCarrierId($value): ?int
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $carrier = Carrier::where('external_id', $value)->first();
        if ($carrier) {
            return $carrier->id;
        }

        return null;
    }
}
