define([], function () {
    'use strict';
    return async function (sandbox) {
        if (typeof window.yapayFingerprintLoaded == 'undefined') {
            var fpOptions = {env: 'production'};
            if (parseInt(sandbox) === 1) {
                fpOptions.env = 'sandbox';
            }
            await window?.yapay?.FingerPrint(fpOptions);
            window.yapayFingerprintLoaded = true;
        }
    };
});
