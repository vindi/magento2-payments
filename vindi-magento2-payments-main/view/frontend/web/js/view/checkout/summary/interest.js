define(
    [
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/totals'
    ],
    function (Component, quote, priceUtils, totals) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Vindi_VP/checkout/summary/interest'
            },
            totals: quote.getTotals(),
            isDisplayed: function() {
                console.log(this.totals);
                return this.isFullMode();
            },

            getRawValue: function () {
                var price = 0;
                if (this.totals() && totals.getSegment('vindi_interest')) {
                    price = totals.getSegment('vindi_interest').value;
                }
                return price;
            },

            getValue: function() {
                var price = this.getRawValue();
                return this.getFormattedPrice(price);
            },
        });
    }
);
