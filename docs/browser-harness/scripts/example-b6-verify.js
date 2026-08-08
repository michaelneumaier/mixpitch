const { launch, login, shot, goto } = require('./lib');
(async () => {
  const browser = await launch();
  const page = await browser.newPage();
  await login(page, 'alice-test@mixpitch.test', 'password123');

  // Verify Alice's ORIGINAL project (which has pitches) now shows Share Project in the dropdown
  await goto(page, '/manage-standard-project/please-help-mix-my-acoustic-demo');
  await page.evaluate(() => {
    const b = Array.from(document.querySelectorAll('button')).find(x => x.textContent.trim() === 'Manage' && x.offsetHeight > 10 && x.offsetHeight < 60);
    if (b) b.click();
  });
  await new Promise(r => setTimeout(r, 1200));
  await shot(page, '85-b6-dropdown-has-share-with-pitches');

  // Also verify the click on Share Project opens the modal
  await page.evaluate(() => {
    const items = Array.from(document.querySelectorAll('[role="menuitem"], button, a'));
    const s = items.find(x => x.textContent.trim() === 'Share Project' && x.offsetHeight > 0);
    if (s) s.click();
  });
  await new Promise(r => setTimeout(r, 2500));
  await shot(page, '86-b6-share-modal-opens-from-dropdown');

  await browser.close();
  console.log('done');
})().catch(e => { console.error('FAIL:', e.message); process.exit(1); });
