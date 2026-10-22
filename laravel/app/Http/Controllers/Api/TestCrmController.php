<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TestCrmCustomer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TestCrmController extends Controller
{
    public function index(Request $request)
    {
        $input = $request->validate(['phone' => ['nullable', 'string', 'regex:/^[0-9]{7,15}$/'], 'page' => ['sometimes', 'integer', 'min:1']]);
        return response()->json(TestCrmCustomer::query()
            ->when(!empty($input['phone']), fn ($q) => $q->where('phone', $input['phone']))
            ->latest('id')->paginate(20));
    }

    public function show(TestCrmCustomer $customer) { return response()->json(['data' => $customer]); }

    public function store(Request $request)
    {
        return response()->json(['data' => TestCrmCustomer::create($this->validated($request))], 201);
    }

    public function update(Request $request, TestCrmCustomer $customer)
    {
        $customer->update($this->validated($request, $customer));
        return response()->json(['data' => $customer->fresh()]);
    }

    public function destroy(TestCrmCustomer $customer)
    {
        $customer->delete();
        return response()->noContent();
    }

    public function summary()
    {
        return response()->json(['data' => [
            'total_customers' => TestCrmCustomer::count(),
            'open_leads' => TestCrmCustomer::whereIn('status', ['new', 'qualified'])->count(),
            'won_revenue' => (string) TestCrmCustomer::where('status', 'won')->sum('value'),
            'lost_revenue' => (string) TestCrmCustomer::where('status', 'lost')->sum('value'),
        ]]);
    }

    private function validated(Request $request, ?TestCrmCustomer $customer = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'phone' => ['required', 'string', 'regex:/^[0-9]{7,15}$/', Rule::unique('test_crm_customers', 'phone')->ignore($customer?->id)],
            'email' => ['nullable', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:200'],
            'status' => ['required', Rule::in(['new', 'qualified', 'won', 'lost'])],
            'value' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
