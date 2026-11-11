<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'IP Visit Logger') }}</title>
    <style>
        body { margin: 0; padding: 2rem 1rem; background: #0a0a0a; color: #eee; font-family: system-ui, sans-serif; }
        main { max-width: 1000px; margin: 0 auto; }
        h1 { margin-top: 0; }
        .cards { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem; }
        .card { flex: 1; min-width: 160px; background: #161615; border: 1px solid #2a2a28; border-radius: 8px; padding: 1rem; }
        .card small { color: #999; display: block; margin-bottom: .25rem; }
        .card strong { font-size: 1.5rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .5rem; border-bottom: 1px solid #2a2a28; }
        th { color: #999; font-weight: 500; }
    </style>
</head>
<body>
<main>
    <h1>IP Visit Logger</h1>
    <div class="cards">
        <div class="card"><small>Your IP</small><strong id="myIp">{{ $myIp }}</strong></div>
        <div class="card"><small>Total visits</small><strong id="total">{{ $total }}</strong></div>
        <div class="card"><small>Unique IPs</small><strong id="unique">{{ $unique }}</strong></div>
    </div>

    <h2>Recent visits</h2>
    @if (! $canSeeAll)
        <p>The visitor list is private. Add <code>?key=...</code> to the URL to view it.</p>
    @elseif (count($recent))
        <table>
            <thead><tr><th>Time (UTC)</th><th>IP</th><th>Visited link</th><th>Came from</th></tr></thead>
            <tbody id="rows">
            @foreach ($recent as $v)
                <tr><td>{{ $v['time'] }}</td><td>{{ $v['ip'] }}</td><td>{{ $v['url'] ?? $v['path'] }}</td><td>{{ $v['referer'] ?: '-' }}</td></tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p>No visits logged yet.</p>
    @endif
</main>
<script>
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    async function refresh() {
        try {
            const r = await fetch('/visits.json' + location.search, {cache: 'no-store'});
            if (!r.ok) return;
            const d = await r.json();
            document.getElementById('total').textContent = d.total;
            document.getElementById('unique').textContent = d.unique;
            const rows = document.getElementById('rows');
            if (rows && d.canSeeAll) {
                rows.innerHTML = d.recent.map(v =>
                    `<tr><td>${esc(v.time)}</td><td>${esc(v.ip)}</td><td>${esc(v.url ?? v.path)}</td><td>${esc(v.referer || '-')}</td></tr>`
                ).join('');
            }
        } catch (e) {}
    }
    setInterval(refresh, 5000);
</script>
</body>
</html>
