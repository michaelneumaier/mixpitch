const { launch, login, shot, shotScrolled, goto } = require('./lib');
async function safe(name, fn) { try { await fn(); } catch (e) { console.log('  ! failed', name, ':', e.message); } }

async function setTab(page, name) {
  // Flux tab.group has role="tab" elements with the name as internal attribute; safer to click the flux:tab button element
  const clicked = await page.evaluate(t => {
    // Try clicking on the flux tab button (has aria-controls or data-flux-tab)
    let el = document.querySelector(`[data-flux-tab="${t}"]`);
    if (!el) el = document.querySelector(`button[name="${t}"]`);
    if (!el) {
      // Flux may render tabs as <button> inside the flux:tabs container
      const container = document.querySelector('[role="tablist"], .flux-tabs');
      if (container) {
        const btns = Array.from(container.querySelectorAll('button, [role="tab"]'));
        el = btns.find(b => b.textContent.trim().toLowerCase().includes(t.toLowerCase()));
      }
    }
    if (el) { el.click(); return true; }
    return false;
  }, name);
  console.log('  setTab', name, clicked ? 'ok' : 'MISS');
  await new Promise(r => setTimeout(r, 2500));
}

(async () => {
  const browser = await launch();
  const page = await browser.newPage();
  await login(page, 'alice-test@mixpitch.test', 'password123');

  const url = '/manage-standard-project/please-help-mix-my-acoustic-demo';

  await safe('inspect-tabs', async () => {
    await goto(page, url);
    // Print out the flux tab structure for debug
    const dump = await page.evaluate(() => {
      const tabs = Array.from(document.querySelectorAll('[role="tab"], [role="tablist"] > *'));
      return tabs.map(t => ({ tag: t.tagName, role: t.getAttribute('role'), aria: t.getAttribute('aria-controls'), text: t.textContent.trim().slice(0,30), dataFlux: t.getAttribute('data-flux-tab'), name: t.getAttribute('name') }));
    });
    console.log('  tab elements:', JSON.stringify(dump, null, 2));
  });

  await safe('pitches', async () => {
    await setTab(page, 'pitches');
    await shot(page, '30a2-alice-pitches');
    await shotScrolled(page, '30b2-alice-pitches-y700', 700);
    await shotScrolled(page, '30c2-alice-pitches-y1400', 1400);
  });

  await safe('files', async () => {
    await goto(page, url);
    await setTab(page, 'files');
    await shot(page, '31a2-alice-files');
    await shotScrolled(page, '31b2-alice-files-y500', 500);
  });

  await safe('project', async () => {
    await goto(page, url);
    await setTab(page, 'project');
    await shot(page, '32a2-alice-project');
    await shotScrolled(page, '32b2-alice-project-y700', 700);
  });

  await browser.close();
  console.log('done');
})().catch(e => { console.error('FAIL:', e.message); process.exit(1); });
