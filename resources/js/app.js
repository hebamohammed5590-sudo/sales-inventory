import './bootstrap';

import Alpine from 'alpinejs';

import Chart from 'chart.js/auto';

window.Alpine = Alpine;

Alpine.data('invoiceForm', () => ({
    products: [],

    invoiceType: 'sale',

    taxRate: '0',

    discountType: '',

    discountValue: '0',

    items: [],

    nextId: 0,

    init() {
        this.products = this.parseJson(
            this.$el.dataset.products,
            []
        );

        this.invoiceType = this.$el.dataset.invoiceType || 'sale';

        this.taxRate = this.$el.dataset.taxRate || '0';

        this.discountType = this.$el.dataset.discountType || '';

        this.discountValue = this.$el.dataset.discountValue || '0';

        const previousItems = this.parseJson(
            this.$el.dataset.previousItems,
            []
        );

        const restoredItems = Array.isArray(previousItems)
            ? previousItems
            : Object.values(previousItems);

        if (restoredItems.length > 0) {
            restoredItems.forEach((item) => {
                this.addItem(
                    item.product_id ?? '',
                    item.quantity ?? 1
                );
            });

            return;
        }

        this.addItem();
    },

    parseJson(value, fallback) {
        try {
            return JSON.parse(value || '');
        } catch {
            return fallback;
        }
    },

    addItem(productId = '', quantity = 1) {
        if (this.items.length >= 50) {
            return;
        }

        this.items.push({
            id: this.nextId++,

            product_id: String(productId),

            quantity: Number(quantity) || 1,
        });
    },

    removeItem(itemId) {
        if (this.items.length <= 1) {
            return;
        }

        this.items = this.items.filter(
            (item) => item.id !== itemId
        );
    },

    findProduct(productId) {
        return this.products.find(
            (product) => String(product.id) === String(productId)
        );
    },

    productLabel(product) {
        return [
            product.name,

            product.sku,

            'Stock: ' + product.quantity,
        ].join(' — ');
    },

    parseAmountToCents(value) {
        const normalized = String(
            value ?? '0'
        ).trim();

        if (!/^\d+(?:\.\d{1,2})?$/.test(normalized)) {
            return 0;
        }

        const parts = normalized.split('.');

        const whole = Number(parts[0]);

        const fraction = Number(
            (parts[1] ?? '').padEnd(2, '0')
        );

        return (whole * 100) + fraction;
    },

    formatCents(cents) {
        return (Number(cents || 0) / 100).toFixed(2);
    },

    unitPriceInCents(item) {
        const product = this.findProduct(
            item.product_id
        );

        if (!product) {
            return 0;
        }

        const price = this.invoiceType === 'purchase'
            ? product.cost_price
            : product.sell_price;

        return this.parseAmountToCents(
            price
        );
    },

    lineTotalInCents(item) {
        const quantity = Number(
            item.quantity
        );

        if (!Number.isFinite(quantity) || quantity < 1) {
            return 0;
        }

        return this.unitPriceInCents(
            item
        ) * quantity;
    },

    get subtotalInCents() {
        return this.items.reduce(
            (total, item) => total + this.lineTotalInCents(item),

            0
        );
    },

    get discountInCents() {
        const enteredDiscount = this.parseAmountToCents(
            this.discountValue || '0'
        );

        let discount = 0;

        if (this.discountType === 'fixed') {
            discount = enteredDiscount;
        }

        if (this.discountType === 'percentage') {
            discount = Math.round(
                (this.subtotalInCents * enteredDiscount) / 10000
            );
        }

        return Math.min(
            discount,

            this.subtotalInCents
        );
    },

    get taxableAmountInCents() {
        return this.subtotalInCents - this.discountInCents;
    },

    get taxInCents() {
        const taxRateInCents = this.parseAmountToCents(
            String(this.taxRate)
        );

        return Math.round(
            (this.taxableAmountInCents * taxRateInCents) / 10000
        );
    },

    get totalInCents() {
        return this.taxableAmountInCents + this.taxInCents;
    },
}));

Alpine.start();

// كود الرسوم البيانية للـ Dashboard (إن وجد)
document.addEventListener('DOMContentLoaded', function () {
    const chartsContainer = document.getElementById('dashboard-charts');
    if (chartsContainer && window.dashboardChartsData) {
        // ... Chart.js initialization logic if existing ...
    }
});