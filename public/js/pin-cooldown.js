(() => {
    const notice = document.querySelector('[data-pin-cooldown]');
    if (!notice) return;

    const output = notice.querySelector('[data-pin-cooldown-time]');
    const until = Date.now() + Number(notice.dataset.retryAfter) * 1000;
    const update = () => {
        const remaining = Math.max(0, Math.ceil((until - Date.now()) / 1000));
        if (remaining === 0) {
            clearInterval(timer);
            window.location.reload();
            return;
        }

        output.textContent = `${String(Math.floor(remaining / 60)).padStart(2, '0')}:${String(remaining % 60).padStart(2, '0')}`;
    };

    const timer = setInterval(update, 1000);
    update();
})();
