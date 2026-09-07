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

    const registeredBlocks = new Map();
    globalThis.window = {
        wp: {
            blocks: {
                registerBlockType: (name, settings) => registeredBlocks.set(name, settings),
            },
            blockEditor: {
                BlockControls: 'BlockControls',
                RichText: 'RichText',
                useBlockProps: (props) => props,
            },
            components: { ToolbarButton: 'ToolbarButton' },
            element: {
                Fragment: 'Fragment',
                createElement: (type, props, ...children) => ({ type, props, children }),
            },
            i18n: { __: (text) => text },
        },
    };
    globalThis.document = { readyState: 'complete' };

    for (const entry of entries.filter((file) => file.endsWith('.js'))) {
        await import(pathToFileURL(join(root, 'packages/theme/resources/js', entry)).href);
    }

    const hero = registeredBlocks.get('esctt/hero');
    assert.ok(hero);
    assert.equal(hero.save(), null);

    const schedules = registeredBlocks.get('esctt/practice-schedules');
    assert.ok(schedules);
    assert.equal(schedules.save(), null);
    assert.match(schedules.edit().children[0].children[0], /horaires/i);

    const changes = [];
    const regular = hero.edit({
        attributes: { compact: false, title: 'Club' },
        setAttributes: (value) => changes.push(value),
    });
    const compact = hero.edit({
        attributes: { compact: true, title: 'Club' },
        setAttributes: (value) => changes.push(value),
    });

    regular.children[0].children[0].props.onClick();
    regular.children[1].children[0].props.onChange('Nouveau titre');

    assert.equal(regular.children[1].props.className, 'esctt-hero');
    assert.equal(compact.children[1].props.className, 'esctt-hero esctt-hero--compact');
    assert.deepEqual(changes, [{ compact: true }, { title: 'Nouveau titre' }]);

    delete globalThis.document;
    delete globalThis.window;
});
