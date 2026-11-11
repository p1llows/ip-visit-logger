<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'IP Visit Logger') }}</title>
    <style>
        :root {
            --bg: #f7f7f5; --panel: #ffffff; --text: #1c1c1a; --muted: #77776f; --line: #e4e4de;
            --head: #f0f0ec; --accent: #0f766e; --hover: #fafaf8;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0f0f0e; --panel: #171716; --text: #ecece8; --muted: #8e8e86; --line: #2b2b28;
                --head: #1d1d1b; --accent: #2dd4bf; --hover: #1b1b19;
            }
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text); font: 15px/1.5 system-ui, "Segoe UI", Roboto, sans-serif; }
        .wrap { max-width: 1000px; margin: 0 auto; padding: 36px 16px 64px; }
        .mono { font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 13px; }

        .top { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
        h1 { font-size: 26px; font-weight: 700; letter-spacing: -0.02em; margin: 0; }
        .status { color: var(--muted); font-size: 13px; }
        .status i { display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: var(--accent); margin-right: 7px; vertical-align: 1px; }

        .stats { display: grid; grid-template-columns: 1.4fr 1fr 1fr; background: var(--panel); border: 1px solid var(--line); border-radius: 8px; margin-bottom: 20px; }
        .stat { padding: 16px 20px; min-width: 0; }
        .stat + .stat { border-left: 1px solid var(--line); }
        .label { font-size: 12px; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); }
        .value { font-size: 30px; font-weight: 650; letter-spacing: -0.02em; margin-top: 2px; font-variant-numeric: tabular-nums; }
        .value.ip { font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 22px; font-weight: 600; letter-spacing: 0; padding: 6px 0 4px; color: var(--accent); overflow-wrap: anywhere; }

        .panel { background: var(--panel); border: 1px solid var(--line); border-radius: 8px; overflow: hidden; }
        .panel-head { display: flex; justify-content: space-between; align-items: baseline; padding: 14px 20px; border-bottom: 1px solid var(--line); }
        .panel-head h2 { font-size: 15px; font-weight: 600; margin: 0; }
        .panel-head span { color: var(--muted); font-size: 13px; }
        .scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); background: var(--head); padding: 8px 20px; border-bottom: 1px solid var(--line); white-space: nowrap; }
        td { padding: 11px 20px; border-bottom: 1px solid var(--line); vertical-align: top; }
        tbody tr:last-child td { border-bottom: 0; }
        tbody tr:hover { background: var(--hover); }
        td.time { white-space: nowrap; }
        td.time small { display: block; color: var(--muted); font-size: 12px; }
        td.ip { color: var(--accent); white-space: nowrap; }
        td.url { max-width: 340px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        td.url .host { color: var(--muted); }
        a { color: inherit; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .muted { color: var(--muted); }
        .note { padding: 28px 20px; color: var(--muted); }
        .note code { font-family: ui-monospace, Menlo, Consolas, monospace; background: var(--head); border: 1px solid var(--line); border-radius: 4px; padding: 1px 6px; color: var(--text); }

        .pager { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; padding: 12px 20px; border-top: 1px solid var(--line); background: var(--head); font-size: 13px; color: var(--muted); }
        .pager .btns { display: flex; align-items: center; gap: 8px; }
        .pager button { font: inherit; color: var(--text); background: var(--panel); border: 1px solid var(--line); border-radius: 6px; padding: 5px 12px; cursor: pointer; }
        .pager button:hover:not(:disabled) { border-color: var(--accent); color: var(--accent); }
        .pager button:disabled { opacity: .4; cursor: default; }

        @media (max-width: 680px) {
            .stats { grid-template-columns: 1fr 1fr; }
            .stat:first-child { grid-column: 1 / -1; border-bottom: 1px solid var(--line); }
            .stat:nth-child(2) { border-left: 0; }
            .hide-sm { display: none; }
            th, td { padding-left: 14px; padding-right: 14px; }
            td.url { max-width: 170px; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <h1>IP Visit Logger</h1>
        <div class="status"><i></i>Updates every 5 seconds</div>
    </div>

    <div class="stats">
        <div class="stat"><div class="label">Your IP</div><div class="value ip" id="myIp">{{ $myIp }}</div></div>
        <div class="stat"><div class="label">Total visits</div><div class="value" id="total">{{ $total }}</div></div>
        <div class="stat"><div class="label">Unique IPs</div><div class="value" id="unique">{{ $unique }}</div></div>
    </div>

    <div class="panel">
        <div class="panel-head"><h2>Recent visits</h2><span id="updated"></span></div>

        @if (! $canSeeAll)
            <div class="note">The visitor list is private. Add <code>?key=...</code> to the URL to view it.</div>
        @else
            <div class="scroll">
                <table>
                    <thead><tr><th>Time</th><th>IP</th><th>Link visited</th><th class="hide-sm">Came from</th></tr></thead>
                    <tbody id="rows"></tbody>
                </table>
            </div>
            <div class="note" id="empty" hidden>No visits yet. Open the site from another tab or device and it will show up here.</div>
            <div class="pager" id="pager">
                <span id="range"></span>
                <span class="btns">
                    <button type="button" id="prev">&larr; Newer</button>
                    <span id="pageOf"></span>
                    <button type="button" id="next">Older &rarr;</button>
                </span>
            </div>
        @endif
    </div>
</div>

<script>
    const canSeeAll = @json($canSeeAll);
    const initial = @json($recent);
    let page = @json($page);
    let pages = @json($pages);
    const perPage = @json($perPage);
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

    // Shows "host" dimmed and "/path?query" normal, so the part that changes stands out.
    function prettyUrl(u) {
        const href = safeHref(u);
        if (!href) return esc(u);
        try {
            const p = new URL(href);
            const rest = (p.pathname === '/' ? '' : p.pathname) + p.search;
            return `<a href="${esc(href)}" target="_blank" rel="noopener noreferrer"><span class="host">${esc(p.host)}</span>${esc(rest)}</a>`;
        } catch (e) {
            return esc(u);
        }
    }

    function render(list) {
        const rows = document.getElementById('rows');
        if (!rows) return;
        document.getElementById('empty').hidden = list.length > 0;
        rows.innerHTML = list.map(v => {
            const when = new Date(v.time.replace(' ', 'T') + 'Z');
            const link = v.url ?? v.path;
            return `<tr>
                <td class="time"><span data-t="${when.getTime()}">${ago(when)}</span><small>${esc(when.toLocaleString())}</small></td>
                <td class="ip mono">${esc(v.ip)}</td>
                <td class="url mono" title="${esc(link)}">${prettyUrl(link)}</td>
                <td class="url mono hide-sm" title="${esc(v.referer || '')}">${v.referer ? prettyUrl(v.referer) : '<span class="muted">-</span>'}</td>
            </tr>`;
        }).join('');
    }

    function tickTimes() {
        document.querySelectorAll('span[data-t]').forEach(el => el.textContent = ago(+el.dataset.t));
    }

    function stamp() {
        document.getElementById('updated').textContent = 'Updated ' + new Date().toLocaleTimeString();
    }

    function renderPager(total) {
        if (!document.getElementById('pager')) return;
        const from = total === 0 ? 0 : (page - 1) * perPage + 1;
        const to = Math.min(total, page * perPage);
        document.getElementById('range').textContent = total ? `Showing ${from}-${to} of ${total}` : '';
        document.getElementById('pageOf').textContent = `Page ${page} of ${pages}`;
        document.getElementById('prev').disabled = page <= 1;
        document.getElementById('next').disabled = page >= pages;
        document.getElementById('pager').hidden = pages <= 1;
    }

    async function refresh() {
        try {
            // Keep ?key=... and send the page we are looking at.
            const params = new URLSearchParams(location.search);
            params.set('page', page);
            const r = await fetch('/visits.json?' + params, {cache: 'no-store'});
            if (!r.ok) return;
            const d = await r.json();
            document.getElementById('total').textContent = d.total;
            document.getElementById('unique').textContent = d.unique;
            page = d.page;
            pages = d.pages;
            if (canSeeAll && d.canSeeAll) {
                render(d.recent);
                renderPager(d.total);
            }
            stamp();
        } catch (e) {}
    }

    function go(p) {
        page = Math.min(pages, Math.max(1, p));
        // Remember the page in the address bar so a reload stays on it.
        const params = new URLSearchParams(location.search);
        page === 1 ? params.delete('page') : params.set('page', page);
        const qs = params.toString();
        history.replaceState(null, '', location.pathname + (qs ? '?' + qs : ''));
        refresh();
    }

    if (canSeeAll) {
        render(initial);
        renderPager(@json($total));
        document.getElementById('prev').addEventListener('click', () => go(page - 1));
        document.getElementById('next').addEventListener('click', () => go(page + 1));
    }
    stamp();
    setInterval(refresh, 5000);
    setInterval(tickTimes, 1000);
</script>
</body>
</html>
