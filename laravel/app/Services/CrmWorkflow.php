<?php
namespace App\Services;
use App\Models\CrmWorkflowSetting;
use Illuminate\Support\Facades\Schema;
class CrmWorkflow {
    public const FIELDS = [
        'loan_amount'=>'Requested amount', 'loan_purpose'=>'Purpose', 'physical_address'=>'Physical address',
        'income_source'=>'Income source', 'monthly_income'=>'Monthly income',
        'food'=>'Food expense', 'utilities'=>'Utilities expense', 'phone_expense'=>'Phone expense',
        'car_payment'=>'Car payment', 'car_insurance'=>'Car insurance', 'housing'=>'Housing expense',
        'other_expenses'=>'Other expenses', 'pain_statement'=>'Customer exact words: difficulty',
        'dream_answer'=>'Customer exact words: desired change', 'excluded_accounts'=>'Accounts customer wants excluded',
    ];
    public const REVIEW = [
        'accounts'=>'Debt accounts and balances', 'debts_total'=>'Total debt', 'accounts_count'=>'Account count',
        'credit_score'=>'Credit score', 'credit_utilization'=>'Credit utilization (%)',
        'plan_name'=>'Enrollment plan', 'monthly_payment'=>'Monthly payment', 'term_length'=>'Term (months)',
        'program_cost'=>'Program cost', 'enrollment_status'=>'Enrollment status',
    ];
    public const STAGES = ['after_verification','debt_review','plan_review'];
    public function sources(): array {
        $sources=[];
        foreach(self::REVIEW as $k=>$label) $sources['review.'.$k]=$label;
        foreach(['monthly_income','loan_amount','pain_statement','dream_answer'] as $k) $sources['intake.'.$k]=self::FIELDS[$k];
        return $sources;
    }
    public function retrieval(): array { return $this->settings()?->retrieval ?? []; }
    public const METRICS = ['total_customers', 'open_leads', 'appointments_booked', 'won_revenue', 'lost_revenue', 'intakes_recorded', 'requested_amount_total'];
    public function settings(): ?CrmWorkflowSetting {
        return Schema::hasTable('crm_workflow_settings') ? CrmWorkflowSetting::find(1) : null;
    }
    public function fields(): array { return $this->settings()?->fields ?? []; }
    public function cards(): array {
        return $this->settings()?->cards ?? array_map(fn($key)=>['key'=>$key, 'label'=>ucwords(str_replace('_',' ',$key)), 'enabled'=>true], array_slice(self::METRICS,0,5));
    }
    public function connection(): array {
        $s=$this->settings();
        return ['url'=>$s?->api_url ?: config('crm.base_url'), 'token'=>$s?->api_token ?: config('crm.token')];
    }
}
