<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallLog;
use App\Models\DialerContact;
use App\Models\DialerContactActivity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactCenterController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeContacts($request);

        $search = trim((string) $request->query('search', ''));
        $filter = (string) $request->query('filter', 'all');
        $sort = (string) $request->query('sort', 'name');

        $contacts = DialerContact::query()
            ->when($search !== '', function ($query) use ($search): void {
                $digits = preg_replace('/\D+/', '', $search) ?? '';
                $query->where(function ($inner) use ($search, $digits): void {
                    $inner->where('name', 'ilike', "%{$search}%")
                        ->orWhere('company', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                    if ($digits !== '') {
                        $inner->orWhere('phone_normalized', 'like', "%{$digits}%");
                    }
                });
            })
            ->when($filter === 'vip', fn ($query) => $query->whereJsonContains('labels', 'VIP'))
            ->when($filter === 'follow-up', fn ($query) => $query->where('is_flagged', true))
            ->when($sort === 'recent', fn ($query) => $query->latest('updated_at'))
            ->when($sort !== 'recent', fn ($query) => $query->orderBy('name'))
            ->with(['comments' => fn ($query) => $query->latest()->limit(1)])
            ->withCount('comments')
            ->paginate(10)
            ->withQueryString();

        return view('backend.pages.dialer.contacts-index', compact('contacts', 'search', 'filter', 'sort'));
    }

    public function show(Request $request, DialerContact $contact): View
    {
        $this->authorizeContacts($request);

        $contact->load([
            'comments' => fn ($query) => $query->with('user:id,external_name,email')->limit(25),
            'activities' => fn ($query) => $query->with('user:id,external_name,email')->limit(25),
        ]);
        $calls = CallLog::query()->forPhone($contact->phone_normalized)
            ->with('user:id,external_name,email')->latest('created_at')->limit(25)->get();

        return view('backend.pages.dialer.contacts-show', compact('contact', 'calls'));
    }

    public function create(Request $request): View
    {
        $this->authorizeContactPermission($request, 'contacts.create');

        return view('backend.pages.dialer.contacts-form', ['contact' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeContactPermission($request, 'contacts.create');
        $data = $this->validateContactForm($request);
        $normalized = $this->normalizePhone($data['phone']);

        if ($normalized === '') {
            return back()->withErrors(['phone' => __('Enter a valid phone number.')])->withInput();
        }
        if (DialerContact::where('phone_normalized', $normalized)->exists()) {
            return back()->withErrors(['phone' => __('A global contact with this phone number already exists.')])->withInput();
        }

        $contact = DialerContact::create([
            'created_by' => $request->user()->id,
            ...$data,
            'phone_normalized' => $normalized,
        ]);
        $contact->activities()->create([
            'user_id' => $request->user()->id,
            'action' => 'contact_created',
            'description' => 'Contact was created.',
            'changes' => ['contact' => ['old' => null, 'new' => $contact->only(['name', 'company', 'phone', 'email', 'avatar_url'])]],
        ]);

        return redirect()->route('admin.contacts.show', $contact)->with('success', __('Contact created successfully.'));
    }

    public function edit(Request $request, DialerContact $contact): View
    {
        $this->authorizeContactPermission($request, 'contacts.edit');

        return view('backend.pages.dialer.contacts-form', compact('contact'));
    }

    public function update(Request $request, DialerContact $contact): RedirectResponse
    {
        $this->authorizeContactPermission($request, 'contacts.edit');
        $data = $this->validateContactForm($request);
        $normalized = $this->normalizePhone($data['phone']);

        if ($normalized === '') {
            return back()->withErrors(['phone' => __('Enter a valid phone number.')])->withInput();
        }
        if (DialerContact::where('phone_normalized', $normalized)->whereKeyNot($contact->id)->exists()) {
            return back()->withErrors(['phone' => __('A global contact with this phone number already exists.')])->withInput();
        }

        $before = $contact->only(['name', 'company', 'phone', 'email', 'avatar_url']);
        $contact->update([...$data, 'phone_normalized' => $normalized]);
        $after = $contact->fresh()->only(array_keys($before));
        if ($before !== $after) {
            $contact->activities()->create([
                'user_id' => $request->user()->id,
                'action' => 'contact_updated',
                'description' => 'Contact details were updated.',
                'changes' => ['contact' => ['old' => $before, 'new' => $after]],
            ]);
        }

        return redirect()->route('admin.contacts.show', $contact)->with('success', __('Contact updated successfully.'));
    }

    public function activity(Request $request): View|JsonResponse
    {
        $this->authorizeContacts($request);

        $activities = DialerContactActivity::query()
            ->with(['contact:id,name,company,phone,avatar_url', 'user:id,external_name,email'])
            ->latest('created_at')->paginate(20)->withQueryString();

        // Return JSON for AJAX requests (embedded in dialer page)
        if ($request->query('format') === 'json' || $request->wantsJson()) {
            return response()->json(['activities' => $activities]);
        }

        return view('backend.pages.dialer.contacts-activity', compact('activities'));
    }

    public function callHistory(Request $request): View|JsonResponse
    {
        $this->authorizeContacts($request);

        $calls = CallLog::query()->with('user:id,external_name,email')
            ->latest('created_at')->paginate(20)->withQueryString();
        $contacts = DialerContact::query()->get(['id', 'name', 'company', 'phone_normalized', 'avatar_url'])
            ->keyBy('phone_normalized');

        $calls->getCollection()->transform(function (CallLog $call) use ($contacts): CallLog {
            $number = $call->direction === 'inbound' ? $call->caller_id : $call->destination;
            $normalized = preg_replace('/\D+/', '', (string) $number) ?? '';
            $call->setRelation('matchedContact', $contacts->get($normalized));
            return $call;
        });

        // Return JSON for AJAX requests (embedded in dialer page)
        if ($request->query('format') === 'json' || $request->wantsJson()) {
            return response()->json(['calls' => $calls]);
        }

        return view('backend.pages.dialer.contacts-call-history', compact('calls'));
    }

    private function authorizeContacts(Request $request): void
    {
        abort_unless($request->user()?->can('contacts.view') || $request->user()?->hasAnyRole(['Admin', 'Superadmin']), 403);
    }

    private function authorizeContactPermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission) || $request->user()?->hasAnyRole(['Admin', 'Superadmin']), 403);
    }

    private function validateContactForm(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'avatar_url' => ['nullable', 'url', 'max:2048'],
        ]);

        foreach ($data as $field => $value) {
            $data[$field] = filled($value) ? trim((string) $value) : null;
        }
        if ($data['email']) $data['email'] = strtolower($data['email']);

        return $data;
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}
