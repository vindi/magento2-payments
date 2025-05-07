document.addEventListener("DOMContentLoaded", function () {
    const mask = {
        card(value, cardType) {
            const digits = value.replace(/\D/g, '');
            if (cardType === 'amex') {
                return digits.replace(/^(\d{4})(\d{6})(\d{5}).*/, '$1-$2-$3');
            } else {
                return digits.replace(/(\d{4})(?=\d)/g, '$1-').substr(0, 19);
            }
        },
        expiration(value) {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{2})(\d)/, '$1/$2')
                .replace(/(\/\d{2})\d+?$/, '$1');
        }
    };

    const visaPattern        = /^4/;
    const mastercardPattern  = /^(5[1-5]|2[2-7])/;
    const amexPattern        = /^3[47]/;
    const hipercardPattern   = /^(606282|3841)/;
    const hiperPattern       = /^(6370|637095|637599|637609|637612)/;
    const eloPattern         = /^(4011|4312|4389|4514|4573|4576|5041|5066|5067|5090|6277|6362|6363|6500|6504|6505|6506|6507|6509|6516|6550)/;

    function detectCardType(raw) {
        const cardPatterns = [
            { type: 'visa',       pattern: visaPattern },
            { type: 'mastercard', pattern: mastercardPattern },
            { type: 'amex',       pattern: amexPattern },
            { type: 'hipercard',  pattern: hipercardPattern },
            { type: 'hiper',      pattern: hiperPattern },
            { type: 'elo',        pattern: eloPattern },
        ];
        for (const { type, pattern } of cardPatterns) {
            if (pattern.test(raw)) {
                return type;
            }
        }
        return '';
    }

    function isValidCardNumber(number, cardType) {
        if (['elo', 'hiper'].includes(cardType)) {
            return true;
        }
        let sum = 0;
        let shouldDouble = false;
        for (let i = number.length - 1; i >= 0; i--) {
            let digit = +number[i];
            if (shouldDouble) {
                digit *= 2;
                if (digit > 9) digit -= 9;
            }
            sum += digit;
            shouldDouble = !shouldDouble;
        }
        return sum % 10 === 0;
    }

    function isValidExpiryDate(date) {
        const [m, y] = date.split('/');
        const expiry = new Date(`20${y}`, m - 1, 1);
        return expiry > new Date();
    }

    function isValidCVV(cvv, cardType) {
        return (
            (cardType === 'amex' && cvv.length === 4) ||
            (['visa','mastercard','hipercard','hiper','elo'].includes(cardType) && cvv.length === 3)
        );
    }

    function isValidCardName(name) {
        if (/\d/.test(name)) return false;
        if (/[!@#$%^*()_+\-=[\]{};':"\\|,.<>/?]+/.test(name)) return false;
        return true;
    }

    const cardNumberInput     = document.getElementById('cc_number');
    const cardExpirationInput = document.getElementById('cc_exp_date');
    const cardTypeInputs      = document.querySelectorAll('.card-type-input');
    const form                = document.getElementById('payment-profile-form');

    cardTypeInputs.forEach(input =>
        input.addEventListener('click', e => e.preventDefault())
    );

    cardNumberInput.addEventListener('input', function (e) {
        const raw = e.target.value.replace(/\D/g, '');
        const type = detectCardType(raw);
        if (type) {
            const radio = document.getElementById(type);
            if (radio) radio.checked = true;
        }
        e.target.value = mask.card(raw, type);
    });

    cardExpirationInput.addEventListener('input', function (e) {
        e.target.value = mask.expiration(e.target.value);
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const numberRaw   = cardNumberInput.value.replace(/-/g, '');
        const expRaw      = cardExpirationInput.value;
        const cvvRaw      = document.getElementById('cc_cvv').value;
        const nameRaw     = document.getElementById('cc_name').value.trim();
        const checkedType = document.querySelector('input[name="cc_type"]:checked')?.value || '';

        if (!checkedType) {
            alert('Número do cartão inválido.');
            return false;
        }
        if (!isValidCardNumber(numberRaw, checkedType)) {
            alert('Número do cartão inválido.');
            return false;
        }
        if (!isValidExpiryDate(expRaw)) {
            alert('Data de validade inválida.');
            return false;
        }
        if (!isValidCVV(cvvRaw, checkedType)) {
            alert('CVV inválido.');
            return false;
        }
        if (!isValidCardName(nameRaw)) {
            alert('O nome não pode conter números nem caracteres especiais.');
            return false;
        }

        form.submit();
    });
});
