
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'IoPay_Core/payment/boleto'
            },

            getCode: function () {
                return 'iopay_boleto';
            },

            /** Returns send check to info */
            getMailingAddress: function() {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },

            /** Returns instructions */
            getInstructions: function() {
                return window.checkoutConfig.payment[this.getCode()]['instructions'];
            },

            getLogoUrl: function () {
                return window.checkoutConfig.payment[this.getCode()]['logo_iopay'];
            }
        });
    }
);
