define(['uiComponent'], function (Component) {
    'use strict';
    return function (sandbox) {
        if (typeof window.yapayFingerprintLoaded == 'undefined') {
            function loadScript() {
                return new Promise((resolve) => {
                    let fpOptions = {env: 'production'};
                    if (parseInt(sandbox) === 1) {
                        fpOptions.env = 'sandbox';
                    }
                    window?.yapay?.FingerPrint(fpOptions);
                    //Wait 1 sec because it was blocking checkout page
                    setTimeout(() => {
                        resolve();
                    }, 1000);
                });
            }
            loadScript().then(() => {
                window.yapayFingerprintLoaded = true;
            });
        }
    };
});
