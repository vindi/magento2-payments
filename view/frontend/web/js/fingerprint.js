define([], function () {
    'use strict';
    return function (sandbox) {
        if (typeof window.yapayFingerprintLoaded == 'undefined') {
            async function loadScript() {
                let fpOptions = {env: 'production'};
                if (parseInt(sandbox) === 1) {
                    fpOptions.env = 'sandbox';
                }
                await window?.yapay?.FingerPrint(fpOptions);
                window.yapayFingerprintLoaded = true;
            }
            loadScript();
        }
    };
});
