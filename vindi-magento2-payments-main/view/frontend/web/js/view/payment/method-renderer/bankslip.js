/*browser:true*/
/*global define*/

define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Vindi_VP/js/fingerprint'
    ],
    function (Component, fingerprint) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Vindi_VP/payment/form/bankslip',
                taxvat: window.checkoutConfig.payment.vindi_vp_bankslip.customer_taxvat.replace(/[^0-9]/g, "")
            },

            /** @inheritdoc */
            initObservable: function () {
                this._super().observe([
                    'taxvat'
                ]);

                return this;
            },

            getCode: function() {
                return 'vindi_vp_bankslip';
            },

            hasInstructions: function () {
                return (this.getInstructions().length > 0);
            },

            getInstructions: function () {
                return window.checkoutConfig.payment.vindi_vp_bankslip.checkout_instructions;
            },

            getData: function() {
                fingerprint(window.checkoutConfig.payment[this.getCode()].sandbox);
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'taxvat': this.taxvat(),
                        'fingerprint': window.yapay?.FingerPrint()?.getFingerPrint() || ''
                    }
                };
            }
        });
    }
);

