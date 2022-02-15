/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'iopay_pix',
                component: 'IoPay_Core/js/view/payment/method-renderer/pix-method'
            }
        );

        rendererList.push(
            {
                type: 'iopay_boleto',
                component: 'IoPay_Core/js/view/payment/method-renderer/boleto-method'
            }
        );

        rendererList.push(
            {
                type: 'iopay_creditcard',
                component: 'IoPay_Core/js/view/payment/method-renderer/creditcard-method'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
