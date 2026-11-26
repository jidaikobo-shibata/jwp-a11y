import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { composeFile, containerPluginDir, pluginDir } from './paths';

const defaultPassword = 'JwpA11y-E2E-Local-Only-2026!';

export const e2eUser = 'jwp_a11y_e2e';
export const e2ePassword = process.env.JWP_A11Y_E2E_PASSWORD || defaultPassword;

export function runFixtureScript(scriptName: 'prepare.php' | 'cleanup.php' | 'audit-check.php', ...args: string[]): string {
  const script = `${containerPluginDir}/e2e/fixtures/${scriptName}`;
  return execFileSync(
    'docker',
    [
      'compose',
      '-f',
      composeFile,
      'exec',
      '-T',
      '--user',
      'www-data',
      '-e',
      `JWP_A11Y_E2E_PASSWORD=${e2ePassword}`,
      'wordpress',
      'wp',
      '--exec=define("DISABLE_WP_CRON", true);',
      'eval-file',
      script,
      ...args,
    ],
    {
      cwd: path.resolve(pluginDir, '../../..'),
      encoding: 'utf8',
      stdio: ['ignore', 'pipe', 'pipe'],
    },
  ).trim();
}
