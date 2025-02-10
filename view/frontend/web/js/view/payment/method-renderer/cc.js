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
        'vindi-cc-form',
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
        fingerprint,
        creditCardForm
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Vindi_VP/payment/form/cc',
                taxvat: (window.checkoutConfig && window.checkoutConfig.payment && window.checkoutConfig.payment.vindi_vp_cc && window.checkoutConfig.payment.vindi_vp_cc.customer_taxvat) ? window.checkoutConfig.payment.vindi_vp_cc.customer_taxvat.replace(/[^0-9]/g, "") : "",
                creditCardOwner: '',
                creditCardInstallments: '',
                vindiCreditCardNumber: '',
                creditCardType: '',
                showCardData: ko.observable(true),
                installments: ko.observableArray([]),
                hasInstallments: ko.observable(false),
                installmentsUrl: '',
                showInstallmentsWarning: ko.observable(false),
                debounceTimer: null,
                isCheckoutPage: ko.observable(true),
                paymentProfiles: [],
                selectedPaymentProfile: '',
                installmentsDisabled: ko.observable(true),
                saveCard: false
            },

            /** @inheritdoc */
            initObservable: function () {
                var self = this;
                this._super().observe([
                    'taxvat',
                    'creditCardType',
                    'creditCardExpDate',
                    'creditCardExpYear',
                    'creditCardExpMonth',
                    'vindiCreditCardNumber',
                    'creditCardType',
                    'creditCardVerificationNumber',
                    'selectedCardType',
                    'creditCardOwner',
                    'creditCardInstallments',
                    'selectedPaymentProfile',
                    'saveCard'
                ]);

                this.creditCardVerificationNumber('');

                setCouponCodeAction.registerSuccessCallback(function () {
                    self.updateInstallmentsValues();
                });

                cancelCouponCodeAction.registerSuccessCallback(function () {
                    self.updateInstallmentsValues();
                });

                this.vindiCreditCardNumber.subscribe(function (value) {
                    if (!value) {
                        return false;
                    }
                    var result = cardNumberValidator(value);
                    if (!result || !result.isValid) {
                        return false;
                    }
                    if (result.card !== null) {
                        self.selectedCardType(result.card.type);
                        creditCardData.creditCard = result.card;
                    }
                    creditCardData.vindiCreditCardNumber = value;
                    self.creditCardType(result.card.type);
                    self.updateInstallmentsValues();
                });

                this.selectedPaymentProfile.subscribe(function (value) {
                    if (value) {
                        var cardProfiles = self.getPaymentProfiles();
                        var selectedCard = cardProfiles.find(function (card) {
                            return card.value == value;
                        });
                        if (selectedCard && selectedCard.card_type) {
                            self.creditCardType(selectedCard.card_type);
                        }
                    }
                    self.updateInstallmentsValues();
                });

                self.installmentsDisabled(true);
                this.updateInstallmentsValues();

                return this;
            },

            loadCard: function () {
                var ccName = document.getElementById(this.getCode() + '_cc_owner');
                var ccNumber = document.getElementById(this.getCode() + '_cc_number');
                var ccExpDate = document.getElementById(this.getCode() + '_cc_exp_date');
                var ccCvv = document.getElementById(this.getCode() + '_cc_cid');
                var ccSingle = document.getElementById('vindi-vp-cc-ccsingle');
                var ccFront = document.getElementById('vindi-vp-cc-front');
                var ccBack = document.getElementById('vindi-vp-cc-back');
                if (ccName && ccNumber && ccExpDate && ccCvv && ccSingle && ccFront && ccBack) {
                    creditCardForm(ccName, ccNumber, ccExpDate, ccCvv, ccSingle, ccFront, ccBack);
                }
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
                var ccExpMonth = '';
                var ccExpYear = '';
                var ccExpDate = this.creditCardExpDate();
                if (typeof ccExpDate !== "undefined" && ccExpDate !== null) {
                    var ccExpDateFull = ccExpDate.split('/');
                    ccExpMonth = ccExpDateFull[0];
                    ccExpYear = ccExpDateFull[1];
                }
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'payment_profile': this.selectedPaymentProfile(),
                        'taxvat': this.taxvat(),
                        'cc_cid': this.creditCardVerificationNumber(),
                        'cc_type': this.mapCardType(this.creditCardType()),
                        'cc_exp_month': ccExpMonth,
                        'cc_exp_year': ccExpYear && ccExpYear.length === 4 ? ccExpYear : '20' + ccExpYear,
                        'cc_number': this.vindiCreditCardNumber(),
                        'cc_owner': this.creditCardOwner(),
                        'installments': this.creditCardInstallments(),
                        'save_card': this.saveCard() ? 1 : 0,
                        'fingerprint': (window.yapay && window.yapay.FingerPrint) ? window.yapay.FingerPrint().getFingerPrint() : ''
                    }
                };
            },

            getCcAvailableTypes: function () {
                return window.checkoutConfig && window.checkoutConfig.payment && window.checkoutConfig.payment[this.getCode()] && window.checkoutConfig.payment[this.getCode()].availableTypes ? window.checkoutConfig.payment[this.getCode()].availableTypes : [];
            },

            getIcons: function (type) {
                var config = window.checkoutConfig && window.checkoutConfig.payment && window.checkoutConfig.payment[this.getCode()];
                if (config && config.icons && config.icons.hasOwnProperty(type)) {
                    return config.icons[type];
                }
                return false;
            },

            isActive: function () {
                return this.getCode() === this.isChecked();
            },

            validate: function () {
                var $form = $('#' + 'form_' + this.getCode());
                return ($form && $form.validation() && $form.validation('isValid'));
            },

            retrieveInstallmentsUrl: function () {
                try {
                    return window.checkoutConfig.payment && window.checkoutConfig.payment.ccform && window.checkoutConfig.payment.ccform.urls && window.checkoutConfig.payment.ccform.urls[this.getCode()] && window.checkoutConfig.payment.ccform.urls[this.getCode()].retrieve_installments ? window.checkoutConfig.payment.ccform.urls[this.getCode()].retrieve_installments : "";
                } catch (e) {
                    console.log('Installments URL not defined');
                    return "";
                }
            },

            isLoggedIn: function () {
                return customer.isLoggedIn();
            },

            mapCardType: function (type) {
                var mapping = {
                    'Mastercard': 'MC',
                    'Aura': 'AU',
                    'Visa': 'VI',
                    'Elo': 'ELO',
                    'American Express': 'AE',
                    'JCB': 'JCB',
                    'Hipercard': 'HC',
                    'Hiper': 'HI'
                };
                return mapping[type] ? mapping[type] : type;
            },

            updateInstallmentsValues: function () {
                var self = this;
                self.installmentsDisabled(true);
                if (self.debounceTimer !== null) {
                    clearTimeout(self.debounceTimer);
                }
                self.debounceTimer = setTimeout(function () {
                    var url = self.retrieveInstallmentsUrl();
                    if (!url || typeof fetch !== "function") {
                        self.installmentsDisabled(false);
                        return;
                    }
                    fetch(url, {
                        method: 'POST',
                        cache: 'no-cache',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            form_key: window.checkoutConfig.formKey,
                            cc_type: self.mapCardType(self.creditCardType())
                        })
                    }).then(function (response) {
                        self.installments.removeAll();
                        return response.json();
                    }).then(function (json) {
                        if (json && Array.isArray(json)) {
                            json.forEach(function (installment) {
                                self.installments.push(installment);
                                self.hasInstallments(true);
                            });
                            if (json.length > 0) {
                                self.creditCardInstallments(json[0].installments);
                            }
                        }
                        self.installmentsDisabled(false);
                    }).catch(function () {
                        self.installmentsDisabled(false);
                    });
                }, 500);
            },

            getPaymentProfiles: function () {
                var paymentProfiles = [];
                var savedCards = window.checkoutConfig && window.checkoutConfig.payment && window.checkoutConfig.payment.vindi_vp_cc && window.checkoutConfig.payment.vindi_vp_cc.saved_cards;
                if (savedCards && Array.isArray(savedCards)) {
                    savedCards.forEach(function (card) {
                        paymentProfiles.push({
                            'value': card.id,
                            'text': card.card_type + ' xxxx-' + card.card_number,
                            'card_type': card.card_type
                        });
                    });
                }
                return paymentProfiles;
            },

            hasPaymentProfiles: function () {
                return this.getPaymentProfiles().length > 0;
            }
        });
    }
);
