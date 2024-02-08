define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (
    Component,
    rendererList
) {
    'use strict';

    rendererList.push({
        type: 'vindi_vp_bankslip',
        component: 'Vindi_VP/js/view/payment/method-renderer/bankslip'
    });

    rendererList.push({
        type: 'vindi_vp_bankslippix',
        component: 'Vindi_VP/js/view/payment/method-renderer/bankslippix'
    });

    rendererList.push({
        type: 'vindi_vp_pix',
        component: 'Vindi_VP/js/view/payment/method-renderer/pix'
    });

    rendererList.push({
        type: 'vindi_vp_cc',
        component: 'Vindi_VP/js/view/payment/method-renderer/cc'
    });

    return Component.extend({});
});
