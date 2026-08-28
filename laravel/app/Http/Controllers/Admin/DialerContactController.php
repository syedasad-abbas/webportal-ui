<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallLog;
use App\Models\DialerContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DialerContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'contacts.view');
        $search = trim((string) $request->query('search', ''));
        $phone = $this->normalizePhone((string) $request->query('phone', ''));
        $label = trim((string) $request->query('label', ''));

        $contacts = DialerContact::query()
            ->when($phone !== '', fn ($query) => $query->where('phone_normalized', $phone))
            ->when($phone === '' && $search !== '', function ($query) use ($search) {
                $normalizedSearch = $this->normalizePhone($search);
                $query->where(function ($inner) use ($search, $normalizedSearch) {
                    $inner->where('name', 'ilike', "%{$search}%")
                        ->orWhere('company', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                    if ($normalizedSearch !== '') {
                        $inner->orWhere('phone_normalized', 'like', "%{$normalizedSearch}%");
                    }
                });
            })
            ->when($label !== '', fn ($query) => $query->whereJsonContains('labels', $label))
            ->with(['comments.user:id,external_name,email', 'creator:id,external_name,email'])
            ->orderByDesc('is_flagged')->orderBy('name')
            ->limit($phone !== '' ? 1 : 50)->get();

        $labels = DialerContact::query()->whereNotNull('labels')->pluck('labels')->flatten()
            ->filter()->unique(fn ($value) => mb_strtolower((string) $value))->sort()->values();

        return response()->json(['contacts' => $contacts, 'labels' => $labels]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'contacts.create');
        $data = $this->validateContact($request, false);
        if (! empty($data['labels'])) $this->authorizePermission($request, 'contacts.labels');

        $normalized = $this->normalizePhone($data['phone']);
        if ($normalized === '') return response()->json(['message' => 'Enter a valid phone number.'], 422);
        if (DialerContact::where('phone_normalized', $normalized)->exists()) {
            return response()->json(['message' => 'A global contact with this phone number already exists.'], 422);
        }

        $contact = DialerContact::create([
            'created_by' => $request->user()->id,
            ...$this->prepareContactData($data),
            'phone_normalized' => $normalized,
        ]);
        $this->logActivity($request, $contact, 'contact_created', 'Contact was created.', [
            'contact' => ['old' => null, 'new' => $contact->only(['name', 'company', 'phone', 'email', 'labels', 'is_flagged'])],
        ]);

        return response()->json(['contact' => $this->loadContact($contact)], 201);
    }

    public function update(Request $request, DialerContact $contact): JsonResponse
    {
        $this->authorizePermission($request, 'contacts.view');
        $data = $this->validateContact($request, true);
        if (array_key_exists('labels', $data)) $this->authorizePermission($request, 'contacts.labels');
        if (collect(array_keys($data))->intersect(['name', 'company', 'phone', 'email', 'is_flagged'])->isNotEmpty()) {
            $this->authorizePermission($request, 'contacts.edit');
        }

        $prepared = $this->prepareContactData($data);
        if (array_key_exists('phone', $prepared)) {
            $prepared['phone_normalized'] = $this->normalizePhone($prepared['phone']);
            if ($prepared['phone_normalized'] === '') return response()->json(['message' => 'Enter a valid phone number.'], 422);
            if (DialerContact::where('phone_normalized', $prepared['phone_normalized'])->whereKeyNot($contact->id)->exists()) {
                return response()->json(['message' => 'A global contact with this phone number already exists.'], 422);
            }
        }

        $before = $contact->only(['name', 'company', 'phone', 'email', 'labels', 'is_flagged']);
        $contact->update($prepared);
        $after = $contact->fresh()->only(array_keys($before));
        $changes = $this->changedValues($before, $after);

        if (array_key_exists('labels', $changes)) {
            $oldLabels = $before['labels'] ?? [];
            $newLabels = $after['labels'] ?? [];
            foreach (array_values(array_diff($newLabels, $oldLabels)) as $label) {
                $this->logActivity($request, $contact, 'label_added', "Label ‘{$label}’ was added.", ['label' => ['old' => null, 'new' => $label]]);
            }
            foreach (array_values(array_diff($oldLabels, $newLabels)) as $label) {
                $this->logActivity($request, $contact, 'label_removed', "Label ‘{$label}’ was removed.", ['label' => ['old' => $label, 'new' => null]]);
            }
            unset($changes['labels']);
        }
        if (array_key_exists('is_flagged', $changes)) {
            $action = $after['is_flagged'] ? 'flag_added' : 'flag_removed';
            $description = $after['is_flagged'] ? 'Contact was flagged.' : 'Contact flag was removed.';
            $this->logActivity($request, $contact, $action, $description, ['is_flagged' => $changes['is_flagged']]);
            unset($changes['is_flagged']);
        }
        if ($changes !== []) {
            $fields = implode(', ', array_map(fn ($field) => str_replace('_', ' ', $field), array_keys($changes)));
            $this->logActivity($request, $contact, 'contact_updated', "Contact {$fields} updated.", $changes);
        }

        return response()->json(['contact' => $this->loadContact($contact->fresh())]);
    }

    public function destroy(Request $request, DialerContact $contact): JsonResponse
    {
        $this->authorizePermission($request, 'contacts.delete');
        $contact->delete();
        return response()->json(['message' => 'Contact deleted.']);
    }

    public function comment(Request $request, DialerContact $contact): JsonResponse
    {
        $this->authorizePermission($request, 'contacts.view');
        $this->authorizePermission($request, 'contacts.comment');
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $comment = $contact->comments()->create([
            'user_id' => $request->user()->id,
            'body' => trim($data['body']),
        ])->load('user:id,external_name,email');
        $this->logActivity($request, $contact, 'comment_added', 'A comment was added.', [
            'comment' => ['old' => null, 'new' => mb_strimwidth($comment->body, 0, 180, '…')],
        ]);
        return response()->json(['comment' => $comment], 201);
    }

    public function activity(Request $request, DialerContact $contact): JsonResponse
    {
        $this->authorizePermission($request, 'contacts.view');
        return response()->json(['activity' => $contact->activities()->with('user:id,external_name,email')->limit(100)->get()]);
    }

    public function callHistory(Request $request, DialerContact $contact): JsonResponse
    {
        $this->authorizePermission($request, 'contacts.view');
        $calls = CallLog::query()->forPhone($contact->phone_normalized)
            ->with('user:id,external_name,email')->latest('created_at')->limit(100)
            ->get(['id', 'user_id', 'call_uuid', 'direction', 'destination', 'caller_id', 'status', 'notes', 'duration_seconds', 'connected_at', 'ended_at', 'created_at']);
        return response()->json(['calls' => $calls]);
    }

    protected function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, 'You do not have permission to use this contact-book feature.');
    }

    protected function validateContact(Request $request, bool $partial): array
    {
        $sometimes = $partial ? 'sometimes' : 'required';
        return $request->validate([
            'name' => [$sometimes, 'required', 'string', 'max:255'],
            'company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => [$sometimes, 'required', 'string', 'max:40'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'secondary_phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'avatar_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'address' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'account_id' => ['sometimes', 'nullable', 'string', 'max:80'],
            'account_status' => ['sometimes', 'nullable', 'string', 'max:80'],
            'customer_since' => ['sometimes', 'nullable', 'date'],
            'industry' => ['sometimes', 'nullable', 'string', 'max:120'],
            'employees' => ['sometimes', 'nullable', 'string', 'max:80'],
            'annual_revenue' => ['sometimes', 'nullable', 'string', 'max:80'],
            'preferred_contact_time' => ['sometimes', 'nullable', 'string', 'max:160'],
            'labels' => ['sometimes', 'array', 'max:10'],
            'labels.*' => ['string', 'max:30'],
            'is_flagged' => ['sometimes', 'boolean'],
        ]);
    }

    protected function prepareContactData(array $data): array
    {
        foreach (['name', 'company', 'phone', 'email', 'secondary_phone', 'avatar_url', 'address', 'account_id', 'account_status', 'customer_since', 'industry', 'employees', 'annual_revenue', 'preferred_contact_time'] as $field) {
            if (array_key_exists($field, $data)) $data[$field] = filled($data[$field]) ? trim((string) $data[$field]) : null;
        }
        if (array_key_exists('email', $data) && $data['email']) $data['email'] = strtolower($data['email']);
        if (array_key_exists('labels', $data)) $data['labels'] = $this->cleanLabels($data['labels']);
        if (array_key_exists('is_flagged', $data)) $data['is_flagged'] = (bool) $data['is_flagged'];
        return $data;
    }

    protected function loadContact(DialerContact $contact): DialerContact
    {
        return $contact->load(['comments.user:id,external_name,email', 'creator:id,external_name,email']);
    }

    protected function logActivity(Request $request, DialerContact $contact, string $action, string $description, array $changes = []): void
    {
        $contact->activities()->create(['user_id' => $request->user()->id, 'action' => $action, 'description' => $description, 'changes' => $changes ?: null]);
    }

    protected function changedValues(array $before, array $after): array
    {
        $changes = [];
        foreach ($after as $field => $value) {
            if (($before[$field] ?? null) !== $value) $changes[$field] = ['old' => $before[$field] ?? null, 'new' => $value];
        }
        return $changes;
    }

    protected function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    protected function cleanLabels(array $labels): array
    {
        return collect($labels)->map(fn ($label) => trim((string) $label))->filter()
            ->unique(fn ($label) => mb_strtolower($label))->take(10)->values()->all();
    }
}
