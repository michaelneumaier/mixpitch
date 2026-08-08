const puppeteer = require('puppeteer');
const BASE = 'https://mixpitch.test';
const SHOTS = '/tmp/mixpitch-browser/shots';

async function launch({ mobile = false } = {}) {
  return puppeteer.launch({
    headless: true,
    acceptInsecureCerts: true,
    waitForInitialPage: false,
    protocolTimeout: 300000,
    timeout: 300000,
    defaultViewport: mobile ? { width: 390, height: 844, isMobile: true, hasTouch: true } : { width: 1280, height: 900 },
    args: ['--no-sandbox', '--disable-dev-shm-usage', '--ignore-certificate-errors', '--disable-gpu']
  });
}

async function login(page, email, password) {
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 60000 });
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
  // Navigate away from the login page to a stable destination first
  await new Promise(r => setTimeout(r, 800));
  await page.goto(`${BASE}/dashboard`, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await new Promise(r => setTimeout(r, 2000));
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
  await new Promise(r => setTimeout(r, 700));
  return shot(page, name);
}

async function goto(page, path) {
  await page.goto(`${BASE}${path}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await new Promise(r => setTimeout(r, 2500));
}

module.exports = { launch, login, shot, shotScrolled, goto, BASE, SHOTS };
