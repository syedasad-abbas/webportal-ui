<?php
namespace App\Services;

/** A single configurable fictional record. Never calls a credit bureau. */
class CrmDemoReport {
    public function fetch(array $scenario): array {
        $record=$scenario['credit_record']??null;
        if (!$record || empty($record['accounts']) || empty($scenario['term_table'])) return ['status'=>'unavailable'];
        $total=round(array_sum(array_column($record['accounts'],'balance')),2);
        $eligible=array_values(array_filter($scenario['term_table'],fn($tier)=>$total >= $tier['min_debt'] && $total <= $tier['max_debt']));
        if (!$eligible) return ['status'=>'unavailable','message'=>'No configured demo plan matches this debt load. Do not invent a plan.'];
        usort($eligible,fn($a,$b)=>$b['term_months']<=>$a['term_months']);
        return ['status'=>'report_ready','simulated'=>true,'record_id'=>$record['id'],
            'accounts'=>$record['accounts'],'accounts_count'=>count($record['accounts']),
            'debts_total'=>$total,'credit_score'=>$record['credit_score'],
            'enrollment'=>$eligible[0], 'message'=>'Fictional report and demo enrollment selection only. No bureau pull or real enrollment was performed.'];
    }
}
