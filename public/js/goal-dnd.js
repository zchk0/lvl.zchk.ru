document.addEventListener('livewire:init', () => {
  const boardSelector = '[data-goals-board]';
  const columnSelector = '[data-goals-column]';
  const cardSelector = '[data-goal-card]';
  let draggedCard = null;

  const isDragEnabled = () => {
    const board = document.querySelector(boardSelector);
    return !!board && board.dataset.dragEnabled === '1';
  };

  const getAfterElement = (container, y) => {
    const items = [...container.querySelectorAll(`${cardSelector}:not(.is-dragging)`)];

    return items.reduce(
      (closest, item) => {
        const box = item.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
          return { offset, element: item };
        }
        return closest;
      },
      { offset: Number.NEGATIVE_INFINITY, element: null }
    ).element;
  };

  const buildPayload = (root) => {
    return Array.from(root.querySelectorAll(columnSelector)).map((column) => ({
      priority: parseInt(column.dataset.priority, 10),
      ids: Array.from(column.querySelectorAll(cardSelector))
        .map((card) => parseInt(card.dataset.goalId, 10))
        .filter((id) => Number.isInteger(id) && id > 0),
    }));
  };

  const syncOrder = () => {
    if (!draggedCard || !isDragEnabled() || typeof Livewire === 'undefined') {
      return;
    }

    const component = draggedCard.closest('[wire\\:id]');
    if (!component) {
      return;
    }

    const payload = buildPayload(component);
    Livewire.find(component.getAttribute('wire:id')).call('syncGoalOrder', payload);
  };

  document.addEventListener('dragstart', (event) => {
    if (!isDragEnabled()) {
      return;
    }

    const card = event.target.closest(cardSelector);
    if (!card) {
      return;
    }

    draggedCard = card;
    card.classList.add('is-dragging', 'opacity-60', 'ring-2', 'ring-indigo-400/60');
    if (event.dataTransfer) {
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData('text/plain', card.dataset.goalId || '');
    }
  });

  document.addEventListener('dragover', (event) => {
    if (!draggedCard || !isDragEnabled()) {
      return;
    }

    const column = event.target.closest(columnSelector);
    if (!column) {
      return;
    }

    event.preventDefault();

    const afterElement = getAfterElement(column, event.clientY);
    if (afterElement == null) {
      column.appendChild(draggedCard);
    } else {
      column.insertBefore(draggedCard, afterElement);
    }
  });

  document.addEventListener('drop', (event) => {
    if (!draggedCard || !isDragEnabled()) {
      return;
    }

    const column = event.target.closest(columnSelector);
    if (column) {
      event.preventDefault();
    }
  });

  document.addEventListener('dragend', () => {
    if (!draggedCard) {
      return;
    }

    syncOrder();
    draggedCard.classList.remove('is-dragging', 'opacity-60', 'ring-2', 'ring-indigo-400/60');
    draggedCard = null;
  });
});
