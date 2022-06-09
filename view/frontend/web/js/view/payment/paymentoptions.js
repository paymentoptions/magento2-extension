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
                type: 'paymentoptions',
                component: 'Agtech_Paymentoptions/js/view/payment/method-renderer/paymentoptions-method'
            }
        );
        return Component.extend({});
    }
);