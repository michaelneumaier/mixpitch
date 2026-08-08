const puppeteer = require('puppeteer');
const BASE = 'https://mixpitch.test';
const SHOTS = '/tmp/mixpitch-browser/shots';
const CHROME_PATH = '/tmp/mixpitch-browser/pptr-arm64/chrome/mac_arm-151.0.7922.71/chrome-mac-arm64/Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing';

async function launch({ mobile = false } = {}) {
  return puppeteer.launch({
    headless: true,
    executablePath: CHROME_PATH,
    acceptInsecureCerts: true,
    waitForInitialPage: false,
    protocolTimeout: 60000,
    timeout: 60000,
    defaultViewport: mobile ? { width: 390, height: 844, isMobile: true, hasTouch: true } : { width: 1280, height: 900 },
    args: ['--no-sandbox', '--disable-dev-shm-usage', '--ignore-certificate-errors', '--disable-gpu']
  });
}

async function newPage(browser) {
  // IMPORTANT: use an isolated incognito-style BrowserContext per login session.
  // browser.newPage() alone shares cookies across ALL pages in the default
  // context -- logging in as a second user does NOT overwrite the first
  // user's session if Laravel's `guest` middleware intercepts the /login
  // POST while already authenticated, silently leaving you on the PREVIOUS
  // user's session. Always pair newPage() with a fresh context per user.
  const ctx = await browser.createBrowserContext();
  const page = await ctx.newPage();
  // The app's PWA service worker calls location.reload() on activation of a
  // new SW version ("Controller changed, reloading page for fresh content").
  // That races with our fetch()-based login/navigation flow and destroys the
  // execution context mid-script. Disable SW registration for test sessions only.
  await page.evaluateOnNewDocument(() => {
    if (navigator.serviceWorker) {
      navigator.serviceWorker.register = () => Promise.reject(new Error('disabled for test harness'));
    }
  });
  return page;
}

async function login(page, email, password) {
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 30000 });
  const csrf = await page.$eval('meta[name="csrf-token"]', el => el.content);
  const result = await page.evaluate(async ({ email, password, csrf }) => {
    const body = new URLSearchParams({ _token: csrf, email, password }).toString();
    const r = await fetch('/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'text/html' },
      body, redirect: 'follow', credentials: 'same-origin'
    });
    return { status: r.status, url: r.url };
  }, { email, password, csrf });
  console.log('  login post:', result);
  await new Promise(r => setTimeout(r, 400));
  await page.goto(`${BASE}/dashboard`, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await new Promise(r => setTimeout(r, 1000));
}

async function shot(page, name) {
  const p = `${SHOTS}/${name}.png`;
  await page.screenshot({ path: p });
  console.log('shot:', name, 'url:', page.url());
  return p;
}

async function shotScrolled(page, name, y) {
  try {
    await page.evaluate(y => window.scrollTo(0, y), y);
  } catch (e) { /* ignore mid-navigation errors */ }
  await new Promise(r => setTimeout(r, 350));
  return shot(page, name);
}

async function goto(page, path) {
  await page.goto(`${BASE}${path}`, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await new Promise(r => setTimeout(r, 1200));
}

module.exports = { launch, newPage, login, shot, shotScrolled, goto, BASE, SHOTS };
