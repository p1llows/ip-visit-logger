// Reports every top-level page you open to your IP Visit Logger app.
// Only the address without ?query or #fragment is sent (those often hold tokens).
// Private/incognito windows are not covered unless you enable that in the browser.

chrome.webNavigation.onCompleted.addListener(async (details) => {
  if (details.frameId !== 0) return; // main page only, not iframes
  if (!/^https?:\/\//i.test(details.url)) return;

  const { appUrl, browseKey, paused } = await chrome.storage.sync.get(['appUrl', 'browseKey', 'paused']);
  if (!appUrl || !browseKey || paused) return;

  let target;
  try {
    target = new URL(details.url);
  } catch {
    return;
  }

  // The app already logs its own visits, and local addresses aren't worth reporting.
  if (target.origin === new URL(appUrl).origin) return;
  if (['localhost', '127.0.0.1', '::1'].includes(target.hostname)) return;

  target.search = '';
  target.hash = '';

  try {
    await fetch(appUrl.replace(/\/$/, '') + '/api/browse', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Browse-Key': browseKey },
      body: JSON.stringify({ url: target.toString() }),
    });
  } catch (e) {
    // App asleep or offline: skip this one.
  }
});
