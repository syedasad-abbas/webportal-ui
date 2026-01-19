<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CarrierController extends Controller
{
    protected function backend(string $token)
    {
        return Http::withToken($token)->baseUrl(config('services.backend.url'));
    }

    // ✅ no repetition: one place to fetch + validate token
    protected function getAdminToken(Request $request)
    {
        $token = $request->session()->get('admin_token');

        if (! $token) {
            return redirect()
                ->route('user.login')
                ->withErrors('Backend admin token missing. Please login again.');
        }

        return $token;
    }

    public function index(Request $request)
    {
        $token = $this->getAdminToken($request);
        if (! is_string($token)) {
            return $token; // redirect response
        }

        $carrier = collect();

        // Optional: pass search to backend if supported
        $query = [];
        if ($request->filled('search')) {
            $query['search'] = $request->string('search')->toString();
        }

        $response = $this->backend($token)->get('/admin/carriers', $query);

        if ($response->ok()) {
            $carrier = collect($response->json());
        }

        return view('backend.pages.carrier.index', [
            'carrier' => $carrier,
            'breadcrumbs' => [
                'title' => __('carrier'),
            ],
        ]);
    }

    public function create(Request $request)
    {
        // No reference data needed for create in your current form
        return view('backend.pages.carrier.create', [
            'breadcrumbs' => [
                'title' => __('Add Carrier'),
                'items' => [
                    [
                        'label' => __('carrier'),
                        'url' => route('admin.carrier.index'),
                    ],
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $token = $this->getAdminToken($request);
        if (! is_string($token)) {
            return $token; // redirect response
        }

        $data = $request->validate([
            'name' => ['required', 'string'],
            'callerId' => ['nullable', 'string'],
            'callerIdRequired' => ['nullable', 'boolean'],
            'transport' => ['required', 'in:udp,tcp,tls'],
            'sipDomain' => ['required', 'string'],
            'sipPort' => ['required', 'integer', 'min:1', 'max:65535'],
            'registrationRequired' => ['nullable', 'boolean'],
            'registrationUsername' => ['nullable', 'string'],
            'registrationPassword' => ['nullable', 'string'],
            'prefix' => ['nullable', 'string'],

            // ✅ added only
            'outboundProxy' => ['nullable', 'string'],
        ]);

        $payload = $data;
        $payload['registrationRequired'] = $request->boolean('registrationRequired');
        $payload['callerIdRequired'] = $request->boolean('callerIdRequired', true);

        // Normalize optional fields
        $payload['registrationUsername'] = filled($data['registrationUsername'] ?? null) ? $data['registrationUsername'] : null;
        $payload['registrationPassword'] = filled($data['registrationPassword'] ?? null) ? $data['registrationPassword'] : null;
        $payload['prefix'] = filled($data['prefix'] ?? null) ? $data['prefix'] : null;

        // ✅ added only
        $payload['outboundProxy'] = filled($data['outboundProxy'] ?? null) ? $data['outboundProxy'] : null;

        $response = $this->backend($token)->post('/admin/carriers', $payload);

        if ($response->failed()) {
            return back()->withErrors([
                'name' => 'Unable to create carrier. Verify data or try again.',
            ])->withInput();
        }

        return redirect()
            ->route('admin.carrier.index')
            ->with('status', 'Carrier added successfully.');
    }

    public function edit(Request $request, string $carrierId)
    {
        $token = $this->getAdminToken($request);
        if (! is_string($token)) {
            return $token; // redirect response
        }

        $carrierResponse = $this->backend($token)->get("/admin/carriers/{$carrierId}");

        if ($carrierResponse->failed()) {
            return redirect()
                ->route('admin.carrier.index')
                ->withErrors(['carrier' => 'Carrier not found.']);
        }

        return view('backend.pages.carrier.edit', [
            'carrier' => $carrierResponse->json(),
            'breadcrumbs' => [
                'title' => __('Edit Carrier'),
                'items' => [
                    [
                        'label' => __('carrier'),
                        'url' => route('admin.carrier.index'),
                    ],
                ],
            ],
        ]);
    }

    public function update(Request $request, string $carrierId)
    {
        $token = $this->getAdminToken($request);
        if (! is_string($token)) {
            return $token; // redirect response
        }

        $data = $request->validate([
            'name' => ['required', 'string'],
            'callerId' => ['nullable', 'string'],
            'callerIdRequired' => ['nullable', 'boolean'],
            'transport' => ['required', 'in:udp,tcp,tls'],
            'sipDomain' => ['required', 'string'],
            'sipPort' => ['required', 'integer', 'min:1', 'max:65535'],
            'registrationRequired' => ['nullable', 'boolean'],
            'registrationUsername' => ['nullable', 'string'],
            'registrationPassword' => ['nullable', 'string'],
            'prefix' => ['nullable', 'string'],

            // ✅ added only
            'outboundProxy' => ['nullable', 'string'],
        ]);

        $payload = $data;
        $payload['registrationRequired'] = $request->boolean('registrationRequired');
        $payload['callerIdRequired'] = $request->boolean('callerIdRequired');

        $payload['registrationUsername'] = filled($data['registrationUsername'] ?? null) ? $data['registrationUsername'] : null;
        $payload['registrationPassword'] = filled($data['registrationPassword'] ?? null) ? $data['registrationPassword'] : null;
        $payload['prefix'] = filled($data['prefix'] ?? null) ? $data['prefix'] : null;

        // ✅ added only
        $payload['outboundProxy'] = filled($data['outboundProxy'] ?? null) ? $data['outboundProxy'] : null;

        $response = $this->backend($token)->put("/admin/carriers/{$carrierId}", $payload);

        if ($response->failed()) {
            return back()->withErrors('Unable to update carrier.')->withInput();
        }

        return redirect()
            ->route('admin.carrier.index')
            ->with('status', 'Carrier updated.');
    }

    public function destroy(Request $request, string $carrierId)
    {
        $token = $this->getAdminToken($request);
        if (! is_string($token)) {
            return $token; // redirect response
        }

        $response = $this->backend($token)->delete("/admin/carriers/{$carrierId}");

        if ($response->failed()) {
            return redirect()
                ->route('admin.carrier.index')
                ->withErrors('Unable to delete carrier.');
        }

        return redirect()
            ->route('admin.carrier.index')
            ->with('status', 'Carrier deleted.');
    }
}
