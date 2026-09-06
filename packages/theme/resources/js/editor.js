import domReady from '@wordpress/dom-ready';

domReady(() => {
  const { blocks, blockEditor, components, element, i18n } = window.wp;
  const { createElement, Fragment } = element;
  const { BlockControls, RichText, useBlockProps } = blockEditor;
  const { ToolbarButton } = components;
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
});
