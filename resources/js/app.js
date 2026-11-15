import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('cityCombobox', (options, field, initialValue) => ({
    options,
    field,
    selected: initialValue,
    query: '',
    open: false,
    activeIndex: -1,

    get filteredOptions() {
        const query = this.query.trim().toLowerCase();

        if (!query) {
            return this.options.slice(0, 20);
        }

        return this.options
            .filter((city) => `${city.name} ${city.label} ${city.province_label}`.toLowerCase().includes(query)
                || city.name.toLowerCase().replace(/^(kabupaten|kota)\s+/, '').includes(query))
            .slice(0, 20);
    },

    get activeOptionId() {
        return this.open && this.activeIndex >= 0
            ? `${this.field}-option-${this.activeIndex}`
            : null;
    },

    init() {
        this.syncQuery();
    },

    syncQuery() {
        const city = this.options.find((option) => option.name === this.selected);
        this.query = city ? `${city.label} · ${city.province_label}` : (this.selected || '');
    },

    searchChanged() {
        this.selected = '';
        this.activeIndex = -1;
        this.open = true;
        this.$dispatch('city-changed', { field: this.field, value: '' });
    },

    clear() {
        this.query = '';
        this.selected = '';
        this.activeIndex = -1;
        this.open = true;
        this.$dispatch('city-changed', { field: this.field, value: '' });
    },

    move(direction) {
        this.open = true;
        const count = this.filteredOptions.length;

        if (!count) {
            return;
        }

        this.activeIndex = (this.activeIndex + direction + count) % count;
    },

    handleEnter(event) {
        if (!this.open) {
            return;
        }

        const city = this.filteredOptions[this.activeIndex < 0 ? 0 : this.activeIndex];

        if (city) {
            event.preventDefault();
            this.select(city);
        }
    },

    select(city) {
        this.selected = city.name;
        this.query = `${city.label} · ${city.province_label}`;
        this.open = false;
        this.activeIndex = -1;
        this.$dispatch('city-changed', { field: this.field, value: city.name });
    },
}));

Alpine.start();