import domReady from '@wordpress/dom-ready';

domReady(() => {
  const { blocks, blockEditor, components, element, i18n } = window.wp;
  const { createElement, Fragment } = element;
  const {
    BlockControls,
    InspectorControls,
    RichText,
    useBlockProps,
  } = blockEditor;
  const { PanelBody, TextControl, ToolbarButton } = components;
  const { __ } = i18n;

  blocks.registerBlockType('esctt/hero', {
    apiVersion: 3,
    title: __('Hero', 'esctt'),
    description: __('The required page heading.', 'esctt'),
    icon: 'cover-image',
    category: 'design',
    attributes: {
      title: {
        type: 'string',
        default: '',
      },
      compact: {
        type: 'boolean',
        default: false,
      },
    },
    supports: {
      html: false,
      multiple: false,
      reusable: false,
    },
    edit: ({ attributes, setAttributes }) => {
      const blockProps = useBlockProps({
        className: attributes.compact ? 'esctt-hero esctt-hero--compact' : 'esctt-hero',
      });

      return createElement(
        Fragment,
        null,
        createElement(
          BlockControls,
          null,
          createElement(ToolbarButton, {
            label: __('Toggle compact Hero', 'esctt'),
            isPressed: attributes.compact,
            onClick: () => setAttributes({ compact: ! attributes.compact }),
          }),
        ),
        createElement(
          'section',
          blockProps,
          createElement(RichText, {
            tagName: 'h1',
            value: attributes.title,
            onChange: (title) => setAttributes({ title }),
            placeholder: __('Page title…', 'esctt'),
          }),
        ),
      );
    },
    save: () => null,
  });

  blocks.registerBlockType('esctt/sport-life', {
    apiVersion: 3,
    title: __('Sports life', 'esctt'),
    description: __('A concise sports life section for the public pages.', 'esctt'),
    icon: 'groups',
    category: 'design',
    attributes: {
      helloAssoUrl: {
        type: 'string',
        default: '',
      },
    },
    supports: {
      html: false,
      multiple: false,
      reusable: false,
    },
    edit: ({ attributes, setAttributes }) => createElement(
      Fragment,
      null,
      createElement(
        InspectorControls,
        null,
        createElement(
          PanelBody,
          { title: __('Family tournament registration', 'esctt') },
          createElement(TextControl, {
            label: __('External HelloAsso URL', 'esctt'),
            help: __('Leave empty until registration opens. Paste the external HelloAsso URL when it is available.', 'esctt'),
            value: attributes.helloAssoUrl,
            onChange: (helloAssoUrl) => setAttributes({ helloAssoUrl }),
            type: 'url',
          }),
        ),
      ),
      createElement(
        'section',
        useBlockProps({ className: 'esctt-sport-life esctt-sport-life--editor' }),
        createElement('p', null, __('Sports life section: family tournament first, FFTT competitions, and the real jersey photos.', 'esctt')),
      ),
    ),
    save: () => null,
  });
});
