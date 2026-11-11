<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'IP Visit Logger') }}</title>
    <style>
        :root {
            --bg: #f5f6f8; --surface: #ffffff; --border: #e3e6eb; --text: #14171f; --muted: #6b7280;
            --accent: #4f46e5; --accent-soft: #eef0ff; --ok: #16a34a; --row-hover: #f8f9fc;
            --shadow: 0 1px 2px rgba(16, 24, 40, .05), 0 1px 3px rgba(16, 24, 40, .06);
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0b0d12; --surface: #14171f; --border: #232836; --text: #e8eaf0; --muted: #8b93a7;
                --accent: #818cf8; --accent-soft: #1d2140; --ok: #4ade80; --row-hover: #181c27;
                --shadow: none;
            }
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; background: var(--bg); color: var(--text);
            font: 15px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .wrap { max-width: 1040px; margin: 0 auto; padding: 32px 16px 64px; }

        header { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; }
        .brand { display: flex; align-items: center; gap: 12px; }
        .logo {
            width: 40px; height: 40px; border-radius: 10px; background: var(--accent-soft); color: var(--accent);
            display: grid; place-items: center;
        }
        h1 { font-size: 20px; margin: 0; letter-spacing: -.01em; }
        .sub { color: var(--muted); font-size: 13px; margin: 0; }
        .live {
            display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 999px;
            background: var(--surface); border: 1px solid var(--border); font-size: 13px; color: var(--muted);
        }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--ok); animation: pulse 2s infinite; }
        .live.off .dot { background: var(--muted); animation: none; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 color-mix(in srgb, var(--ok) 50%, transparent); } 70% { box-shadow: 0 0 0 7px transparent; } 100% { box-shadow: 0 0 0 0 transparent; } }

        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px 20px; box-shadow: var(--shadow); min-width: 0; }
        .stat .label { color: var(--muted); font-size: 13px; }
        .stat .value { font-size: 28px; font-weight: 650; letter-spacing: -.02em; margin-top: 4px; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
        .stat.me .value { font-size: 22px; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; padding-top: 5px; }

        .panel { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; box-shadow: var(--shadow); overflow: hidden; }
        .panel-head { display: flex; justify-content: space-between; align-items: baseline; padding: 16px 20px; border-bottom: 1px solid var(--border); }
        .panel-head h2 { font-size: 16px; margin: 0; }
        .panel-head span { color: var(--muted); font-size: 13px; }
        .scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); padding: 10px 20px; background: var(--bg); white-space: nowrap; }
        td { padding: 12px 20px; border-top: 1px solid var(--border); vertical-align: middle; }
        tbody tr:hover { background: var(--row-hover); }
        tbody tr.new { animation: flash 1.6s ease-out; }
        @keyframes flash { from { background: var(--accent-soft); } to { background: transparent; } }
        .time b { display: block; font-weight: 500; }
        .time small { color: var(--muted); }
        .ip { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 13px; background: var(--accent-soft); color: var(--accent); padding: 3px 8px; border-radius: 6px; white-space: nowrap; }
        .url { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 13px; max-width: 360px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .url a { color: inherit; text-decoration: none; }
        .url a:hover { color: var(--accent); text-decoration: underline; }
        .muted { color: var(--muted); }

        .empty { text-align: center; padding: 48px 20px; color: var(--muted); }
        .empty strong { display: block; color: var(--text); margin-bottom: 4px; }
        .private { display: flex; gap: 12px; align-items: flex-start; padding: 20px; color: var(--muted); }
        .private code { background: var(--bg); border: 1px solid var(--border); padding: 1px 6px; border-radius: 5px; font-size: 13px; color: var(--text); }

        @media (max-width: 720px) {
            .stats { grid-template-columns: 1fr; }
            .hide-sm { display: none; }
            th, td { padding: 10px 14px; }
            .url { max-width: 180px; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <header>
        <div class="brand">
            <div class="logo">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15 15 0 0 1 0 20a15 15 0 0 1 0-20"/></svg>
            </div>
            <div>
                <h1>IP Visit Logger</h1>
                <p class="sub">Every visit to this site, logged by IP</p>
            </div>
        </div>
        <div class="live" id="live"><span class="dot"></span><span id="liveText">Live</span></div>
    </header>

    <section class="stats" aria-label="Summary">
        <div class="stat me"><div class="label">Your IP</div><div class="value" id="myIp">{{ $myIp }}</div></div>
        <div class="stat"><div class="label">Total visits</div><div class="value" id="total">{{ $total }}</div></div>
        <div class="stat"><div class="label">Unique IPs</div><div class="value" id="unique">{{ $unique }}</div></div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h2>Recent visits</h2>
            <span id="updated"></span>
        </div>

        @if (! $canSeeAll)
            <div class="private">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <div>The visitor list is private. Add <code>?key=...</code> to the URL to view it.</div>
            </div>
        @else
            <div class="scroll">
                <table>
                    <thead>
                    <tr><th>Time</th><th>IP</th><th>Visited link</th><th class="hide-sm">Came from</th></tr>
                    </thead>
                    <tbody id="rows"></tbody>
                </table>
            </div>
            <div class="empty" id="empty" hidden><strong>No visits yet</strong>Open the site from another tab or device and it will show up here.</div>
        @endif
    </section>
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

    let seen = new Set();
    function render(list, animate) {
        const rows = document.getElementById('rows');
        if (!rows) return;
        document.getElementById('empty').hidden = list.length > 0;
        rows.innerHTML = list.map(v => {
            const when = new Date(v.time.replace(' ', 'T') + 'Z');
            const id = v.time + v.ip + (v.url || v.path);
            const isNew = animate && !seen.has(id);
            const link = v.url ?? v.path;
            const href = safeHref(link);
            const ref = v.referer ? esc(v.referer) : '<span class="muted">Direct</span>';
            return `<tr class="${isNew ? 'new' : ''}">
                <td class="time"><b data-t="${when.getTime()}">${ago(when)}</b><small>${esc(when.toLocaleString())}</small></td>
                <td><span class="ip">${esc(v.ip)}</span></td>
                <td class="url" title="${esc(link)}">${href ? `<a href="${esc(href)}" target="_blank" rel="noopener noreferrer">${esc(link)}</a>` : esc(link)}</td>
                <td class="url hide-sm" title="${esc(v.referer || '')}">${ref}</td>
            </tr>`;
        }).join('');
        list.forEach(v => seen.add(v.time + v.ip + (v.url || v.path)));
    }

    function tickTimes() {
        document.querySelectorAll('b[data-t]').forEach(el => el.textContent = ago(+el.dataset.t));
    }

    async function refresh() {
        const live = document.getElementById('live');
        try {
            const r = await fetch('/visits.json' + location.search, {cache: 'no-store'});
            if (!r.ok) throw new Error();
            const d = await r.json();
            document.getElementById('total').textContent = d.total;
            document.getElementById('unique').textContent = d.unique;
            if (canSeeAll && d.canSeeAll) render(d.recent, true);
            live.classList.remove('off');
            document.getElementById('liveText').textContent = 'Live';
            document.getElementById('updated').textContent = 'Updated ' + new Date().toLocaleTimeString();
        } catch (e) {
            live.classList.add('off');
            document.getElementById('liveText').textContent = 'Reconnecting…';
        }
    }

    if (canSeeAll) render(initial, false);
    document.getElementById('updated').textContent = 'Updated ' + new Date().toLocaleTimeString();
    setInterval(refresh, 5000);
    setInterval(tickTimes, 1000);
</script>
</body>
</html>
