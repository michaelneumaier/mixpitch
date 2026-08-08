const { launch, login, shot, shotScrolled, goto } = require('./lib');
async function safe(name, fn) { try { await fn(); } catch (e) { console.log('  ! failed', name, ':', e.message); } }
(async () => {
  const browser = await launch();
  const page = await browser.newPage();
  await login(page, 'alice-test@mixpitch.test', 'password123');

  await safe('open-share-from-empty-state', async () => {
    await goto(page, '/manage-standard-project/please-help-mix-my-acoustic-demo');
    await shot(page, '80a-manage-before-share-click');
    // Click Share Project button
    const clicked = await page.evaluate(() => {
      const btns = Array.from(document.querySelectorAll('button, a'));
      const s = btns.find(x => x.textContent.trim() === 'Share Project' && x.offsetHeight > 0);
      if (s) { s.click(); return true; }
      return false;
    });
    console.log('  share clicked:', clicked);
    await new Promise(r => setTimeout(r, 2500));
    await shot(page, '80b-share-modal-opened');
    await shotScrolled(page, '80c-share-modal-y400', 400);
  });

  await safe('expand-preview', async () => {
    // Click the "Preview post" accordion
    const clicked = await page.evaluate(() => {
      const items = Array.from(document.querySelectorAll('button, summary, [data-flux-accordion-trigger]'));
      const pv = items.find(x => /Preview post/i.test(x.textContent.trim()) && x.offsetHeight > 0);
      if (pv) { pv.click(); return true; }
      return false;
    });
    console.log('  preview expanded:', clicked);
    await new Promise(r => setTimeout(r, 1200));
    await shot(page, '80d-share-modal-preview-expanded');
    await shotScrolled(page, '80e-share-modal-preview-y500', 500);
  });

  await safe('close-and-verify-manage-dropdown', async () => {
    // Close modal then open Manage dropdown to verify Reddit items are gone
    await page.evaluate(() => {
      const c = Array.from(document.querySelectorAll('button')).find(b => b.textContent.trim() === 'Cancel' || b.getAttribute('aria-label') === 'Close');
      if (c) c.click();
    });
    await new Promise(r => setTimeout(r, 800));
    await page.evaluate(() => {
      const b = Array.from(document.querySelectorAll('button')).find(x => x.textContent.trim() === 'Manage' && x.offsetHeight > 10 && x.offsetHeight < 60);
      if (b) b.click();
    });
    await new Promise(r => setTimeout(r, 1200));
    await shot(page, '81-manage-dropdown-no-reddit');
  });

  await browser.close();
  console.log('done');
})().catch(e => { console.error('FAIL:', e.message); process.exit(1); });
