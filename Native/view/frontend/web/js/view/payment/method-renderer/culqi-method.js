define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'culqipay',
        'Magento_Checkout/js/model/quote',
        'Magento_Payment/js/model/credit-card-validation/validator'
    ],
    function (Component, $, culqipay, quote) {
        'use strict';

        Culqi.publicKey = window.checkoutConfig.payment.culqi_pay.publicKey;
        Culqi.init();

        window.culqi = function () {
            if(Culqi.token) {
                $('#culqi_pay_token').val(Culqi.token.id);
                $('#culqi_pay_place_order').click();
            } else {
                $('#culqi_pay_retry_place_order').click();
                if (Culqi.error && Culqi.error.user_message) {
                    alert(Culqi.error.user_message);
                } else {
                    alert('Por favor vuelva intentar.');
                }
            }
        }

        return Component.extend({
            defaults: {
                template: 'Culqi_Native/payment/culqi-form'
            },

            getCode: function() {
                return 'culqi_pay';
            },

            isActive: function() {
                return true;
            },

            validate: function() {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },

            getData: function() {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'token': $('#culqi_pay_token').val(),
                    }
                };
            },

            preparePayment: function()
            {
                $("form[id='culqi-card-form'] :input[id='card[email]']").val(this.getEmail());
                if (this.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    Culqi.createToken();
                    return true;
                }
                return false;
            },

            getEmail: function () {
                if(quote.guestEmail) {
                    return quote.guestEmail;
                } else {
                    return window.checkoutConfig.customerData.email;
                }
            },

            retryPlaceOrder: function()
            {
                this.isPlaceOrderActionAllowed(true);
            }
        });
    }
);
