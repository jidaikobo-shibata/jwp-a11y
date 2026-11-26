import { test as setup, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import { authFile, fixtureStateFile, stateDir } from '../helpers/paths';
import { e2ePassword, e2eUser, runFixtureScript } from '../helpers/wp-cli';

setup('prepare fixtures and authenticate', async ({ page }) => {
  fs.mkdirSync(stateDir, { recursive: true });
  fs.mkdirSync(path.dirname(authFile), { recursive: true });

  const fixtureJson = runFixtureScript('prepare.php');
  const fixture = JSON.parse(fixtureJson.split('\n').at(-1) || '{}');
  expect(fixture.editorPostId).toBeGreaterThan(0);
  fs.writeFileSync(fixtureStateFile, JSON.stringify(fixture, null, 2));
  expect(runFixtureScript('audit-check.php', String(fixture.editorPostId))).toContain('Audit regression checks passed.');

  await page.goto('/wp-login.php');
  await page.locator('#user_login').fill(e2eUser);
  await page.locator('#user_pass').fill(e2ePassword);
  await page.locator('#wp-submit').click();
  await page.waitForURL((url) =>
    url.pathname.startsWith('/wp-admin/') || url.searchParams.get('action') === 'confirm_admin_email'
  );
  if (new URL(page.url()).searchParams.get('action') === 'confirm_admin_email') {
    // The local development site can periodically request an admin email review.
    await page.locator('a[href*="remind_me_later="]').click();
  }
  await expect(page).toHaveURL(/\/wp-admin\//);
  await page.context().storageState({ path: authFile });
});
