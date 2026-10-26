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
            'intakes_recorded' => TestCrmCustomer::get(['intake'])->filter(fn($c)=>count(array_filter($c->intake??[],fn($v)=>$v!==null && $v!==''))>0)->count(),
            'requested_amount_total' => TestCrmCustomer::get(['intake'])->sum(fn($c)=>(float)($c->intake['loan_amount'] ?? 0)),
            'total_customers' => TestCrmCustomer::count(),
            'open_leads' => TestCrmCustomer::whereIn('status', ['new', 'qualified'])->count(),
            'appointments_booked' => TestCrmCustomer::whereRaw('LOWER(notes) LIKE ?', ['%appointment date:%'])->count(),
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
            'review'=>['sometimes','nullable','array:'.implode(',',array_keys(\App\Services\CrmWorkflow::REVIEW))],
            'review.credit_score'=>['nullable','integer','min:300','max:850'],
            'review.credit_utilization'=>['nullable','numeric','min:0','max:100'],
            'review.debts_total'=>['nullable','numeric','min:0','max:999999999.99'],
            'review.accounts_count'=>['nullable','integer','min:0','max:1000'],
            'review.term_length'=>['nullable','integer','min:1','max:600'],
            'review.monthly_payment'=>['nullable','numeric','min:0','max:999999999.99'],
            'review.program_cost'=>['nullable','numeric','min:0','max:999999999.99'],
            'review.plan_name'=>['nullable','string','max:200'],
            'review.enrollment_status'=>['nullable','string','max:100'],
            'review.accounts'=>['nullable','array','max:30'],
            'review.accounts.*'=>['array:creditor,balance'],
            'review.accounts.*.creditor'=>['required','string','max:200'],
            'review.accounts.*.balance'=>['required','numeric','min:0','max:999999999.99'],
            'intake' => ['sometimes', 'nullable', 'array:'.implode(',', array_keys(\App\Services\CrmWorkflow::FIELDS))],
            'intake.*' => ['nullable', 'string', 'max:2000'],
            ...array_fill_keys(array_map(fn($key)=>'intake.'.$key, ['loan_amount','monthly_income','food','utilities','phone_expense','car_payment','car_insurance','housing','other_expenses']), ['nullable','numeric','min:0','max:999999999.99']),
        ]);
    }
}
