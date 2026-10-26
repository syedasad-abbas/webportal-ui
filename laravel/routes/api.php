<?php

use App\Http\Controllers\Backend\Api\TermsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

// API endpoint to get translations for a specific language
Route::get('/translations/{lang}', function (string $lang) {
    $path = resource_path("lang/{$lang}.json");

    if (! file_exists($path)) {
        return response()->json(['error' => 'Language not found'], 404);
    }

    $translations = json_decode(file_get_contents($path), true);

    return response()->json($translations);
});

// Admin API routes
Route::middleware(['auth', 'web'])->prefix('admin')->name('admin.api.')->group(function () {
    // Terms API
    Route::post('/terms/{taxonomy}', [TermsController::class, 'store'])->name('terms.store');
    Route::put('/terms/{taxonomy}/{id}', [TermsController::class, 'update'])->name('terms.update');
    Route::delete('/terms/{taxonomy}/{id}', [TermsController::class, 'destroy'])->name('terms.destroy');
});

// Isolated lab CRM: disabled until explicitly configured with a strong API token.
Route::prefix('test-crm/v1')->middleware([\App\Http\Middleware\TestCrmToken::class, 'throttle:120,1'])->group(function () {
    Route::get('reports/summary', [\App\Http\Controllers\Api\TestCrmController::class, 'summary']);
    Route::apiResource('customers', \App\Http\Controllers\Api\TestCrmController::class)->parameters(['customers' => 'customer']);
});

// Dashboard API: proxy to Node.js backend for live call data
Route::middleware(['auth', 'web'])->prefix('dashboard')->name('dashboard.api.')->group(function () {
    Route::get('/active-calls', function () {
        $nodeUrl = config('app.node_backend_url', 'http://127.0.0.1:4000');
        try {
            $response = \Illuminate\Support\Facades\Http::get("{$nodeUrl}/api/active-calls");
            return response()->json($response->json(), $response->status());
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'calls' => [], 'count' => 0, 'error' => $e->getMessage()], 200);
        }
    })->name('active-calls');

    Route::get('/crm-summary', function () {
        $client = app(\App\Services\CrmApiClient::class);
        try {
            $response = $client->request('GET', 'reports/summary');
            return response()->json(['ok' => true, 'data' => $response['data'] ?? null]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'data' => null, 'error' => $e->getMessage()], 200);
        }
    })->name('crm-summary');

    Route::get('/health', function () {
        $nodeUrl = config('app.node_backend_url', 'http://127.0.0.1:4000');
        try {
            $response = \Illuminate\Support\Facades.Http::get("{$nodeUrl}/health");
            return response()->json($response->json(), $response->status());
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'error' => $e->getMessage()], 200);
        }
    })->name('health');

    // Socket.io auth token for real-time updates
    Route::get('/socket-token', function () {
        $secret = env('JWT_SECRET', 'your-super-secret-jwt-key-here-32-chars-min');
        $user = auth()->user();
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'id' => $user->id,
            'iat' => time(),
            'exp' => time() + 3600
        ]));
        $signature = base64_encode(hash_hmac('sha256', "$header.$payload", $secret, true));
        // Convert to URL-safe base64
        $b64url = function ($s) { return str_replace(['+', '/', '='], ['-', '_', ''], $s); };
        $token = "{$b64url($header)}.{$b64url($payload)}.{$b64url($signature)}";
        return response()->json(['token' => $token]);
    })->name('socket-token');

    // Call control proxies to Node.js backend (internal-token endpoints)
    Route::post('/calls/{uuid}/hangup', function (Request $request, string $uuid) {
        $nodeUrl = config('app.node_backend_url', 'http://127.0.0.1:4000');
        try {
            $response = \Illuminate\Support\Facades\Http::withToken(config('services.backend.internal_token'))
                ->post("{$nodeUrl}/calls/internal/{$uuid}/hangup", [
                    'durationSeconds' => $request->integer('durationSeconds', 0)
                ]);
            return response()->json($response->json(), $response->status());
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 200);
        }
    })->name('calls.hangup');

    Route::post('/calls/{uuid}/mute', function (Request $request, string $uuid) {
        $nodeUrl = config('app.node_backend_url', 'http://127.0.0.1:4000');
        try {
            $response = \Illuminate\Support\Facades\Http::withToken(config('services.backend.internal_token'))
                ->post("{$nodeUrl}/calls/internal/{$uuid}/mute");
            return response()->json($response->json(), $response->status());
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 200);
        }
    })->name('calls.mute');

    Route::post('/calls/{uuid}/unmute', function (Request $request, string $uuid) {
        $nodeUrl = config('app.node_backend_url', 'http://127.0.0.1:4000');
        try {
            $response = \Illuminate\Support\Facades.Http::withToken(config('services.backend.internal_token'))
                ->post("{$nodeUrl}/calls/internal/{$uuid}/unmute");
            return response()->json($response->json(), $response->status());
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 200);
        }
    })->name('calls.unmute');

    Route::post('/calls/{uuid}/dtmf', function (Request $request, string $uuid) {
        $nodeUrl = config('app.node_backend_url', 'http://127.0.0.1:4000');
        $validated = $request->validate(['digits' => 'required|string|max:20']);
        try {
            $response = \Illuminate\Support\Facades.Http::withToken(config('services.backend.internal_token'))
                ->post("{$nodeUrl}/calls/internal/{$uuid}/dtmf", [
                    'digits' => $validated['digits']
                ]);
            return response()->json($response->json(), $response->status());
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 200);
        }
    })->name('calls.dtmf');

    Route::get('/calls/{uuid}/status', function (string $uuid) {
        $nodeUrl = config('app.node_backend_url', 'http://127.0.0.1:4000');
        try {
            $response = \Illuminate\Support\Facades.Http::withToken(config('services.backend.internal_token'))
                ->get("{$nodeUrl}/calls/internal/{$uuid}/status");
            return response()->json($response->json(), $response->status());
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 200);
        }
    })->name('calls.status');
});

Route::post('/internal/crm-workflow', [\App\Http\Controllers\Api\CrmWorkflowController::class, 'handle'])->middleware('throttle:60,1');

Route::post('/internal/crm-proxy', [\App\Http\Controllers\Api\CrmWorkflowController::class, 'proxy'])->middleware('throttle:120,1');
