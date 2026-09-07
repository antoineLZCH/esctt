window.addEventListener('DOMContentLoaded', () => {
  const settings = window.escttRegistrationDocument || {};
  const field = document.getElementById('esctt-registration-document-attachment-id');
  const selectButton = document.getElementById('esctt-registration-document-select');
  const removeButton = document.getElementById('esctt-registration-document-remove');
  const fileName = document.getElementById('esctt-registration-document-file-name');

  if (
    !field ||
    !selectButton ||
    !removeButton ||
    !fileName ||
    !window.wp ||
    typeof window.wp.media !== 'function'
  ) {
    return;
  }

  let frame;

  selectButton.addEventListener('click', (event) => {
    event.preventDefault();

    if (!frame) {
      frame = window.wp.media({
        title: settings.title,
        button: { text: settings.button },
        multiple: false,
      });

      frame.on('select', () => {
        const attachment = frame.state().get('selection').first().toJSON();
        const name = attachment.filename || attachment.title || '';

        field.value = attachment.id || '';
        fileName.textContent = name
          ? settings.selected.replace('%s', name)
          : settings.empty;
        removeButton.hidden = !field.value;
      });
    }

    frame.open();
  });

  removeButton.addEventListener('click', (event) => {
    event.preventDefault();
    field.value = '';
    fileName.textContent = settings.empty;
    removeButton.hidden = true;
  });
});
