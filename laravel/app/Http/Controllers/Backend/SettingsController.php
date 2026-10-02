<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Enums\ActionType;
use App\Http\Controllers\Controller;
use App\Services\CacheService;
use App\Services\EnvWriter;
use App\Services\SettingService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingService $settingService,
        private readonly EnvWriter $envWriter,
        private readonly CacheService $cacheService
    ) {
        // The cacheService is used in the EnvWriter for cache clearing operations
    }

    public function index($tab = null): Renderable
    {
        $this->checkAuthorization(Auth::user(), ['settings.view']);

        $tab = $tab ?? request()->input('tab', 'general');

        return view('backend.pages.settings.index', compact('tab'))
            ->with([
                'breadcrumbs' => [
                    'title' => __('Settings'),
                ],
            ]);
    }

    public function store(Request $request)
    {
        // The AI agent box title is free text that is rendered straight into the
        // dialer page, so it is length limited and stripped of markup here.
        // Blank input falls back to the default rather than persisting an empty
        // heading.
        $validated = $request->validate([
            'ai_box_title' => ['nullable', 'string', 'max:60'],
            'ai_box_subtitle' => ['nullable', 'string', 'max:60'],
        ]);

        // Restrict specific fields in demo mode.
        if (config('app.demo_mode', false)) {
            $restrictedFields = ld_apply_filters('settings_restricted_fields', ['app_name', 'google_analytics_script']);
            $fields = $request->except($restrictedFields);
        } else {
            $fields = $request->all();
        }

        foreach (['ai_box_title', 'ai_box_subtitle'] as $fieldName) {
            if (! array_key_exists($fieldName, $validated)) {
                continue;
            }

            $fields[$fieldName] = trim(strip_tags((string) $validated[$fieldName]));
        }

        $this->checkAuthorization(Auth::user(), ['settings.edit']);

        $uploadPath = 'uploads/settings';

        foreach ($fields as $fieldName => $fieldValue) {
            if ($request->hasFile($fieldName)) {
                deleteImageFromPublic((string) config($fieldName));
                $fileUrl = storeImageAndGetUrl($request, $fieldName, $uploadPath);
                $this->settingService->addSetting($fieldName, $fileUrl);
            } else {
                $this->settingService->addSetting($fieldName, $fieldValue);
            }
        }

        $this->envWriter->batchWriteKeysToEnvFile($fields);

        $this->storeActionLog(ActionType::UPDATED, [
            'settings' => $fields,
        ]);

        return redirect()->back()->with('success', 'Settings saved successfully.');
    }
}
