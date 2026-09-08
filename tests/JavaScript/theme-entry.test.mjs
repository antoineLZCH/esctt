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
    let domReadyCallback;
    globalThis.document = {
        readyState: 'complete',
        addEventListener: (event, callback) => {
            if (event === 'DOMContentLoaded') {
                domReadyCallback = callback;
            }
        },
        querySelectorAll: () => [],
    };

    let appModule;
    for (const entry of entries.filter((file) => file.endsWith('.js'))) {
        const module = await import(pathToFileURL(join(root, 'packages/theme/resources/js', entry)).href);
        if (entry === 'app.js') {
            appModule = module;
        }
    }

    assert.equal(typeof appModule.initializePracticeScheduleComparison, 'function');
    domReadyCallback();

    const listeners = {};
    const profileSlugs = ['jeune', 'adulte-loisir', 'adulte-competition'];
    const makeSlot = (profiles) => {
        const statuses = profileSlugs.map((slug) => ({
            dataset: { profileStatus: slug, profileMatch: String(profiles.includes(slug)) },
            hidden: slug !== 'jeune',
        }));

        return {
            dataset: { profileSlugs: profiles.join(' ') },
            classList: {
                toggled: {},
                toggle(className, enabled) { this.toggled[className] = enabled; },
            },
            querySelectorAll: () => statuses,
            statuses,
        };
    };
    const slots = [makeSlot(['jeune']), makeSlot(['adulte-loisir'])];
    const profileLabels = {
        jeune: 'Jeune',
        'adulte-loisir': 'Adulte loisir',
        'adulte-competition': 'Adulte compétition',
    };
    const inputs = profileSlugs.map((slug) => ({
        value: slug,
        dataset: { profileLabel: profileLabels[slug] },
        checked: slug === 'jeune',
        addEventListener: (event, callback) => { listeners[slug] = callback; },
    }));
    const selectedMessage = { textContent: '' };
    const schedule = {
        querySelectorAll: (selector) => selector.includes('input') ? inputs : slots,
        querySelector: () => inputs.find((input) => input.checked),
        selectedMessage,
    };
    schedule.querySelector = (selector) => selector.includes(':checked')
        ? inputs.find((input) => input.checked)
        : selectedMessage;

    appModule.initializePracticeScheduleComparison({ querySelectorAll: () => [schedule] });
    assert.equal(slots[0].dataset.profileMatch, 'true');
    assert.equal(slots[1].dataset.profileMatch, 'false');
    assert.equal(slots[0].statuses[0].hidden, false);
    assert.equal(slots[1].statuses[1].hidden, true);
    assert.match(selectedMessage.textContent, /Jeune sélectionné : 1 créneaux adaptés/);

    inputs.forEach((input) => { input.checked = input.value === 'adulte-loisir'; });
    listeners['adulte-loisir']();
    assert.equal(slots[0].dataset.profileMatch, 'false');
    assert.equal(slots[1].dataset.profileMatch, 'true');
    assert.equal(slots[0].statuses[1].hidden, false);
    assert.equal(slots[1].statuses[1].hidden, false);
    assert.match(selectedMessage.textContent, /Adulte loisir sélectionné : 1 créneaux adaptés/);

    const emptySchedule = {
        querySelectorAll: () => [],
        querySelector: () => null,
    };
    const scheduleWithoutStatus = {
        querySelectorAll: (selector) => selector.includes('input') ? inputs : [],
        querySelector: (selector) => selector.includes(':checked') ? inputs[0] : null,
    };
    appModule.initializePracticeScheduleComparison({
        querySelectorAll: () => [emptySchedule, scheduleWithoutStatus],
    });

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

    delete globalThis.document;
    delete globalThis.window;
});
