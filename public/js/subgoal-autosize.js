document.addEventListener('livewire:init', () => {
  const selector = '[data-subgoal-title]';
  let resizeTimer = null;

  const resizeField = (field) => {
    if (!(field instanceof HTMLTextAreaElement)) {
      return;
    }

    field.style.height = '1.75rem';
    field.style.height = `${Math.max(field.scrollHeight, 28)}px`;
  };

  const resizeAll = (root = document) => {
    if (root.matches?.(selector)) {
      resizeField(root);
    }

    root.querySelectorAll(selector).forEach(resizeField);
  };

  const scheduleResize = (root = document) => {
    requestAnimationFrame(() => {
      resizeAll(root);
      requestAnimationFrame(() => resizeAll(root));
    });

    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => resizeAll(root), 150);
  };

  document.addEventListener('input', (event) => {
    if (event.target?.matches?.(selector)) {
      resizeField(event.target);
    }
  });

  window.addEventListener('resize', () => scheduleResize());
  document.addEventListener('livewire:navigated', () => scheduleResize());

  Livewire.hook('morphed', ({ el }) => scheduleResize(el || document));
  scheduleResize();
});
