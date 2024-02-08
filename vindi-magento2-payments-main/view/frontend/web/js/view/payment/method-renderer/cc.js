/**
 * Vindi
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Vindi license that is
 * available through the world-wide-web at this URL:
 *
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Vindi
 * @package     Vindi_VP
 * @copyright   Copyright (c) Vindi
 *
 */

define(
    [
        'underscore',
        'ko',
        'jquery',
        'mage/translate',
        'Magento_SalesRule/js/action/set-coupon-code',
        'Magento_SalesRule/js/action/cancel-coupon',
        'Magento_Customer/js/model/customer',
        'Magento_Payment/js/view/payment/cc-form',
        'Vindi_VP/js/model/credit-card-validation/credit-card-number-validator',
        'Magento_Payment/js/model/credit-card-validation/credit-card-data',
        'Vindi_VP/js/fingerprint',
        'Magento_Payment/js/model/credit-card-validation/validator',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/mage',
        'mage/validation',
        'vindi_vp/validation'
    ],
    function (
        _,
        ko,
        $,
        $t,
        setCouponCodeAction,
        cancelCouponCodeAction,
        customer,
        Component,
        cardNumberValidator,
        creditCardData,
        fingerprint
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Vindi_VP/payment/form/cc',
                taxvat: window.checkoutConfig.payment.vindi_vp_cc.customer_taxvat.replace(/[^0-9]/g, ""),
                creditCardOwner: '',
                creditCardInstallments: '',
                vindiCreditCardNumber: '',
                creditCardType: '',
                showCardData: ko.observable(true),
                installments: ko.observableArray([]),
                hasInstallments: ko.observable(false),
                installmentsUrl: '',
                showInstallmentsWarning: ko.observable(true),
                debounceTimer: null
            },

            /** @inheritdoc */
            initObservable: function () {
                var self = this;
                this._super()
                    .observe([
                        'taxvat',
                        'creditCardType',
                        'creditCardExpYear',
                        'creditCardExpMonth',
                        'vindiCreditCardNumber',
                        'creditCardType',
                        'creditCardVerificationNumber',
                        'selectedCardType',
                        'creditCardOwner',
                        'creditCardInstallments'
                    ]);

                this.creditCardVerificationNumber('');

                setCouponCodeAction.registerSuccessCallback(function () {
                    self.updateInstallmentsValues();
                });

                cancelCouponCodeAction.registerSuccessCallback(function () {
                    self.updateInstallmentsValues();
                });

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

                return this;
            },

            getCode: function () {
                return this.item.method;
            },

            /**
             * Get data
             * @returns {Object}
             */
            getData: function () {
                fingerprint(window.checkoutConfig.payment[this.getCode()].sandbox);
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'taxvat': this.taxvat(),
                        'cc_cid': this.creditCardVerificationNumber(),
                        'cc_type': this.creditCardType(),
                        'cc_exp_year': this.creditCardExpYear(),
                        'cc_exp_month': this.creditCardExpMonth(),
                        'cc_number': this.vindiCreditCardNumber(),
                        'cc_owner': this.creditCardOwner(),
                        'installments': this.creditCardInstallments(),
                        'fingerprint': window.yapay?.FingerPrint()?.getFingerPrint() || ''
                    }
                };
            },

            /**
             * Get list of available credit card types
             * @returns {Object}
             */
            getCcAvailableTypes: function () {
                return window.checkoutConfig.payment[this.getCode()].availableTypes;
            },

            getIcons: function (type) {
                return window.checkoutConfig.payment[this.getCode()].icons.hasOwnProperty(type)
                    ? window.checkoutConfig.payment[this.getCode()].icons[type]
                    : false;
            },

            /**
             * Check if payment is active
             *
             * @returns {Boolean}
             */
            isActive: function () {
                return this.getCode() === this.isChecked();
            },

            /**
             * @return {Boolean}
             */
            validate: function () {
                const $form = $('#' + 'form_' + this.getCode());
                return ($form.validation() && $form.validation('isValid'));
            },

            /**
             * @returns {boolean|*}
             */
            retrieveInstallmentsUrl: function () {
                try {
                    this.installmentsUrl = window.checkoutConfig.payment.ccform.urls[this.getCode()].retrieve_installments;
                    return this.installmentsUrl;
                } catch (e) {
                    console.log('Installments URL not defined');
                }
                return false;
            },

            isLoggedIn: function () {
                return customer.isLoggedIn();
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
                                form_key: window.checkoutConfig.formKey,
                                cc_type: self.creditCardType()
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
            }
        });
    }
);
