import { test, expect } from '@playwright/test';

test('documentation index and criterion detail render', async ({ page }) => {
  await page.goto('/wp-admin/tools.php?page=jwp-a11y-docs');

  await expect(page.locator('.jwp-a11y-doc-index')).toBeVisible();
  const criterionLink = page.locator('.jwp-a11y-doc-index a').first();
  await expect(criterionLink).toBeVisible();
  await criterionLink.click();

  await expect(page.locator('.jwp-a11y-doc')).toBeVisible();
  await expect(page.locator('.a11yc_table_info')).toBeVisible();
});
