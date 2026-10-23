window.goalAuthorTags = (value, input, options) => ({
    value,
    input,
    options,
    open: false,
    activeIndex: -1,

    parse(value) {
        const seen = new Set();
        return (value || '').split(/[,;\r\n]+/).map(tag => tag.replace(/\s+/gu, ' ').trim())
            .filter(tag => {
                const key = tag.toLocaleLowerCase();
                if (!tag || seen.has(key)) return false;
                seen.add(key);
                return true;
            });
    },

    get tags() {
        return this.parse(this.value);
    },

    get suggestions() {
        const selected = new Set(this.tags.map(tag => tag.toLocaleLowerCase()));
        const query = this.input.trim().toLocaleLowerCase();
        return this.options.filter(tag => !selected.has(tag.toLocaleLowerCase())
            && tag.toLocaleLowerCase().includes(query));
    },

    add(value = this.input) {
        this.value = this.parse([...this.tags, value].join(', ')).join(', ');
        this.input = '';
        this.activeIndex = -1;
        this.open = false;
    },

    remove(index) {
        this.value = this.tags.filter((tag, tagIndex) => tagIndex !== index).join(', ');
        this.activeIndex = -1;
    },

    move(direction) {
        this.open = true;
        const count = this.suggestions.length;
        this.activeIndex = count
            ? (this.activeIndex < 0 ? (direction > 0 ? 0 : count - 1)
                : (this.activeIndex + direction + count) % count)
            : -1;
        this.$nextTick(() => {
            this.$refs.suggestions?.querySelector('[aria-selected="true"]')
                ?.scrollIntoView({ block: 'nearest' });
        });
    },

    keydown(event) {
        if (event.isComposing) return;

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            this.move(event.key === 'ArrowDown' ? 1 : -1);
        } else if (event.key === 'Enter' || event.key === ',' || event.key === ';') {
            event.preventDefault();
            this.add(this.open && this.activeIndex >= 0
                ? this.suggestions[this.activeIndex] : this.input);
        } else if (event.key === 'Tab') {
            if (this.input.trim()) this.add();
            this.open = false;
        } else if (event.key === 'Escape' && this.open) {
            event.preventDefault();
            event.stopPropagation();
            this.open = false;
        }
    },
});
