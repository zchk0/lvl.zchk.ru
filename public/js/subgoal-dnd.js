document.addEventListener('livewire:init', () => {
  const boardSelector = '[data-subgoals-board]';
  const listSelector = '[data-subgoals-list]';
  const itemSelector = '[data-subgoal-item]';
  const handleSelector = '[data-subgoal-drag-handle]';
  let draggedItem = null;

  const getAfterElement = (container, y) => {
    const items = [...container.querySelectorAll(`${itemSelector}:not(.is-dragging)`)]
      .filter((item) => item.offsetParent !== null);

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

  const syncSubgoalOrder = () => {
    if (!draggedItem || typeof Livewire === 'undefined') {
      return;
    }

    const board = draggedItem.closest(boardSelector);
    const component = draggedItem.closest('[wire\\:id]');
    if (!board || !component) {
      return;
    }

    const goalId = parseInt(board.dataset.goalId || '', 10);
    if (!Number.isInteger(goalId) || goalId <= 0) {
      return;
    }

    const list = board.querySelector(listSelector);
    if (!list) {
      return;
    }

    const orderedIds = Array.from(list.querySelectorAll(itemSelector))
      .map((item) => item.dataset.subgoalId || '')
      .filter((id) => id !== '');

    Livewire.find(component.getAttribute('wire:id')).call('reorderSubgoals', goalId, orderedIds);
  };

  document.addEventListener('dragstart', (event) => {
    const item = event.target.closest(itemSelector);
    if (!item) {
      return;
    }

    const handle = event.target.closest(handleSelector);
    if (!handle) {
      event.preventDefault();
      return;
    }

    draggedItem = item;
    item.classList.add('is-dragging', 'opacity-60', 'ring-1', 'ring-indigo-400/60');

    if (event.dataTransfer) {
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData('text/plain', item.dataset.subgoalId || '');
    }
  });

  document.addEventListener('dragover', (event) => {
    if (!draggedItem) {
      return;
    }

    const list = event.target.closest(listSelector);
    if (!list) {
      return;
    }

    event.preventDefault();

    const afterElement = getAfterElement(list, event.clientY);
    if (afterElement == null) {
      if (list.lastElementChild !== draggedItem) {
        list.appendChild(draggedItem);
      }
      return;
    }

    if (afterElement !== draggedItem && afterElement.previousElementSibling !== draggedItem) {
      list.insertBefore(draggedItem, afterElement);
    }
  });

  document.addEventListener('drop', (event) => {
    if (!draggedItem) {
      return;
    }

    const list = event.target.closest(listSelector);
    if (list) {
      event.preventDefault();
    }
  });

  document.addEventListener('dragend', () => {
    if (!draggedItem) {
      return;
    }

    syncSubgoalOrder();
    draggedItem.classList.remove('is-dragging', 'opacity-60', 'ring-1', 'ring-indigo-400/60');
    draggedItem = null;
  });
});
