const { launch, login, shot, shotScrolled, goto } = require('./lib');
async function safe(name, fn) {
  try { await fn(); } catch (e) { console.log('  ! failed', name, ':', e.message); }
}
(async () => {
  const browser = await launch();
  const page = await browser.newPage();
  await login(page, 'bob-test@mixpitch.test', 'password123');
  console.log('logged in as bob');

  await safe('bob-dashboard', async () => {
    await shot(page, '20a-bob-dashboard');
    await shotScrolled(page, '20b-bob-dashboard-y700', 700);
  });

  await safe('bob-projects-browse', async () => {
    await goto(page, '/projects');
    await shot(page, '21a-bob-projects-browse');
    await shotScrolled(page, '21b-bob-projects-y700', 700);
  });

  await safe('bob-project-detail', async () => {
    await goto(page, '/projects/please-help-mix-my-acoustic-demo');
    await shot(page, '22a-bob-project-detail');
    await shotScrolled(page, '22b-bob-project-detail-y700', 700);
    await shotScrolled(page, '22c-bob-project-detail-y1400', 1400);
  });

  await safe('bob-pitch-create', async () => {
    await goto(page, '/projects/please-help-mix-my-acoustic-demo/pitches/create');
    await shot(page, '23-bob-pitch-create');
  });

  await browser.close();
  console.log('done');
})().catch(e => { console.error('FAIL:', e.message); process.exit(1); });
