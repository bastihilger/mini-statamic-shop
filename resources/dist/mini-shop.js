/*
 * Mini Statamic Shop – Alpine.js components.
 *
 * Load this before Alpine starts, either with {{ mini_shop:scripts }} in the
 * <head> or by importing it in your bundle before Alpine.start().
 * It registers the store "miniShop" and the components "miniShopBuy",
 * "miniShopCart" and "miniShopCheckout".
 */
(function () {
    function register(Alpine) {
        const config = window.miniShop || {};

        async function post(url, data) {
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': config.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(data),
            });

            const json = await response.json().catch(() => ({}));

            if (!response.ok) {
                const error = new Error(json.message || response.statusText);
                error.status = response.status;
                error.errors = json.errors || {};
                throw error;
            }

            return json;
        }

        Alpine.store('miniShop', {
            cart: config.cart || { items: [], count: 0 },
            texts: config.texts || {},
            urls: config.urls || {},
            busy: false,

            get isEmpty() {
                return !this.cart.items || this.cart.items.length === 0;
            },

            async add(productId, articleNumber, quantity) {
                this.busy = true;
                try {
                    this.cart = await post(config.endpoints.add, {
                        product_id: productId,
                        article_number: articleNumber,
                        quantity: parseInt(quantity, 10) || 1,
                    });
                } finally {
                    this.busy = false;
                }
            },

            async update(item, quantity) {
                this.busy = true;
                try {
                    this.cart = await post(config.endpoints.update, {
                        product_id: item.product.id,
                        article_number: item.article.article_number,
                        quantity: Math.max(0, parseInt(quantity, 10) || 0),
                    });
                } finally {
                    this.busy = false;
                }
            },

            remove(item) {
                return this.update(item, 0);
            },

            attributes(item) {
                return (item.article.attributes || [])
                    .map((attribute) => attribute.title + ': ' + attribute.value)
                    .join(' / ');
            },
        });

        Alpine.data('miniShopBuy', (product) => ({
            product: product,
            articleNumber: product.articles.length ? product.articles[0].article_number : null,
            quantity: 1,
            added: false,

            get article() {
                return this.product.articles.find((article) => article.article_number === this.articleNumber);
            },

            async add() {
                await this.$store.miniShop.add(this.product.id, this.articleNumber, this.quantity);
                this.added = true;
                setTimeout(() => (this.added = false), 2000);
            },
        }));

        Alpine.data('miniShopCart', () => ({
            setQuantity(item, value) {
                const quantity = parseInt(value, 10);
                if (quantity > 0 && quantity !== item.quantity) {
                    this.$store.miniShop.update(item, quantity);
                }
            },
        }));

        Alpine.data('miniShopCheckout', (address, fields) => ({
            address: address,
            fields: fields,
            section: 'data',
            errors: {},
            failed: false,

            get emailIsValid() {
                return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(this.address.email || '');
            },

            get addressIsValid() {
                return this.missingFields().length === 0;
            },

            missingFields() {
                const types = this.address.shipping_same_as_invoice ? ['invoice'] : ['invoice', 'shipping'];
                const missing = [];
                types.forEach((type) => {
                    this.fields.forEach((field) => {
                        if (field.required && !(this.address[type] || {})[field.name]) {
                            missing.push(type + '.' + field.name);
                        }
                    });
                });
                return missing;
            },

            error(key) {
                return this.errors[key] || '';
            },

            clearError(key) {
                delete this.errors[key];
            },

            requiredMessage(fieldName) {
                return this.$store.miniShop.texts.generic_required.replace(':field', this.$store.miniShop.texts[fieldName] || fieldName);
            },

            validateData() {
                this.errors = {};
                if (!this.emailIsValid) {
                    this.errors.email = this.address.email
                        ? this.$store.miniShop.texts.email_invalid
                        : this.requiredMessage('email');
                    return;
                }
                this.section = 'address';
            },

            validateAddress() {
                this.errors = {};
                const missing = this.missingFields();
                if (missing.length) {
                    missing.forEach((key) => (this.errors[key] = this.requiredMessage(key.split('.')[1])));
                    return;
                }
                this.pay();
            },

            async pay() {
                this.section = 'redirecting';
                this.failed = false;
                try {
                    const response = await post(config.endpoints.checkout, this.address);
                    window.location.href = response.url;
                } catch (error) {
                    this.section = 'address';
                    this.failed = true;
                    Object.entries(error.errors || {}).forEach(([key, messages]) => {
                        this.errors[key] = messages[0];
                    });
                    if (this.errors.email) {
                        this.section = 'data';
                    }
                }
            },
        }));
    }

    if (window.Alpine && window.Alpine.version) {
        register(window.Alpine);
    } else {
        document.addEventListener('alpine:init', () => register(window.Alpine));
    }
})();
