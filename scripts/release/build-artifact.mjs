#!/usr/bin/env node

import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { execFileSync } from 'node:child_process';
import { cpSync, existsSync, lstatSync, mkdirSync, mkdtempSync, readFileSync, readdirSync, rmSync, statSync, writeFileSync } from 'node:fs';
import { dirname, join, relative, resolve, sep } from 'node:path';
import { fileURLToPath } from 'node:url';

const repositoryRoot = resolve(dirname(fileURLToPath(import.meta.url)), '../..');
const semverPattern = /^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-[0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*)?(?:\+[0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*)?$/;
const forbiddenPathPattern = /(^|\/)(?:\.git|\.env(?:\.|$)|node_modules|uploads|media|backups|database|test-results|tests|prototype)(?:\/|$)/;

function option(args, name, fallback) {
  const prefix = `${name}=`;
  const inline = args.find((argument) => argument.startsWith(prefix));
  if (inline) {
    return inline.slice(prefix.length);
  }

  const index = args.indexOf(name);
  if (index !== -1 && args[index + 1]) {
    return args[index + 1];
  }

  return fallback;
}

function requiredDirectory(path, label) {
  if (!existsSync(path) || !statSync(path).isDirectory()) {
    throw new Error(`${label} directory is required: ${path}`);
  }
}

function copyTree(source, destination, filter = () => true) {
  requiredDirectory(source, 'Source');
  mkdirSync(dirname(destination), { recursive: true });
  cpSync(source, destination, {
    recursive: true,
    force: true,
    filter: (sourcePath, destinationPath) => {
      if (lstatSync(sourcePath).isSymbolicLink()) {
        throw new Error(`Symbolic links are not allowed in release sources: ${sourcePath}`);
      }
      return filter(sourcePath, destinationPath);
    },
  });
}

function relativePath(root, path) {
  return relative(root, path).split(sep).join('/');
}

function filesIn(root) {
  const files = [];

  function visit(directory) {
    for (const entry of readdirSync(directory, { withFileTypes: true })) {
      const path = join(directory, entry.name);
      if (entry.isDirectory()) {
        visit(path);
      } else if (entry.isFile()) {
        files.push(relativePath(root, path));
      }
    }
  }

  visit(root);
  return files.sort();
}

function sha256(path) {
  return createHash('sha256').update(readFileSync(path)).digest('hex');
}

function runtimeFilter(sourceRoot, sourcePath) {
  const path = relativePath(sourceRoot, sourcePath);
  if (!path) {
    return true;
  }
  const segments = path.split('/');
  const name = segments.at(-1);

  return !segments.some((segment) =>
    segment === '.git' ||
    segment === 'node_modules' ||
    segment === 'vendor' ||
    segment === 'tests' ||
    segment === 'test' ||
    segment === 'docs' ||
    segment === 'cache' ||
    segment === 'media' ||
    segment === 'backups' ||
    segment === 'database' ||
    segment.startsWith('.env') ||
    name === 'package.json' ||
    name === 'composer.json' ||
    name === 'composer.lock' ||
    name === '.gitignore'
  );
}

function themeFilter(sourceRoot, sourcePath) {
  const path = relativePath(sourceRoot, sourcePath);
  if (!path) {
    return true;
  }
  const topLevel = path.split('/')[0];
  const allowed = new Set(['app', 'public', 'resources', 'functions.php', 'index.php', 'style.css', 'theme.json']);

  if (!allowed.has(topLevel)) {
    return false;
  }
  if (topLevel === 'public' && path !== 'public' && path !== 'public/build' && !path.startsWith('public/build/')) {
    return false;
  }
  if (topLevel === 'resources' && path !== 'resources' && path !== 'resources/views' && path !== 'resources/lang' && !path.startsWith('resources/views/') && !path.startsWith('resources/lang/')) {
    return false;
  }

  return runtimeFilter(sourceRoot, sourcePath);
}

function pluginFilter(sourceRoot, sourcePath) {
  return runtimeFilter(sourceRoot, sourcePath);
}

function vendorFilter(sourceRoot, sourcePath) {
  const path = relativePath(sourceRoot, sourcePath);
  if (!path) {
    return true;
  }
  const segments = path.split('/');
  return !segments.some((segment) =>
    segment === '.git' ||
    segment === 'node_modules' ||
    segment === 'tests' ||
    segment === 'test' ||
    segment === 'docs' ||
    segment === 'bin' ||
    segment === 'media' ||
    segment === 'backups' ||
    segment === 'database' ||
    segment.startsWith('.env')
  ) && !['.gitignore', 'composer.json', 'composer.lock'].includes(path.split('/').at(-1));
}

function replaceVersionHeader(path, version) {
  const content = readFileSync(path, 'utf8');
  const pattern = /^(\s*\*?\s*Version:\s*)\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?/m;

  if (!pattern.test(content)) {
    throw new Error(`Version header not found in ${path}`);
  }
  writeFileSync(path, content.replace(pattern, `$1${version}`));
}

function verifyManifest(stage, manifest) {
  const payloadFiles = filesIn(stage).filter((path) => !['release-manifest.json', 'SHA256SUMS'].includes(path));
  const manifestFiles = manifest.files.map(({ path }) => path).sort();
  assert.deepEqual(manifestFiles, payloadFiles, 'The release manifest does not match the staged payload');

  for (const { path, sha256: expectedHash } of manifest.files) {
    assert.equal(sha256(join(stage, path)), expectedHash, `Manifest hash mismatch: ${path}`);
  }
}

function verifyChecksums(stage) {
  const expected = new Map(
    filesIn(stage)
      .filter((path) => path !== 'SHA256SUMS')
      .map((path) => [path, sha256(join(stage, path))])
  );
  const actual = new Map(
    readFileSync(join(stage, 'SHA256SUMS'), 'utf8')
      .trim()
      .split('\n')
      .filter(Boolean)
      .map((line) => {
        const match = line.match(/^([a-f0-9]{64})  (.+)$/);
        assert.ok(match, `Invalid SHA256SUMS entry: ${line}`);
        return [match[2], match[1]];
      })
  );

  assert.deepEqual(actual, expected, 'SHA256SUMS does not match the staged payload');
}

function verifyArchive(archive, stage, manifest) {
  const archiveEntries = execFileSync('tar', ['-tzf', archive], { encoding: 'utf8' })
    .split('\n')
    .map((entry) => entry.replace(/^\.\//, '').replace(/\/$/, ''))
    .filter(Boolean);

  for (const entry of archiveEntries) {
    if (entry.startsWith('/') || entry.split('/').includes('..') || forbiddenPathPattern.test(entry)) {
      throw new Error(`Forbidden path in archive: ${entry}`);
    }
  }

  verifyManifest(stage, manifest);
  verifyChecksums(stage);

  const archiveSet = new Set(archiveEntries);
  for (const file of filesIn(stage)) {
    if (!archiveSet.has(file)) {
      throw new Error(`File missing from archive: ${file}`);
    }
  }

  if (!manifest.files.some(({ path }) => path.startsWith('web/app/themes/esctt/public/build/'))) {
    throw new Error('The manifest must contain built theme assets');
  }
  if (!archiveSet.has('vendor/autoload.php')) {
    throw new Error('The archive must contain the runtime Composer autoloader');
  }
}

function sourceCommitFromGit() {
  try {
    return execFileSync('git', ['rev-parse', 'HEAD'], { cwd: repositoryRoot, encoding: 'utf8' }).trim();
  } catch {
    throw new Error('Pass --source-commit when the source is not a Git checkout');
  }
}

function main() {
  const args = process.argv.slice(2);
  const version = option(args, '--version', null);
  const sourceCommit = option(args, '--source-commit', process.env.GITHUB_SHA || sourceCommitFromGit());
  const outputDirectory = resolve(repositoryRoot, option(args, '--output-dir', 'artifacts'));
  const themeDirectory = resolve(repositoryRoot, option(args, '--theme-dir', 'packages/theme'));
  const pluginDirectory = resolve(repositoryRoot, option(args, '--plugin-dir', 'packages/esctt-content'));
  const vendorDirectory = resolve(repositoryRoot, option(args, '--vendor-dir', 'apps/wordpress/vendor'));
  const dependencyPluginDirectory = resolve(
    repositoryRoot,
    option(args, '--dependency-plugin-dir', 'apps/wordpress/web/app/plugins/secure-custom-fields')
  );

  if (!version || !semverPattern.test(version)) {
    throw new Error(`Release version must be SemVer without a leading v: ${version || '(missing)'}`);
  }
  if (!sourceCommit) {
    throw new Error('A source commit is required');
  }

  requiredDirectory(themeDirectory, 'Theme');
  requiredDirectory(pluginDirectory, 'Plugin');
  requiredDirectory(vendorDirectory, 'Runtime dependency');
  requiredDirectory(dependencyPluginDirectory, 'Secure Custom Fields dependency');
  requiredDirectory(join(themeDirectory, 'public/build'), 'Built theme assets');

  mkdirSync(outputDirectory, { recursive: true });
  const archive = join(outputDirectory, `esctt-v${version}.tar.gz`);
  const stage = mkdtempSync(join(outputDirectory, `.esctt-${version}-`));

  try {
    copyTree(themeDirectory, join(stage, 'web/app/themes/esctt'), (sourcePath) => themeFilter(themeDirectory, sourcePath));
    copyTree(pluginDirectory, join(stage, 'web/app/plugins/esctt-content'), (sourcePath) => pluginFilter(pluginDirectory, sourcePath));
    copyTree(vendorDirectory, join(stage, 'vendor'), (sourcePath) => vendorFilter(vendorDirectory, sourcePath));
    copyTree(
      dependencyPluginDirectory,
      join(stage, 'web/app/plugins/secure-custom-fields'),
      (sourcePath) => runtimeFilter(dependencyPluginDirectory, sourcePath)
    );

    replaceVersionHeader(join(stage, 'web/app/themes/esctt/style.css'), version);
    replaceVersionHeader(join(stage, 'web/app/plugins/esctt-content/esctt-content.php'), version);

    const sourceFiles = filesIn(stage);
    const forbiddenFiles = sourceFiles.filter((path) => forbiddenPathPattern.test(path));
    if (forbiddenFiles.length > 0) {
      throw new Error(`The staged artifact contains forbidden paths: ${forbiddenFiles.join(', ')}`);
    }

    const manifest = {
      name: 'esctt',
      version,
      sourceCommit,
      files: sourceFiles.map((path) => ({
        path,
        sha256: sha256(join(stage, path)),
      })),
    };
    writeFileSync(join(stage, 'release-manifest.json'), `${JSON.stringify(manifest, null, 2)}\n`);

    const checksummedFiles = filesIn(stage).filter((path) => path !== 'SHA256SUMS');
    writeFileSync(
      join(stage, 'SHA256SUMS'),
      `${checksummedFiles.map((path) => `${sha256(join(stage, path))}  ${path}`).join('\n')}\n`
    );

    execFileSync('tar', ['-czf', archive, '-C', stage, '.'], { stdio: 'inherit' });
    verifyArchive(archive, stage, manifest);

    console.log(JSON.stringify({ archive, version, sourceCommit, fileCount: filesIn(stage).length }));
  } finally {
    rmSync(stage, { recursive: true, force: true });
  }
}

try {
  main();
} catch (error) {
  console.error(error instanceof Error ? error.message : error);
  process.exitCode = 1;
}
