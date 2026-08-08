const { launch, login, shot, shotScrolled, goto } = require('./lib');
async function safe(name, fn) {
  try { await fn(); } catch (e) { console.log('  ! failed', name, ':', e.message); }
}
(async () => {
  const browser = await launch();
  const page = await browser.newPage();
  console.log('login...');
  await login(page, 'alice-test@mixpitch.test', 'password123');
  console.log('logged in, url:', page.url());

  await safe('dashboard', async () => {
    await shot(page, '05a-dashboard');
    await shotScrolled(page, '05b-dashboard-y700', 700);
  });

  await safe('create-step1', async () => {
    await goto(page, '/projects/upload');
    await shot(page, '06a-create-step1');
    await shotScrolled(page, '06b-create-step1-y600', 600);
  });

  await safe('create-step2', async () => {
    await page.evaluate(() => {
      const btns = Array.from(document.querySelectorAll('button'));
      const c = btns.find(b => /continue/i.test(b.textContent.trim()));
      if (c) c.click();
    });
    await new Promise(r => setTimeout(r, 1500));
    await shot(page, '07a-create-step2-details');
    await shotScrolled(page, '07b-create-step2-y500', 500);
  });

  await safe('profile-edit', async () => {
    await goto(page, '/profile/edit');
    await shot(page, '08a-profile-edit');
    await shotScrolled(page, '08b-profile-edit-y700', 700);
    await shotScrolled(page, '08c-profile-edit-y1400', 1400);
  });

  await safe('billing', async () => {
    await goto(page, '/billing');
    await shot(page, '09-billing');
  });

  await browser.close();
  console.log('done');
})().catch(e => { console.error('FAIL:', e.message); process.exit(1); });
