import { spawnSync } from 'node:child_process';
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readdir } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '../..');
const entries = await readdir(join(root, 'packages/theme/resources/js'));

test('theme JavaScript entries parse and the application entry loads', async () => {
    for (const entry of entries.filter((file) => file.endsWith('.js'))) {
        const result = spawnSync(process.execPath, ['--check', join(root, 'packages/theme/resources/js', entry)], {
            encoding: 'utf8',
        });

        assert.equal(result.status, 0, result.stderr);
    }

    globalThis.document = { readyState: 'complete' };

    for (const entry of entries.filter((file) => file.endsWith('.js'))) {
        await import(pathToFileURL(join(root, 'packages/theme/resources/js', entry)).href);
    }

    delete globalThis.document;
});
