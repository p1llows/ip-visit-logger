<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class VisitLog
{
    /**
     * Rows are either "visit" (someone opened this app) or "browse" (a site opened
     * in the owner's browser, reported by the extension).
     *
     * @return array<int, array{type:string, time:string, ip:string, path:string, url:?string, referer:?string}>
     */
    public static function all(): array
    {
        $file = storage_path('logs/visits.log');
        $rows = [];

        if (! File::exists($file)) {
            return $rows;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (preg_match('/^\[([^\]]+)\] \S+ (visit|browse) (\{.*\})/', $line, $m)) {
                $data = json_decode($m[3], true) ?: [];
                $rows[] = [
                    'type' => $m[2],
                    'time' => $m[1],
                    'ip' => $data['ip'] ?? '?',
                    'path' => $data['path'] ?? '/',
                    'url' => $data['url'] ?? null,
                    'referer' => $data['referer'] ?? null,
                ];
            }
        }

        return $rows;
    }

    public static function hasKey(Request $request): bool
    {
        $key = (string) config('app.logs_key');

        return $key !== '' && hash_equals($key, (string) $request->query('key'));
    }

    /** The visitor table may be open to everyone when LOGS_PUBLIC is on. */
    public static function canSeeAll(Request $request): bool
    {
        return config('app.logs_public') || self::hasKey($request);
    }

    /** Browsing history is private: it is only ever shown with the key, never publicly. */
    public static function canSeeBrowsing(Request $request): bool
    {
        return self::hasKey($request);
    }

    public static function summary(Request $request): array
    {
        $rows = self::all();

        if (! self::canSeeBrowsing($request)) {
            $rows = array_values(array_filter($rows, fn ($r) => $r['type'] === 'visit'));
        }

        $canSeeAll = self::canSeeAll($request);

        return [
            'myIp' => $request->ip(),
            'canSeeAll' => $canSeeAll,
            'total' => count($rows),
            'unique' => count(array_unique(array_column($rows, 'ip'))),
            'recent' => $canSeeAll ? array_slice(array_reverse($rows), 0, 30) : [],
        ];
    }
}
