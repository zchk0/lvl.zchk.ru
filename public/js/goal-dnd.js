document.addEventListener('livewire:init', () => {
  const boardSelector = '[data-goals-board]';
  const columnSelector = '[data-goals-column]';
  const cardSelector = '[data-goal-card]';
  let draggedCard = null;

  const isDragEnabled = () => {
    const board = document.querySelector(boardSelector);
    return !!board && board.dataset.dragEnabled === '1';
  };

  const getAfterElementInSingleColumn = (items, y) => {
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

  const getAfterElement = (container, x, y) => {
    const items = [...container.querySelectorAll(`${cardSelector}:not(.is-dragging)`)];
    if (items.length === 0) {
      return null;
    }

    const laneTolerance = 8;
    const laneMap = new Map();
    items.forEach((item) => {
      const box = item.getBoundingClientRect();
      const laneKey = Math.round(box.left / laneTolerance) * laneTolerance;
      if (!laneMap.has(laneKey)) {
        laneMap.set(laneKey, []);
      }
      laneMap.get(laneKey).push({ item, box });
    });

    const lanes = Array.from(laneMap.entries())
      .sort((a, b) => a[0] - b[0])
      .map(([, entries]) => ({
        entries: entries.sort((a, b) => a.box.top - b.box.top),
      }));

    if (lanes.length <= 1) {
      return getAfterElementInSingleColumn(items, y);
    }

    const targetLaneIndex = lanes.reduce(
      (best, lane, index) => {
        const sample = lane.entries[0];
        const laneCenterX = sample.box.left + sample.box.width / 2;
        const distance = Math.abs(x - laneCenterX);
        if (distance < best.distance) {
          return { distance, index };
        }
        return best;
      },
      { distance: Number.POSITIVE_INFINITY, index: 0 }
    ).index;

    const targetLane = lanes[targetLaneIndex];
    const beforeInLane = targetLane.entries.find(({ box }) => y < box.top + box.height / 2);
    if (beforeInLane) {
      return beforeInLane.item;
    }

    const nextLane = lanes[targetLaneIndex + 1];
    if (nextLane && nextLane.entries.length > 0) {
      return nextLane.entries[0].item;
    }

    return null;
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

    const afterElement = getAfterElement(column, event.clientX, event.clientY);
    if (afterElement === draggedCard || afterElement === draggedCard.nextElementSibling) {
      return;
    }

    if (afterElement == null) {
      if (column.lastElementChild !== draggedCard) {
        column.appendChild(draggedCard);
      }
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
