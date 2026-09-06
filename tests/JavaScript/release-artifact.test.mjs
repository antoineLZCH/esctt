import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { execFileSync, spawnSync } from 'node:child_process';
import { mkdtempSync, mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { test } from 'node:test';

const root = new URL('../..', import.meta.url).pathname;
const script = join(root, 'scripts/release/build-artifact.mjs');

function writeFixture(root, path, content = '') {
  const file = join(root, path);
  mkdirSync(dirname(file), { recursive: true });
  writeFileSync(file, content);
}

function hash(path) {
  return createHash('sha256').update(readFileSync(path)).digest('hex');
}

function createReleaseFixture() {
  const fixture = mkdtempSync(join(tmpdir(), 'esctt-release-test-'));
  const theme = join(fixture, 'theme');
  const plugin = join(fixture, 'plugin');
  const vendor = join(fixture, 'vendor');
  const secureCustomFields = join(fixture, 'secure-custom-fields');
  const output = join(fixture, 'artifacts');

  writeFixture(theme, 'style.css', '/*\nVersion: 0.1.0\n*/\n');
  writeFixture(theme, 'index.php', '<?php\n');
  writeFixture(theme, 'functions.php', '<?php\n');
  writeFixture(theme, 'theme.json', '{}\n');
  writeFixture(theme, 'app/runtime.php', '<?php\n');
  writeFixture(theme, 'resources/views/page.blade.php', '<main>Page</main>\n');
  writeFixture(theme, 'resources/css/app.css', 'development source\n');
  writeFixture(theme, 'resources/images/logo.jpg', 'development media\n');
  writeFixture(theme, 'public/build/app.js', 'compiled asset\n');
  writeFixture(theme, '.env', 'secret\n');
  writeFixture(theme, 'node_modules/package/index.js', 'not runtime\n');
  writeFixture(theme, 'composer.json', '{}\n');

  writeFixture(plugin, 'esctt-content.php', '<?php\n/**\n * Version: 0.1.0\n */\n');
  writeFixture(plugin, 'src/runtime.php', '<?php\n');
  writeFixture(plugin, 'tests/plugin.php', '<?php\n');
  writeFixture(plugin, 'media/logo.jpg', 'plugin media\n');
  writeFixture(plugin, 'composer.json', '{}\n');

  writeFixture(vendor, 'autoload.php', '<?php\n');
  writeFixture(vendor, 'composer/installed.php', '<?php\n');
  writeFixture(vendor, 'package/src/runtime.php', '<?php\n');
  writeFixture(vendor, 'illuminate/cache/ArrayStore.php', '<?php\n');
  writeFixture(vendor, 'package/tests/test.php', '<?php\n');
  writeFixture(vendor, 'package/.gitignore', 'cache\n');
  writeFixture(vendor, 'bin/dev-tool', '#!/bin/sh\n');

  writeFixture(secureCustomFields, 'secure-custom-fields.php', '<?php\n');
  writeFixture(secureCustomFields, 'tests/test.php', '<?php\n');

  return { fixture, theme, plugin, vendor, secureCustomFields, output };
}

test('builds a traceable minimal release archive', () => {
  const { fixture, theme, plugin, vendor, secureCustomFields, output } = createReleaseFixture();

  try {
    const result = spawnSync(process.execPath, [
      script,
      '--version', '0.2.0',
      '--source-commit', 'abc123',
      '--output-dir', output,
      '--theme-dir', theme,
      '--plugin-dir', plugin,
      '--vendor-dir', vendor,
      '--dependency-plugin-dir', secureCustomFields,
    ], { encoding: 'utf8' });

    assert.equal(result.status, 0, result.stderr);
    const archive = join(output, 'esctt-v0.2.0.tar.gz');
    const entries = execFileSync('tar', ['-tzf', archive], { encoding: 'utf8' });
    const manifest = JSON.parse(execFileSync('tar', ['-xOzf', archive, './release-manifest.json'], { encoding: 'utf8' }));

    assert.equal(manifest.version, '0.2.0');
    assert.equal(manifest.sourceCommit, 'abc123');
    assert.ok(manifest.files.some(({ path }) => path === 'web/app/themes/esctt/public/build/app.js'));
    assert.match(entries, /web\/app\/plugins\/esctt-content\/esctt-content\.php/);
    assert.match(entries, /web\/app\/plugins\/secure-custom-fields\/secure-custom-fields\.php/);
    assert.match(entries, /vendor\/autoload\.php/);
    assert.match(entries, /vendor\/illuminate\/cache\/ArrayStore\.php/);
    assert.doesNotMatch(entries, /resources\/css|resources\/images|node_modules|\.env|(?:^|\/)tests(?:\/|$)|(?:^|\/)media(?:\/|$)|vendor\/bin|vendor\/.*\.gitignore|composer\.json/);

    const themeHeader = execFileSync('tar', ['-xOzf', archive, './web/app/themes/esctt/style.css'], { encoding: 'utf8' });
    assert.match(themeHeader, /Version:\s+0\.2\.0/);
    const pluginHeader = execFileSync('tar', ['-xOzf', archive, './web/app/plugins/esctt-content/esctt-content.php'], { encoding: 'utf8' });
    assert.match(pluginHeader, /Version: 0\.2\.0/);
    const builtAsset = execFileSync('tar', ['-xOzf', archive, './web/app/themes/esctt/public/build/app.js'], { encoding: 'utf8' });
    const asset = manifest.files.find(({ path }) => path.endsWith('/public/build/app.js'));
    assert.equal(hash(join(theme, 'public/build/app.js')), asset.sha256);
    assert.equal(builtAsset, 'compiled asset\n');

    const checksums = execFileSync('tar', ['-xOzf', archive, './SHA256SUMS'], { encoding: 'utf8' });
    assert.match(checksums, new RegExp(`${asset.sha256}  web/app/themes/esctt/public/build/app\\.js`));
  } finally {
    rmSync(fixture, { recursive: true, force: true });
  }
});

test('rejects a release version that is not SemVer', () => {
  const { fixture, theme, plugin, vendor, secureCustomFields, output } = createReleaseFixture();

  try {
    const result = spawnSync(process.execPath, [
      script,
      '--version', 'v0.2.0',
      '--source-commit', 'abc123',
      '--output-dir', output,
      '--theme-dir', theme,
      '--plugin-dir', plugin,
      '--vendor-dir', vendor,
      '--dependency-plugin-dir', secureCustomFields,
    ], { encoding: 'utf8' });

    assert.notEqual(result.status, 0);
    assert.match(result.stderr, /SemVer/);
  } finally {
    rmSync(fixture, { recursive: true, force: true });
  }
});
