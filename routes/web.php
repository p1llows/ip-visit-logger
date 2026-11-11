<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    $file = storage_path('logs/visits.log');
    $visits = [];

    if (File::exists($file)) {
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (preg_match('/^\[([^\]]+)\] \S+ visit (\{.*\})/', $line, $m)) {
                $data = json_decode($m[2], true) ?: [];
                $visits[] = ['time' => $m[1], 'ip' => $data['ip'] ?? '?', 'path' => $data['path'] ?? '/', 'url' => $data['url'] ?? null, 'referer' => $data['referer'] ?? null];
            }
        }
    }

    return view('welcome', [
        'myIp' => $request->ip(),
        'canSeeAll' => config('app.logs_public')
            || ((string) config('app.logs_key') !== '' && hash_equals((string) config('app.logs_key'), (string) $request->query('key'))),
        'total' => count($visits),
        'unique' => count(array_unique(array_column($visits, 'ip'))),
        'recent' => array_slice(array_reverse($visits), 0, 20),
    ]);
});

// View the visitor log: /logs?key=<LOGS_KEY from .env>
Route::get('/logs', function (Request $request) {
    $key = (string) config('app.logs_key');
    abort_unless(
        config('app.logs_public') || ($key !== '' && hash_equals($key, (string) $request->query('key'))),
        403
    );

    $file = storage_path('logs/visits.log');
    $contents = File::exists($file) ? File::get($file) : '(no visits logged yet)';

    return response($contents, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
});
