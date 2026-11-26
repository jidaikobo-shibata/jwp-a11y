import { test, expect } from '@playwright/test';
import { readFixtureState } from '../helpers/fixture-state';

function withQuery(url: string, name: string, value: string): string {
  const target = new URL(url);
  target.searchParams.set(name, value);
  return target.toString();
}

test('legacy result shortcode renders policy and report navigation', async ({ page }) => {
  const fixture = readFixtureState();
  await page.goto(fixture.resultsPageUrl);

  await expect(page.locator('.jwp-a11y-results')).toContainText('E2E accessibility policy fixture.');
  await expect(page.locator('.jwp-a11y-results a[href*="a11yc_report=1"]')).toBeVisible();

  await page.goto(withQuery(fixture.resultsPageUrl, 'a11yc_report', '1'));
  await expect(page.locator('.jwp-a11y-results')).toContainText('E2E Accessibility Report');
  await expect(page.locator('.a11yc_table_report')).toBeVisible();
  await expect(page.locator('.jwp-a11y-results')).toContainText('E2E failure memo.');
});

test('legacy result shortcode renders URL list and individual result', async ({ page }) => {
  const fixture = readFixtureState();
  await page.goto(withQuery(fixture.resultsPageUrl, 'a11yc_page', '1'));

  await expect(page.locator('.jwp-a11y-results')).toContainText('E2E tested page');
  const resultLink = page.locator(`a[href*="a11yc_each=1"]`).first();
  await expect(resultLink).toBeVisible();
  await resultLink.click();

  await expect(page.locator('.jwp-a11y-results')).toContainText('E2E tested page');
  await expect(page.locator('.jwp-a11y-results')).toContainText('E2E failure memo.');
  await expect(page.locator('tr.a11yc_not_passed')).toBeVisible();
});


test('historical version persists through report, URL list, detail and back navigation', async ({ page }) => {
  const fixture = readFixtureState();
  await page.goto(fixture.resultsPageUrl);
  await page.locator('#a11yc_version').selectOption('990000');
  await page.locator('form').filter({ has: page.locator('#a11yc_version') }).locator('button[type="submit"]').click();
  await expect(page.locator('.jwp-a11y-results')).toContainText('Historical E2E policy fixture.');
  await page.locator('.jwp-a11y-results a[href*="a11yc_report=1"]').first().click();
  await expect(page.locator('.jwp-a11y-results')).toContainText('Historical E2E Report');
  await expect(page.locator('.jwp-a11y-results')).toContainText(/試験結果が不完全です|Test results incomplete/);
  await page.locator('.jwp-a11y-results a[href*="a11yc_page=1"]').first().click();
  await expect(page.locator('.jwp-a11y-results')).toContainText('Historical E2E tested page');
  await page.locator('.jwp-a11y-results a[href*="a11yc_each=1"]').first().click();
  await expect(page.locator('.jwp-a11y-results')).toContainText('Historical E2E pass memo.');
  await expect(page.locator('.jwp-a11y-results')).toContainText(/試験結果が不完全です|Test results incomplete/);
  await page.locator('.jwp-a11y-results a[href*="a11yc_page=1"]').first().click();
  await expect(page).toHaveURL(/a11yc_version=990000/);
  await expect(page.locator('.jwp-a11y-results')).toContainText('Historical E2E tested page');
});
