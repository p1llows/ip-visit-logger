<?php

use App\Support\VisitLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
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

    return response($contents, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
});
