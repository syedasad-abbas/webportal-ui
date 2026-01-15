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

    public function index(Request $request)
    {
        $token = $request->session()->get('admin_token');
        $carriers = collect();

        if ($token) {
            // Optional: pass search to backend if supported
            $query = [];
            if ($request->filled('search')) {
                $query['search'] = $request->string('search')->toString();
            }

            $response = $this->backend($token)->get('/admin/carriers', $query);

            if ($response->ok()) {
                $carriers = collect($response->json());
            }
        }

        return view('backend.pages.carriers.index', [
            'carriers' => $carriers,
            'breadcrumbs' => [
                'title' => __('Carriers'),
            ],
        ]);
    }

    public function create(Request $request)
    {
        // No reference data needed for create in your current form
        return view('backend.pages.carriers.create', [
            'breadcrumbs' => [
                'title' => __('Add Carrier'),
                'items' => [
                    [
                        'label' => __('Carriers'),
                        'url' => route('admin.carriers.index'),
                    ],
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $token = $request->session()->get('admin_token');

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
        ]);

        $payload = $data;
        $payload['registrationRequired'] = $request->boolean('registrationRequired');
        $payload['callerIdRequired'] = $request->boolean('callerIdRequired', true);

        // Normalize optional fields
        $payload['registrationUsername'] = filled($data['registrationUsername'] ?? null) ? $data['registrationUsername'] : null;
        $payload['registrationPassword'] = filled($data['registrationPassword'] ?? null) ? $data['registrationPassword'] : null;
        $payload['prefix'] = filled($data['prefix'] ?? null) ? $data['prefix'] : null;

        $response = $this->backend($token)->post('/admin/carriers', $payload);

        if ($response->failed()) {
            return back()->withErrors([
                'name' => 'Unable to create carrier. Verify data or try again.',
            ])->withInput();
        }

        return redirect()
            ->route('admin.carriers.index')
            ->with('status', 'Carrier added successfully.');
    }

    public function edit(Request $request, string $carrierId)
    {
        $token = $request->session()->get('admin_token');

        $carrierResponse = $this->backend($token)->get("/admin/carriers/{$carrierId}");

        if ($carrierResponse->failed()) {
            return redirect()
                ->route('admin.carriers.index')
                ->withErrors(['carrier' => 'Carrier not found.']);
        }

        return view('backend.pages.carriers.edit', [
            'carrier' => $carrierResponse->json(),
            'breadcrumbs' => [
                'title' => __('Edit Carrier'),
                'items' => [
                    [
                        'label' => __('Carriers'),
                        'url' => route('admin.carriers.index'),
                    ],
                ],
            ],
        ]);
    }

    public function update(Request $request, string $carrierId)
    {
        $token = $request->session()->get('admin_token');

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
        ]);

        $payload = $data;
        $payload['registrationRequired'] = $request->boolean('registrationRequired');
        $payload['callerIdRequired'] = $request->boolean('callerIdRequired');

        $payload['registrationUsername'] = filled($data['registrationUsername'] ?? null) ? $data['registrationUsername'] : null;
        $payload['registrationPassword'] = filled($data['registrationPassword'] ?? null) ? $data['registrationPassword'] : null;
        $payload['prefix'] = filled($data['prefix'] ?? null) ? $data['prefix'] : null;

        $response = $this->backend($token)->put("/admin/carriers/{$carrierId}", $payload);

        if ($response->failed()) {
            return back()->withErrors('Unable to update carrier.')->withInput();
        }

        return redirect()
            ->route('admin.carriers.index')
            ->with('status', 'Carrier updated.');
    }

    public function destroy(Request $request, string $carrierId)
    {
        $token = $request->session()->get('admin_token');

        $response = $this->backend($token)->delete("/admin/carriers/{$carrierId}");

        if ($response->failed()) {
            return redirect()
                ->route('admin.carriers.index')
                ->withErrors('Unable to delete carrier.');
        }

        return redirect()
            ->route('admin.carriers.index')
            ->with('status', 'Carrier deleted.');
    }
}
