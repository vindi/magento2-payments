var config = {
    paths: {
        'vindi-cc-form': 'Vindi_VP/js/credit-card/card',
        'vindi-cc-mask': 'Vindi_VP/js/credit-card/mask',
    },
    shim: {
        'vindi-cc-mask': {}
    },
     map: {
         "*": {
             "vindi_vp/oneclickbuy": "Vindi_VP/js/product/oneclickbuy"
         }
     }
};
