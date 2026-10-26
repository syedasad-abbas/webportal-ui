<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;

class CrmDemoContinuation {
    public const EXPENSES=['food','utilities','phone','car_payment','car_insurance','housing','other'];
    public function run(string $callId, array $data, array $scenario): array {
        return DB::transaction(function() use($callId,$data,$scenario) {
            $note=DB::table('crm_demo_notes')->where('call_uuid',$callId)->lockForUpdate()->first();
            if (!$note) return ['status'=>'unavailable','message'=>'Retrieve the demo report first.'];
            $state=json_decode($note->continuation??'{}',true)?:[];
            $report=json_decode($note->credit_report??'{}',true)?:[];
            switch ($data['operation']) {
                case 'demo_budget':
                    $budget=$data['budget'];
                    $total=round(array_sum(array_intersect_key($budget,array_flip(self::EXPENSES))),2);
                    $state['budget']=['reported'=>$budget,'expenses_total'=>$total,'expense_percent'=>round($total/$budget['monthly_income']*100,2),'remaining'=>round($budget['monthly_income']-$total,2)];
                    $result=['status'=>'budget_ready','budget'=>$state['budget'],'credit_score'=>$report['credit_score']??null,'credit_utilization'=>$scenario['credit_record']['credit_utilization']??null,'message'=>'Actual reported expenses preserved. This is a demo calculation, not underwriting or a credit decision.'];
                    break;
                case 'demo_dream':
                    $state['dream_quote']=$data['quote'];
                    $result=['status'=>'saved','message'=>'Dream quote saved without summarizing. Continue to the next configured step.'];
                    break;
                case 'demo_comparison':
                    if (empty($state['budget']) || empty($state['dream_quote']) || empty($note->pain_quote) || empty($scenario['comparison']) || empty($report['enrollment'])) return ['status'=>'unavailable','message'=>'Budget, pain, dream, report and configured comparison are required.'];
                    $c=$scenario['comparison'];
                    $term=$report['enrollment']['term_months'];
                    $state['comparison']=['template'=>'Comparison Template A (demo)','delivery_status'=>'preview_only','email_sent'=>false,
                        'accounts'=>$report['accounts'],'pain_quote'=>$note->pain_quote,'dream_quote'=>$state['dream_quote'],
                        'current'=>$c,'proposed'=>['monthly_payment'=>round($c['program_cost']/$term,2),'term_months'=>$term,'program_cost'=>$c['program_cost'],'interest_rate'=>$c['interest_rate']],
                        'remaining_after_payment'=>round($state['budget']['remaining']-$c['program_cost']/$term,2)];
                    $result=['status'=>'preview_ready','comparison'=>$state['comparison'],'message'=>'Preview saved in CRM; no email sent, approval or creditor agreement. Read one number at a time; accept no or uncertainty without pressure.'];
                    break;
                case 'demo_exclusions':
                    $creditors=array_column($report['accounts']??[],'creditor');
                    if (array_diff($data['accounts'],$creditors)) return ['status'=>'invalid','message'=>'Only creditors in this demo report can be excluded.'];
                    $state['requested_exclusions']=array_values(array_unique($data['accounts']));
                    // Any changed debt selection needs a fresh quote, never reuse the prior price.
                    if ($state['requested_exclusions']) unset($state['comparison']);
                    $result=['status'=>'saved','message'=>'Exclusion request recorded for review. No account changed. If any exclusions were requested, the previous comparison is invalid; do not promise a revised payment.'];
                    break;
                default: return ['status'=>'invalid'];
            }
            DB::table('crm_demo_notes')->where('call_uuid',$callId)->update(['continuation'=>json_encode($state),'updated_at'=>now()]);
            return $result+['simulated'=>true];
        });
    }
}
