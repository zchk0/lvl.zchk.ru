(() => {
    document.querySelectorAll('[data-code-tabs]').forEach(group => {
        const tabs = [...group.querySelectorAll('[data-example-tab]')];
        const select = tab => {
            tabs.forEach(item => {
                const active = item === tab;
                item.setAttribute('aria-selected', String(active));
                item.tabIndex = active ? 0 : -1;
                document.getElementById(item.getAttribute('aria-controls')).hidden = !active;
            });
        };

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => select(tab));
            tab.addEventListener('keydown', event => {
                const target = {
                    ArrowRight: (index + 1) % tabs.length,
                    ArrowLeft: (index - 1 + tabs.length) % tabs.length,
                    Home: 0,
                    End: tabs.length - 1,
                }[event.key];
                if (target === undefined) return;
                event.preventDefault();
                select(tabs[target]);
                tabs[target].focus();
            });
        });
    });

    const copyText = async text => {
        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(text);
                return;
            } catch {
                // Fall back to selection-based copying when clipboard access is denied.
            }
        }

        const previousFocus = document.activeElement;
        const field = document.createElement('textarea');
        field.value = text;
        field.readOnly = true;
        field.style.cssText = 'position:fixed;top:0;left:0;opacity:0;';
        document.body.appendChild(field);
        try {
            field.select();
            if (!document.execCommand('copy')) throw new Error('copy failed');
        } finally {
            field.remove();
            previousFocus?.focus({preventScroll: true});
        }
    };

    document.querySelectorAll('[data-copy-code]').forEach(button => {
        let resetTimer;
        button.addEventListener('click', async () => {
            const block = button.closest('[data-code-block]');
            const code = block.querySelector('code');
            const label = button.querySelector('[data-copy-label]');
            const status = block.querySelector('[data-copy-status]');
            clearTimeout(resetTimer);
            try {
                await copyText(code.textContent);
                label.textContent = 'Скопировано';
                status.textContent = 'Пример скопирован в буфер обмена.';
            } catch {
                const range = document.createRange();
                range.selectNodeContents(code);
                const selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
                label.textContent = 'Выделено';
                status.textContent = 'Пример выделен. Скопируйте его вручную.';
            }
            resetTimer = setTimeout(() => {
                label.textContent = 'Копировать';
                status.textContent = '';
            }, 2500);
        });
    });
})();
