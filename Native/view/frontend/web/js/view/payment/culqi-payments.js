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
                type: 'culqi_pay',
                component: 'Culqi_Native/js/view/payment/method-renderer/culqi-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);