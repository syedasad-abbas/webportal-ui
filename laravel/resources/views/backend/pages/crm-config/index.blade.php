@extends('backend.layouts.app')
@section('title', 'CRM Configuration')
@section('admin-content')
<div class="max-w-4xl mx-auto p-6 space-y-6">
<h1 class="text-2xl font-semibold">CRM Configuration</h1>
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
<form method="POST" action="{{ route('admin.crm-config.update') }}" class="space-y-4">
@csrf @method('PUT')
<details class="border rounded p-3">
<summary>Demo call scenario</summary>
<p>Simulation only. Enable here, then mark the active call as a demo in Customer Records. No real provider actions are performed.</p>
<input type="hidden" name="demo_enabled" value="0">
<label><input type="checkbox" name="demo_enabled" value="1" @checked(old('demo_enabled',$demoEnabled))> Enable explicitly selected demo calls</label>
<label class="block">Editable values and ordered steps<textarea id="crm-demo-json" class="form-control w-full" rows="14" name="demo_json" maxlength="30000">{{ old('demo_json',$demoScenario ? json_encode($demoScenario,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) : '') }}</textarea></label>
<button type="button" id="crm-demo-fill" data-template="{{ asset('demo/clarity-scenario.json') }}">Fill missing demo examples</button>
<button type="button" id="crm-demo-intro" data-template="{{ asset('demo/clarity-intro-scenario.json') }}">Use greeting-to-pain demo</button>
<p>This replaces the draft steps with the flow ending after the pain question and adds one editable fictional credit record. Existing caller-example values are retained. Review the draft before saving.</p>
<p id="crm-demo-status" role="status"></p>
<p>Existing values are preserved. Review fictional values, then Save. Do not enter real identity or banking credentials.</p>
</details>
<h2 class="text-lg font-semibold">Customer data to retrieve during calls</h2>
<p>Choose CRM fields and the script stage for reviewing them. A staff member must first verify and link the customer to the active call in CRM Records. A phone number alone does not verify identity.</p>
<p>Fields marked internal only are withheld from the voice AI. Missing values are never replaced with shared demo data.</p>
@foreach($sources as $source=>$label)
@php $i=$loop->index; $r=collect($retrieval)->firstWhere('source',$source)??['label'=>$label,'enabled'=>false,'read_aloud'=>false,'stage'=>'debt_review','missing'=>'handoff']; @endphp
<fieldset class="border rounded p-3 space-y-2">
<input type="hidden" name="retrieval[{{ $i }}][source]" value="{{ $source }}">
<input type="hidden" name="retrieval[{{ $i }}][enabled]" value="0">
<label><input type="checkbox" name="retrieval[{{ $i }}][enabled]" value="1" @checked($r['enabled'])> {{ $label }}</label>
<label class="block">AI label <input class="form-control" name="retrieval[{{ $i }}][label]" value="{{ $r['label'] }}" required maxlength="80"></label>
<p>CRM field: {{ $source }}</p>
<label>Fetch at <select name="retrieval[{{ $i }}][stage]">
@foreach(\App\Services\CrmWorkflow::STAGES as $stage)<option value="{{ $stage }}" @selected($r['stage']===$stage)>{{ ucwords(str_replace('_',' ',$stage)) }}</option>@endforeach
</select></label>
<input type="hidden" name="retrieval[{{ $i }}][read_aloud]" value="0">
<label><input type="checkbox" name="retrieval[{{ $i }}][read_aloud]" value="1" @checked($r['read_aloud'])> AI may read this value aloud</label>
<label class="block">If missing <select name="retrieval[{{ $i }}][missing]"><option value="handoff" @selected($r['missing']==='handoff')>Ask a human to review</option><option value="clarify" @selected($r['missing']==='clarify')>Ask customer for clarification</option></select></label>
</fieldset>
@endforeach
<h2 class="text-lg font-semibold">AI intake fields</h2>
<p>Only checked fields are offered to the AI. Amounts must reflect the caller’s actual answers; no expense targets or invented values.</p>
@foreach($fields as $key=>$label)
<label class="block"><input type="checkbox" name="fields[]" value="{{ $key }}" @checked(in_array($key, old('fields', $selected),true))> {{ $label }}</label>
@endforeach
<p>Credit reports, enrollment, bank collection, payment scheduling, email, signatures, and compliance transfers are not connected. Do not collect SSN or bank credentials in these intake fields.</p>
@can('crm_config.edit')<button class="btn-primary" type="submit">Save CRM configuration</button>@endcan
</form>
@can('contacts.view')
<h2 class="text-lg font-semibold">Recent demo CRM notes</h2>
<p>Model-transcribed caller quotes; check against the recording if exact transcription is important. Demo notes are separate from real customer records.</p>
@forelse($demoNotes as $note)
<div class="border rounded p-3"><p>{{ $note->updated_at }} · Call {{ $note->call_uuid }} · Customer {{ $note->customer_id }}</p>@if($note->credit_report)
@php($report=json_decode($note->credit_report,true))
<p>Demo report: {{ $report['accounts_count'] ?? '' }} accounts · {{ $report['debts_total'] ?? '' }} total · {{ $report['enrollment']['plan_account'] ?? '' }} / {{ $report['enrollment']['base_plan'] ?? '' }} · {{ $report['enrollment']['term_months'] ?? '' }} months</p>
@endif
<blockquote class="whitespace-pre-wrap">{{ $note->pain_quote }}</blockquote></div>
@empty<p>No demo pain statements saved yet.</p>@endforelse
@endcan
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/crm-demo-config.js') }}"></script>
@endpush
