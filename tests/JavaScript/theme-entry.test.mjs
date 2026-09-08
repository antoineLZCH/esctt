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
    const windowListeners = new Map();
    globalThis.window = {
        addEventListener: (type, handler) => windowListeners.set(type, handler),
        wp: {
            blocks: {
                registerBlockType: (name, settings) => registeredBlocks.set(name, settings),
            },
            blockEditor: {
                BlockControls: 'BlockControls',
                InspectorControls: 'InspectorControls',
                RichText: 'RichText',
                useBlockProps: (props) => props,
            },
            components: {
                PanelBody: 'PanelBody',
                TextControl: 'TextControl',
                ToolbarButton: 'ToolbarButton',
            },
            element: {
                Fragment: 'Fragment',
                createElement: (type, props, ...children) => ({ type, props, children }),
            },
            i18n: { __: (text) => text },
        },
    };
    const helloAssoIframe = {
        contentWindow: {},
        style: {},
    };
    globalThis.document = {
        readyState: 'complete',
        querySelectorAll: () => [helloAssoIframe],
    };

    for (const entry of entries.filter((file) => file.endsWith('.js'))) {
        await import(pathToFileURL(join(root, 'packages/theme/resources/js', entry)).href);
    }

    assert.equal(typeof windowListeners.get('message'), 'function');
    const handleHelloAssoMessage = windowListeners.get('message');
    handleHelloAssoMessage({ origin: 'https://evil.example', source: helloAssoIframe.contentWindow, data: { height: 1200 } });
    handleHelloAssoMessage({ origin: 'https://www.helloasso.com', source: null, data: { height: 1200 } });
    handleHelloAssoMessage({ origin: 'https://www.helloasso.com', source: {}, data: { height: 1200 } });
    handleHelloAssoMessage({ origin: 'https://www.helloasso.com', source: helloAssoIframe.contentWindow, data: null });
    handleHelloAssoMessage({ origin: 'https://www.helloasso.com', source: helloAssoIframe.contentWindow, data: { height: 0 } });
    handleHelloAssoMessage({ origin: 'https://www.helloasso.com', source: helloAssoIframe.contentWindow, data: { height: 3001 } });
    handleHelloAssoMessage({ origin: 'https://www.helloasso.com', source: helloAssoIframe.contentWindow, data: { height: 1200 } });
    assert.equal(helloAssoIframe.style.height, '1200px');

    const hero = registeredBlocks.get('esctt/hero');
    assert.ok(hero);
    assert.equal(hero.save(), null);

    const partners = registeredBlocks.get('esctt/partners');
    assert.ok(partners);
    assert.equal(partners.save(), null);
    assert.equal(partners.edit({}).props.className, 'esctt-partners');

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

    const sportLife = registeredBlocks.get('esctt/sport-life');
    assert.ok(sportLife);
    assert.equal(sportLife.save(), null);
    const sportChanges = [];
    const sportEditor = sportLife.edit({
        attributes: { helloAssoUrl: '' },
        setAttributes: (value) => sportChanges.push(value),
    });
    const sportUrlControl = sportEditor.children[0].children[0].children[0];
    sportUrlControl.props.onChange('https://www.helloasso.com/associations/example/adhesions/tournoi');

    assert.equal(sportEditor.children[1].props.className, 'esctt-sport-life esctt-sport-life--editor');
    assert.equal(sportUrlControl.props.type, 'url');
    assert.deepEqual(sportChanges, [{ helloAssoUrl: 'https://www.helloasso.com/associations/example/adhesions/tournoi' }]);

    const helloAsso = registeredBlocks.get('esctt/helloasso');
    assert.ok(helloAsso);
    assert.equal(helloAsso.save(), null);
    const helloAssoChanges = [];
    const helloAssoEditor = helloAsso.edit({
        attributes: { membershipUrl: '', widgetUrl: '' },
        setAttributes: (value) => helloAssoChanges.push(value),
    });
    const helloAssoMembershipControl = helloAssoEditor.children[0].children[0].children[0];
    const helloAssoWidgetControl = helloAssoEditor.children[0].children[0].children[1];
    helloAssoMembershipControl.props.onChange('https://www.helloasso.com/associations/example/adhesions/adhesion-2026');
    helloAssoWidgetControl.props.onChange('https://www.helloasso.com/associations/example/adhesions/adhesion-2026/widget');

    assert.equal(helloAssoEditor.children[1].props.className, 'esctt-helloasso esctt-helloasso--editor');
    assert.equal(helloAssoMembershipControl.props.type, 'url');
    assert.equal(helloAssoWidgetControl.props.type, 'url');
    assert.deepEqual(helloAssoChanges, [
        { membershipUrl: 'https://www.helloasso.com/associations/example/adhesions/adhesion-2026' },
        { widgetUrl: 'https://www.helloasso.com/associations/example/adhesions/adhesion-2026/widget' },
    ]);

    delete globalThis.document;
    delete globalThis.window;
});
