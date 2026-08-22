import { test as teardown } from '@playwright/test';
import fs from 'node:fs';
import { authFile, fixtureStateFile } from '../helpers/paths';
import { runFixtureScript } from '../helpers/wp-cli';

teardown('remove transient fixtures', async () => {
  runFixtureScript('cleanup.php');
  fs.rmSync(authFile, { force: true });
  fs.rmSync(fixtureStateFile, { force: true });
});
