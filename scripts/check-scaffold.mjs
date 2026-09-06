import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const readJson = (file) => JSON.parse(fs.readFileSync(path.join(root, file), 'utf8'));
const requiredFiles = [
  'pnpm-workspace.yaml',
  'compose.yml',
  'apps/wordpress/package.json',
  'apps/wordpress/server.php',
  'apps/wordpress/composer.json',
  'apps/wordpress/composer.lock',
  'apps/wordpress/web/wp-config.php',
  'apps/wordpress/config/application.php',
  'apps/wordpress/web/app/mu-plugins/esctt-content.php',
  'packages/theme/composer.json',
  'packages/theme/package.json',
  'packages/esctt-content/composer.json',
  'packages/esctt-content/esctt-content.php',
];

for (const file of requiredFiles) {
  assert.ok(fs.existsSync(path.join(root, file)), `Missing ${file}`);
}

const wordpress = readJson('apps/wordpress/composer.json');
assert.equal(wordpress.require['wpackagist-plugin/secure-custom-fields'], '^6.4');
assert.equal(wordpress.require['esctt/theme'], 'dev-main');
assert.equal(wordpress.require['esctt/esctt-content'], 'dev-main');
assert.deepEqual(wordpress.extra['installer-paths']['web/app/themes/esctt/'], ['esctt/theme']);
assert.deepEqual(wordpress.extra['installer-paths']['web/app/plugins/esctt-content/'], ['esctt/esctt-content']);
assert.equal(wordpress.require['wpackagist-plugin/advanced-custom-fields'], undefined);
assert.equal(wordpress.require['wpackagist-plugin/advanced-custom-fields-pro'], undefined);

const application = fs.readFileSync(path.join(root, 'apps/wordpress/config/application.php'), 'utf8');
assert.match(application, /WP_DEFAULT_THEME', 'esctt'/);

const server = fs.readFileSync(path.join(root, 'apps/wordpress/server.php'), 'utf8');
assert.match(server, /\$documentRoot = __DIR__ \. '\/web';/);
assert.match(server, /\$file = \$documentRoot \. \$path;/);

const loader = fs.readFileSync(path.join(root, 'apps/wordpress/web/app/mu-plugins/esctt-content.php'), 'utf8');
assert.ok(loader.indexOf('secure-custom-fields.php') < loader.indexOf('esctt-content.php'));

const lock = readJson('apps/wordpress/composer.lock');
assert.ok(lock.packages.some(({ name }) => name === 'wpackagist-plugin/secure-custom-fields'));

const compose = fs.readFileSync(path.join(root, 'compose.yml'), 'utf8');
assert.match(compose, /image: mariadb:11\.4/);
assert.match(compose, /3307:3306/);
assert.match(compose, /healthcheck:/);

const workspace = fs.readFileSync(path.join(root, 'pnpm-workspace.yaml'), 'utf8');
assert.match(workspace, /apps\/\*/);
assert.match(workspace, /packages\/\*/);

console.log('ESCTT WordPress scaffold: OK');
