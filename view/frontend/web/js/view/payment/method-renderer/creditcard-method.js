
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery'
    ],
    function (
        Component,
        $
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'IoPay_Core/payment/creditcard'
            },

            getCode: function () {
                return 'iopay_creditcard';
            },

            getData: function () {
                // data to Post in backend
                var dataObj = {
                    'method': this.item.method,
                    'additional_data': {
                        'method': this.getCode(),
                        'credit_card_number': document.querySelector("input[name='payment[credit_card_number]']").value,
                        'credit_card_holder': document.querySelector("input[name='payment[credit_card_holder]']").value,
                        'cc_exp_month': document.getElementById("creditCardExpirationMonth").selectedOptions[0].value,
                        'cc_exp_year': document.getElementById("creditCardExpirationYear").selectedOptions[0].value,
                        'cc_cid': document.querySelector("input[name='payment[cc_cid]']").value,
                        'credit_card_document': document.querySelector("input[name='payment[credit_card_document]']").value,
                        'cc_installments': document.getElementById("creditCardInstallments").selectedOptions[0].value,
                        'credit_card_token': document.querySelector("input[name='payment[credit_card_token]']").value,
                    }
                };

                return dataObj;
            },

            /** Returns send check to info */
            getMailingAddress: function() {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },

            getIoPayCcMonthsValues: function () {
                var months = [
                    '01',
                    '02',
                    '03',
                    '04',
                    '05',
                    '06',
                    '07',
                    '08',
                    '09',
                    '10',
                    '11',
                    '12',
                ];
                return _.map(months, function (value, key) {
                    return {
                        value: key + 1,
                        month: value,
                    };
                });
            },

            getIoPayCcYearsValues: function () {
                var thisYear = new Date().getFullYear();
                var maxYear = thisYear + 20;
                var years = [];
                var i = thisYear;

                for (i = thisYear; i < maxYear; i++) {
                    years.push(i);
                }

                return _.map(years, function (value, key) {
                    return {
                        value: value,
                        year: value,
                    };
                });
            },
            getIoPayInstallments: function () {
                /*
                var installs = [];
                var totalInstallments = window.checkoutConfig.payment[this.getCode()]['installments'];

                for (var i = 1; i <= totalInstallments; i++) {
                    var parcel = i + 'x';
                    installs.push(parcel);
                }

                return _.map(installs, function (value, key) {
                    return {
                        value: value,
                        installment: value,
                    };
                }); */

                var installments = window.checkoutConfig.payment[this.getCode()]['installments'];
                return _.map(installments, function (value, key) {
                    return {
                        value: key,
                        installment: value,
                    };
                });
            },
            getCvvImageHtml: function () {
                var cvvImgUrl = window.checkoutConfig.payment[this.getCode()]['cvvImage'];
                return '<img src="' + cvvImgUrl + '" alt="Cvv image" title="Cvv image" />';
            },

            getEnvironment: function () {
                var environment = window.checkoutConfig.payment[this.getCode()]['environment'];
                return environment;
            },

            getCardToken: function () {
                var cardToken = window.checkoutConfig.payment[this.getCode()]['cardToken'];
                return cardToken;
            },

            validate: function () {
                var $form = $('#' + this.getCode() + '-form');
                //this.getHash();
                return $form.validation() && $form.validation('isValid');
            },
            /*
            placeOrder: function () {

            } */
        });
    }
);
