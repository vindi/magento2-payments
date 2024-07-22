define(
    [
        'jquery',
        'uiComponent',
        'ko',
        'Magento_Payment/js/model/credit-card-validation/credit-card-data',
        'Vindi_VP/js/model/credit-card-validation/credit-card-number-validator',
        'Vindi_VP/js/fingerprint',
        'vindi-cc-form',
        'mage/url'
    ],
    function (
        $,
        Component,
        ko,
        creditCardData,
        cardNumberValidator,
        fingerprint,
        creditCardForm,
        url
    ) {


        return Component.extend({
            defaults: {
                template: 'Vindi_VP/payment/form/cc',
                showCardData: ko.observable(true),
                installments: ko.observableArray([]),
                hasInstallments: ko.observable(false),
                selectedCardType: ko.observable(null),
                creditCardVerificationNumber: ko.observable(''),
                showInstallmentsWarning: ko.observable(true),
                creditCardInstallments: ko.observable(''),
                creditCardOwner: ko.observable(''),
                creditCardExpDate: ko.observable(''),
                taxvat: ko.observable(''),
                vindiCreditCardNumber: ko.observable(''),
                creditCardType: ko.observable(''),
                isCheckoutPage: ko.observable(false)
            },

            initialize: function (config) {
                this._super();
                var self = this;
                this.taxvat(this.customerTaxvat.replace(/[^0-9]/g, ""));

                this.creditCardVerificationNumber('');

                //Set credit card number to credit card data object
                this.vindiCreditCardNumber.subscribe(function (value) {
                    let result;

                    self.installments.removeAll();
                    self.hasInstallments(false);
                    self.showInstallmentsWarning(true);
                    self.selectedCardType(null);

                    if (value === '' || value === null) {
                        return false;
                    }

                    result = cardNumberValidator(value);
                    if (!result.isValid) {
                        return false;
                    }

                    if (result.card !== null) {
                        self.selectedCardType(result.card.type);
                        creditCardData.creditCard = result.card;
                    }

                    if (result.isValid) {
                        creditCardData.vindiCreditCardNumber = value;
                        self.creditCardType(result.card.type);
                    }

                    self.updateInstallmentsValues();
                });
                this.creditCardVerificationNumber.subscribe(function (value) {
                    creditCardData.cvvCode = value;
                });

                return this;
            },

            getCode: function () {
                return this.methodCode;
            },

            isChecked: function () {
                return 'vindi_vp_cc';
            },

            /**
             * @return {Boolean}
             */
            selectPaymentMethod: function () {
                return true;
            },

            isRadioButtonVisible: function () {
                return false;
            },

            /**
             * Get payment method type.
             */
            getTitle: function () {
                return this.methodTitle;
            },

            getCcAvailableTypes: function () {
                return {
                    "AE": "American Express",
                    "ELO": "Elo",
                    "HC": "Hipercard",
                    "HI": "Hiper",
                    "MC": "MasterCard",
                    "VI": "Visa"
                }
            },

            getCcAvailableTypesValues: function () {
                return _.map(this.getCcAvailableTypes(), function (value, key) {
                    return {
                        'value': key,
                        'type': value
                    };
                });
            },

            updateInstallmentsValues: function () {

                var self = this;
                if (self.vindiCreditCardNumber().length > 6) {
                    if (self.debounceTimer !== null) {
                        clearTimeout(self.debounceTimer);
                    }

                    //I need to change it to a POST with body
                    self.debounceTimer = setTimeout(() => {
                        fetch(self.retrieveInstallmentsUrl(), {
                            method: 'POST',
                            cache: 'no-cache',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                form_key: self.formKey,
                                cc_type: self.creditCardType(),
                                payment_link: {
                                    grand_total: self.grandTotal
                                }
                            })
                        }).then((response) => {
                            self.installments.removeAll();
                            return response.json();
                        }).then(json => {
                            json.forEach(function (installment) {
                                self.installments.push(installment);
                                self.hasInstallments(true);
                                self.showInstallmentsWarning(false);
                            });
                        });
                    }, 500);
                }
            },

            getIcons: function (code) {
                return JSON.parse(window.ccIcons)[code];
            },

            isActive: function () {
                return this.getCode() === this.isChecked();
            },

            loadCard: function () {
                let ccName = document.getElementById(this.getCode() + '_cc_owner');
                let ccNumber = document.getElementById(this.getCode() + '_cc_number');
                let ccExpDate = document.getElementById(this.getCode() + '_cc_exp_date');
                let ccCvv = document.getElementById(this.getCode() + '_cc_cid');
                let ccSingle = document.getElementById('vindi-vp-cc-ccsingle');
                let ccFront = document.getElementById('vindi-vp-cc-front');
                let ccBack = document.getElementById('vindi-vp-cc-back');

                creditCardForm(ccName, ccNumber, ccExpDate, ccCvv, ccSingle, ccFront, ccBack);
            },

            /**
             * @return {String}
             */
            getBillingAddressFormName: function () {
                return 'billing-address-form-' + this.methodCode;
            },

            /**
             * Is legend available to display
             * @returns {Boolean}
             */
            isShowLegend: function () {
                return false;
            },

            retrieveInstallmentsUrl: function () {
                return url.build('vindi_vp/installments/retrieve');
            },

            /**
             * Get data
             * @returns {Object}
             */
            getData: function () {

                fingerprint(this.isSandbox);
                let ccExpMonth = '';
                let ccExpYear = '';
                let ccExpDate = this.creditCardExpDate();

                if (typeof ccExpDate !== "undefined" && ccExpDate !== null) {
                    let ccExpDateFull = ccExpDate.split('/');
                    ccExpMonth = ccExpDateFull[0];
                    ccExpYear = ccExpDateFull[1];
                }
                return {
                    'method': this.methodCode,
                    'finger_print': window.yapay?.FingerPrint()?.getFingerPrint() || '',
                    'additional_data': {
                        'taxvat': this.taxvat(),
                        'cc_cid': this.creditCardVerificationNumber(),
                        'cc_type': this.creditCardType(),
                        'cc_exp_month': ccExpMonth,
                        'cc_exp_year': ccExpYear.length === 4 ? ccExpYear : '20' + ccExpYear,
                        'cc_number': this.vindiCreditCardNumber(),
                        'cc_owner': this.creditCardOwner(),
                        'installments': this.creditCardInstallments(),
                    }
                };
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
            }
        });

    });
