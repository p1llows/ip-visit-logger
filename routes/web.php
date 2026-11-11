<?php

use App\Support\VisitLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (Request $request) => view('welcome', VisitLog::summary($request)));

// Polled by the home page to refresh itself. Not logged as a visit.
Route::get('/visits.json', fn (Request $request) => response()->json(VisitLog::summary($request))
    ->header('Cache-Control', 'no-store'));

// View the visitor log: /logs?key=<LOGS_KEY from .env>
Route::get('/logs', function (Request $request) {
    abort_unless(VisitLog::canSeeAll($request), 403);

    $file = storage_path('logs/visits.log');
    $contents = File::exists($file) ? File::get($file) : '(no visits logged yet)';

    // Browsing history never goes out without the key.
    if (! VisitLog::canSeeBrowsing($request)) {
        $contents = implode("\n", array_filter(
            explode("\n", $contents),
            fn ($l) => ! str_contains($l, ' browse {')
        ));
    }

    return response($contents, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
});

// The browser extension reports each site you open here.
Route::post('/api/browse', function (Request $request) {
    $key = (string) config('app.browse_key');
    abort_unless($key !== '' && hash_equals($key, (string) $request->header('X-Browse-Key')), 403);

    $data = $request->validate(['url' => ['required', 'string', 'max:2000']]);
    abort_unless(preg_match('#^https?://#i', $data['url']), 422);

    Log::channel('visits')->info('browse', [
        'ip' => $request->ip(),
        'path' => (string) parse_url($data['url'], PHP_URL_PATH) ?: '/',
        'url' => $data['url'],
        'referer' => null,
        'user_agent' => $request->userAgent(),
    ]);

    return response()->noContent();
})->middleware('throttle:120,1');
