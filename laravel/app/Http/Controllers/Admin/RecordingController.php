<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecordingSearchRequest;
use App\Models\CallLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RecordingController extends Controller
{
    public function index(RecordingSearchRequest $request): View
    {
        Gate::authorize('recording.view');

        [$recordings, $filters] = $this->recordingListing($request);
        $users = $this->userOptions();

        return view('backend.pages.recordings.index', [
            'recordings' => $recordings,
            'users' => $users,
            'filters' => $filters,
        ]);
    }

    public function search(RecordingSearchRequest $request): JsonResponse
    {
        Gate::authorize('recording.view');

        [$recordings] = $this->recordingListing($request);

        return response()->json([
            'html' => view('backend.pages.recordings._table', compact('recordings'))->render(),
        ]);
    }

    public function download(CallLog $callLog)
    {
        Gate::authorize('recording.download');

        if (! $callLog->recording_path) {
            abort(404, __('Recording not found.'));
        }

        $disk = $this->recordingsDisk();

        if (! Storage::disk($disk)->exists($callLog->recording_path)) {
            abort(404, __('Recording file not found on disk.'));
        }

        $downloadName = $this->prepareDownloadName(
            request()->string('download_name')->toString(),
            basename($callLog->recording_path)
        );

        return Storage::disk($disk)->download($callLog->recording_path, $downloadName);
    }

    public function destroy(CallLog $callLog)
    {
        Gate::authorize('recording.delete');

        $disk = $this->recordingsDisk();

        if ($callLog->recording_path && Storage::disk($disk)->exists($callLog->recording_path)) {
            Storage::disk($disk)->delete($callLog->recording_path);
        }

        $callLog->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'message' => __('Recording deleted successfully.'),
            ]);
        }

        return redirect()
            ->route('admin.recordings.index')
            ->with('status', __('Recording deleted successfully.'));
    }

    /**
     * @return array{0:\Illuminate\Contracts\Pagination\LengthAwarePaginator,1:array<string,mixed>}
     */
    protected function recordingListing(RecordingSearchRequest $request): array
    {
        $filters = Arr::only($request->validated(), [
            'phone_number',
            'start_date',
            'end_date',
            'user_id',
            'download_name',
        ]);

        $query = CallLog::query()
            ->with(['user'])
            ->withRecording()
            ->forPhone($filters['phone_number'] ?? null)
            ->forUser(isset($filters['user_id']) ? (int) $filters['user_id'] : null)
            ->withinPeriod($filters['start_date'] ?? null, $filters['end_date'] ?? null)
            ->orderByDesc('created_at');

        $recordings = $query->paginate(20)->appends($filters);

        return [$recordings, $filters];
    }

    protected function userOptions()
    {
        return User::orderBy('external_name')
            ->orderBy('email')
            ->get(['id', 'external_name', 'email']);
    }

    protected function recordingsDisk(): string
    {
        return config('filesystems.recordings_disk', 'recordings');
    }

    protected function prepareDownloadName(?string $requestedName, string $fallback): string
    {
        if (blank($requestedName)) {
            return $fallback;
        }

        $sanitized = preg_replace('/[\\\\\\/]+/', '-', $requestedName);
        $sanitized = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $sanitized);
        $sanitized = trim($sanitized, '-_.');

        return $sanitized !== '' ? $sanitized : $fallback;
    }
}
