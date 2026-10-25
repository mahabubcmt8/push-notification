const btn = document.getElementById('toggle');
const statusEl = document.getElementById('status');

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const raw = atob(base64);
  return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
}

async function readJson(res, url) {
  const text = await res.text();
  let data;
  try {
    data = JSON.parse(text);
  } catch {
    throw new Error(`${url} did not return JSON (HTTP ${res.status}): ${text.slice(0, 300)}`);
  }
  if (!res.ok) throw new Error(data.error || `${url} returned ${res.status}`);
  return data;
}

async function post(url, body) {
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  return readJson(res, url);
}

async function currentSubscription() {
  const reg = await navigator.serviceWorker.ready;
  return reg.pushManager.getSubscription();
}

async function refreshButton() {
  const sub = await currentSubscription();
  btn.textContent = sub ? 'Disable notifications' : 'Enable notifications';
  btn.disabled = false;
}

async function subscribe() {
  const permission = await Notification.requestPermission();
  if (permission !== 'granted') {
    statusEl.textContent = 'Permission was not granted.';
    return;
  }

  const { publicKey } = await readJson(await fetch('vapid-public-key.php'), 'vapid-public-key.php');
  const reg = await navigator.serviceWorker.ready;

  const sub = await reg.pushManager.subscribe({
    userVisibleOnly: true,
    applicationServerKey: urlBase64ToUint8Array(publicKey),
  });

  const encoding = (PushManager.supportedContentEncodings || ['aesgcm'])[0];
  await post('subscribe.php', { ...sub.toJSON(), contentEncoding: encoding });
  statusEl.textContent = 'Subscribed. Now try the Send Notification button below.';
}

async function unsubscribe() {
  const sub = await currentSubscription();
  if (!sub) return;
  await post('unsubscribe.php', { endpoint: sub.endpoint });
  await sub.unsubscribe();
  statusEl.textContent = 'Unsubscribed.';
}

const sendBtn = document.getElementById('sendBtn');
const sendStatusEl = document.getElementById('sendStatus');

async function sendNotification() {
  sendBtn.disabled = true;
  sendStatusEl.textContent = 'Sending…';

  const title = document.getElementById('sendTitle').value;
  const body = document.getElementById('sendBody').value;

  try {
    const result = await post('send-notification.php', { title, body, url: '/' });
    sendStatusEl.textContent =
      `Sent: ${result.sent}, failed: ${result.failed}, removed expired: ${result.removed}` +
      (result.message ? ` (${result.message})` : '');
  } catch (err) {
    sendStatusEl.textContent = 'Error: ' + err.message;
  } finally {
    sendBtn.disabled = false;
  }
}

if (sendBtn) {
  sendBtn.addEventListener('click', sendNotification);
}

async function init() {
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
    btn.textContent = 'Push not supported in this browser';
    return;
  }

  await navigator.serviceWorker.register('sw.js');

  btn.addEventListener('click', async () => {
    btn.disabled = true;
    try {
      (await currentSubscription()) ? await unsubscribe() : await subscribe();
    } catch (err) {
      statusEl.textContent = 'Error: ' + err.message;
    }
    await refreshButton();
  });

  await refreshButton();
}

init();