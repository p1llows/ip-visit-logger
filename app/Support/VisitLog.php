<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class VisitLog
{
    /** @return array<int, array{time:string, ip:string, path:string, url:?string, referer:?string}> */
    public static function all(): array
    {
        $file = storage_path('logs/visits.log');
        $visits = [];

        if (! File::exists($file)) {
            return $visits;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (preg_match('/^\[([^\]]+)\] \S+ visit (\{.*\})/', $line, $m)) {
                $data = json_decode($m[2], true) ?: [];
                $visits[] = [
                    'time' => $m[1],
                    'ip' => $data['ip'] ?? '?',
                    'path' => $data['path'] ?? '/',
                    'url' => $data['url'] ?? null,
                    'referer' => $data['referer'] ?? null,
                ];
            }
        }

        return $visits;
    }

    public static function canSeeAll(Request $request): bool
    {
        $key = (string) config('app.logs_key');

        return config('app.logs_public')
            || ($key !== '' && hash_equals($key, (string) $request->query('key')));
    }

    public static function summary(Request $request): array
    {
        $visits = self::all();
        $canSeeAll = self::canSeeAll($request);

        $perPage = 15;
        $total = count($visits);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($pages, max(1, (int) $request->query('page', 1)));

        return [
            'myIp' => $request->ip(),
            'canSeeAll' => $canSeeAll,
            'total' => $total,
            'unique' => count(array_unique(array_column($visits, 'ip'))),
            'page' => $page,
            'pages' => $pages,
            'perPage' => $perPage,
            // Newest first, one page at a time.
            'recent' => $canSeeAll ? array_slice(array_reverse($visits), ($page - 1) * $perPage, $perPage) : [],
        ];
    }
}
