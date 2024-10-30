define(['uiComponent'], function (Component) {
    'use strict';
    return function (sandbox) {
        if (typeof window.yapayFingerprintLoaded == 'undefined') {
            let fpOptions = {env: 'production'};
            if (parseInt(sandbox) === 1) {
                fpOptions.env = 'sandbox';
            }
            window?.yapay?.FingerPrint(fpOptions);
            window.yapayFingerprintLoaded = true;
        }
    };
});
