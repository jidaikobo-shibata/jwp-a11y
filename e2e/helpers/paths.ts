import path from 'node:path';

export const pluginDir = path.resolve(__dirname, '../..');
export const composeFile = path.resolve(pluginDir, '../../../docker-compose.yml');
export const stateDir = path.join(pluginDir, 'e2e/.state');
export const fixtureStateFile = path.join(stateDir, 'fixture.json');
export const authFile = path.join(pluginDir, 'playwright/.auth/admin.json');

export const containerPluginDir = '/var/www/html/wp-content/plugins/jwp-a11y';
