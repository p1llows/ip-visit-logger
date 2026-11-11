const $ = (id) => document.getElementById(id);

chrome.storage.sync.get(['appUrl', 'browseKey', 'paused']).then((v) => {
  $('appUrl').value = v.appUrl || '';
  $('browseKey').value = v.browseKey || '';
  $('paused').checked = !!v.paused;
});

$('save').addEventListener('click', async () => {
  await chrome.storage.sync.set({
    appUrl: $('appUrl').value.trim(),
    browseKey: $('browseKey').value.trim(),
    paused: $('paused').checked,
  });
  $('msg').textContent = 'Saved';
  setTimeout(() => ($('msg').textContent = ''), 1500);
});
