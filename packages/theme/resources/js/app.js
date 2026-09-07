export function initializePracticeScheduleComparison(root) {
  root.querySelectorAll('[data-schedule-comparison]').forEach((schedule) => {
    const inputs = schedule.querySelectorAll('input[name="esctt-practice-profile"]');
    const slots = schedule.querySelectorAll('[data-profile-slugs]');
    const selectedProfile = schedule.querySelector('input[name="esctt-practice-profile"]:checked');
    const statusMessage = schedule.querySelector('[data-profile-selected]');

    const update = (input) => {
      let matchCount = 0;

      slots.forEach((slot) => {
        const isMatch = slot.dataset.profileSlugs.split(' ').includes(input.value);
        slot.dataset.profileMatch = String(isMatch);
        slot.classList.toggle('is-match', isMatch);
        slot.querySelectorAll('[data-profile-status]').forEach((status) => {
          status.hidden = status.dataset.profileStatus !== input.value;
        });
        matchCount += Number(isMatch);
      });

      statusMessage.textContent = `${input.dataset.profileLabel} sélectionné : ${matchCount} créneaux adaptés. Tous les créneaux restent visibles.`;
    };

    inputs.forEach((input) => input.addEventListener('change', () => update(input)));
    update(selectedProfile);
  });
}

document.addEventListener('DOMContentLoaded', () => initializePracticeScheduleComparison(document));
