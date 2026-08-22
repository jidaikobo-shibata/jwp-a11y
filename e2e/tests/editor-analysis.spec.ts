import { test, expect } from '@playwright/test';
import { readFixtureState } from '../helpers/fixture-state';

test('saving a post runs analysis and publishes an editor notice', async ({ page }) => {
  const fixture = readFixtureState();
  await page.goto(fixture.editorEditUrl);
  await page.waitForFunction(() => Boolean((window as any).wp?.data?.dispatch('core/editor')));

  const noticeResponse = page.waitForResponse((response) => {
    const url = new URL(response.url());
    return url.pathname.endsWith('/wp-admin/admin-ajax.php') &&
      url.searchParams.get('action') === 'jwp_a11y_notice';
  });

  await page.evaluate(async () => {
    const wp = (window as any).wp;
    wp.data.dispatch('core/editor').editPost({
      content: '<!-- wp:html --><img src="/e2e-missing-image.png"><!-- /wp:html -->',
    });
    await wp.data.dispatch('core/editor').savePost();
  });

  const response = await noticeResponse;
  expect(response.ok()).toBeTruthy();
  const payload = await response.json();
  expect(payload.success).toBeTruthy();
  expect(payload.data.errorCount + payload.data.noticeCount).toBeGreaterThan(0);

  await expect.poll(async () => page.evaluate(() => {
    const notices = (window as any).wp.data.select('core/notices').getNotices();
    return notices.some((notice: { id?: string }) => notice.id?.startsWith('jwp-a11y-result-'));
  })).toBeTruthy();
});
