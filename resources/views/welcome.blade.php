<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'IP Visit Logger') }}</title>
    <style>
        :root { --bg: #ffffff; --text: #1a1a1a; --muted: #6a6a6a; --line: #dcdcdc; --head: #f3f3f3; }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #111111; --text: #e6e6e6; --muted: #8c8c8c; --line: #2c2c2c; --head: #181818; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text); font: 15px/1.45 system-ui, "Segoe UI", Roboto, sans-serif; }
        .wrap { max-width: 960px; margin: 0 auto; padding: 28px 16px 56px; }
        .mono { font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 13px; }
        h1 { font-size: 20px; font-weight: 600; margin: 0 0 14px; }
        .meta { display: flex; flex-wrap: wrap; gap: 6px 28px; padding: 10px 0; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); margin-bottom: 28px; }
        .meta div span { color: var(--muted); margin-right: 6px; }
        .meta b { font-weight: 600; font-variant-numeric: tabular-nums; }
        h2 { font-size: 15px; font-weight: 600; margin: 0 0 8px; display: flex; justify-content: space-between; align-items: baseline; }
        h2 small { font-weight: 400; color: var(--muted); font-size: 13px; }
        .scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-weight: 500; font-size: 13px; color: var(--muted); background: var(--head); padding: 6px 10px; border-bottom: 1px solid var(--line); white-space: nowrap; }
        td { padding: 7px 10px; border-bottom: 1px solid var(--line); vertical-align: top; }
        td.time { white-space: nowrap; color: var(--muted); }
        td.url { max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        a { color: inherit; }
        .muted { color: var(--muted); }
        .note { color: var(--muted); padding: 14px 0; }
        .note code { font-family: ui-monospace, Menlo, Consolas, monospace; }
        @media (max-width: 640px) { .hide-sm { display: none; } td.url { max-width: 170px; } }
    </style>
</head>
<body>
<div class="wrap">
    <h1>IP Visit Logger</h1>
    <div class="meta">
        <div><span>Your IP</span><b class="mono" id="myIp">{{ $myIp }}</b></div>
        <div><span>Visits</span><b id="total">{{ $total }}</b></div>
        <div><span>Unique IPs</span><b id="unique">{{ $unique }}</b></div>
    </div>

    <h2>Recent visits <small id="updated"></small></h2>

    @if (! $canSeeAll)
        <p class="note">The visitor list is private. Add <code>?key=...</code> to the URL to view it.</p>
    @else
        <div class="scroll">
            <table>
                <thead><tr><th>Time</th><th>IP</th><th>Link visited</th><th class="hide-sm">Came from</th></tr></thead>
                <tbody id="rows"></tbody>
            </table>
        </div>
        <p class="note" id="empty" hidden>No visits yet.</p>
    @endif
</div>
<script>
    const canSeeAll = @json($canSeeAll);
    const initial = @json($recent);
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const safeHref = u => /^https?:\/\//i.test(u || '') ? u : null;

    function ago(date) {
        const s = Math.max(0, Math.round((Date.now() - date) / 1000));
        if (s < 5) return 'just now';
        if (s < 60) return s + 's ago';
        if (s < 3600) return Math.floor(s / 60) + 'm ago';
        if (s < 86400) return Math.floor(s / 3600) + 'h ago';
        return Math.floor(s / 86400) + 'd ago';
    }

    function render(list) {
        const rows = document.getElementById('rows');
        if (!rows) return;
        document.getElementById('empty').hidden = list.length > 0;
        rows.innerHTML = list.map(v => {
            const when = new Date(v.time.replace(' ', 'T') + 'Z');
            const link = v.url ?? v.path;
            const href = safeHref(link);
            const ref = v.referer ? esc(v.referer) : '<span class="muted">-</span>';
            return `<tr>
                <td class="time" data-t="${when.getTime()}" title="${esc(when.toLocaleString())}">${ago(when)}</td>
                <td class="mono">${esc(v.ip)}</td>
                <td class="url mono" title="${esc(link)}">${href ? `<a href="${esc(href)}" target="_blank" rel="noopener noreferrer">${esc(link)}</a>` : esc(link)}</td>
                <td class="url mono hide-sm" title="${esc(v.referer || '')}">${ref}</td>
            </tr>`;
        }).join('');
    }

    function tickTimes() {
        document.querySelectorAll('td[data-t]').forEach(el => el.textContent = ago(+el.dataset.t));
    }

    async function refresh() {
        try {
            const r = await fetch('/visits.json' + location.search, {cache: 'no-store'});
            if (!r.ok) throw new Error();
            const d = await r.json();
            document.getElementById('total').textContent = d.total;
            document.getElementById('unique').textContent = d.unique;
            if (canSeeAll && d.canSeeAll) render(d.recent);
            document.getElementById('updated').textContent = 'Updated ' + new Date().toLocaleTimeString();
        } catch (e) {
        }
    }

    if (canSeeAll) render(initial);
    document.getElementById('updated').textContent = 'Updated ' + new Date().toLocaleTimeString();
    setInterval(refresh, 5000);
    setInterval(tickTimes, 1000);
</script>
</body>
</html>
