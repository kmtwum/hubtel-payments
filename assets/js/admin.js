jQuery(function ($) {
    'use strict'

    var wc_hubtel_admin = {
        /**
         * Initialize.
         */
        init: function () {
            $('#woocommerce_hubtel_user_type').change(function () {
                const posNumber = $('#woocommerce_hubtel_merchant_account_number').parents( 'tr' ).eq( 0 )
                const mobileNumber = $('#woocommerce_hubtel_mobile_number').parents( 'tr' ).eq( 0 )
                if ($(this).val() === 'merchant') {
                    posNumber.show()
                    mobileNumber.hide()
                } else {
                    posNumber.hide()
                    mobileNumber.show()
                }
            })
            $( '#woocommerce_hubtel_user_type' ).change();

            fetch('https://excelliumgh.com/cdn/plugins/woo-hubtel/verify', {
                method: 'POST',
                body: JSON.stringify({
                    "site": window.location.hostname,
                    "code": $('#woocommerce_hubtel_activation_code').val()
                })
            }).then(r => r.json().then(response => {
                const expiry = response.validTill;
                let parent = $('#woocommerce_hubtel_activation_code').parent()
                let p = parent.find('.description')
                if (expiry) {
                    let dit = new Date(expiry).getTime() - Date.now();
                    let did = Math.round(dit / (1000 * 3600 * 24));
                    p.html(p.text() + `<br> Subscription Status: <span style="padding: 1px 5px 3px 5px; border-radius: 5px; color:white; background-color: ${did > 1 ? '#00c731' : '#bb0000'}">${did > 1 ? 'Active' : 'Expired'}</span>`)
                } else {
                    p.html(p.text() + `<br><span style="padding: 1px 5px 3px 5px; border-radius: 5px; color:white; background-color: #6e6e6e">Invalid / Empty Code</span>`)
                }
            }));
        }
    }

    wc_hubtel_admin.init();
})
