define(
    [
        'uiComponent',
        'ko',
        'jquery',
        'Vindi_VP/js/fingerprint',
        'mage/url'
    ],
    function (Component, ko, $, fingerprint, url) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Vindi_VP/payment/form/pix',
                taxvat: ko.observable(''),
                isCheckoutPage: ko.observable(false)
            },

            /** @inheritdoc */
            initObservable: function () {
                this._super();
                var self = this;

                self.taxvat(self.customerTaxvat.replace(/[^0-9]/g, ""))
                return this;
            },

            isChecked: function () {
                return 'vindi_vp_pix';
            },

            isRadioButtonVisible: function () {
                return false;
            },

            /**
             * @return {Boolean}
             */
            selectPaymentMethod: function () {
                return true;
            },

            getCode: function() {
                return 'vindi_vp_bankslippix';
            },

            getData: function() {
                var self = this;
                fingerprint(this.isSandbox.sandbox);
                return {
                    'method': self.methodCode,
                    'finger_print': window.yapay?.FingerPrint()?.getFingerPrint() || '',
                    'additional_data': {
                        'taxvat': self.taxvat(),
                    }
                };
            },

            hasInstructions: function () {
                return (this.getInstructions().length > 0);
            },

            getInstructions: function () {
                return this.instructions;
            },

            sendPayment: function () {
                var self = this;
                $.ajax({
                    url: url.build('vindi_vp/checkout/sendtransaction'),
                    data: {
                        order_id: this.orderId,
                        payment_data: this.getData(),
                        form_key: this.formKey
                    },
                    type: "POST",
                    showLoader: true
                }).done(function (response) {
                    if (response.success) {
                        window.paymentLinkSuccess = true;
                        window.location.href = url.build('vindi_vp/checkout/success?order_id=' + self.orderId);
                    }
                });
            },

            /**
             * Get payment method type.
             */
            getTitle: function () {
                return this.methodTitle;
            },
        });
    }
);

