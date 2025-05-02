document.addEventListener("DOMContentLoaded", function () {
    const mask = {
        card(value, cardType) {
            if (cardType === 'amex') {
                return value
                    .replace(/\D/g, '')
                    .replace(/^(\d{4})(\d{6})(\d{5}).*/, '$1-$2-$3');
            } else {
                return value
                    .replace(/\D/g, '')
                    .replace(/(\d{4})(?=\d)/g, '$1-')
                    .substr(0, 19);
            }
        },
        expiration(value) {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{2})(\d)/, '$1/$2')
                .replace(/(\/\d{2})\d+?$/, '$1');
        }
    };

    const cardNumberInput     = document.getElementById('cc_number');
    const cardExpirationInput = document.getElementById('cc_exp_date');
    const cardTypeInputs      = document.querySelectorAll('.card-type-input');
    const form                = document.getElementById('payment-profile-form');

    cardTypeInputs.forEach(input => {
        input.addEventListener('click', function(e) {
            e.preventDefault();
        });
    });

    cardNumberInput.addEventListener('input', function (e) {
        e.target.value = mask.card(e.target.value);
    });

    cardNumberInput.addEventListener('input', function (e) {
        const value = e.target.value.replace(/\D/g, '');
        let cardType = '';

        const visaPattern = /^4/;
        const mastercardPattern = /^(5[1-5]|2[2-7])/;
        const amexPattern = /^3[47]/;
        const hipercardPattern = /^(606282|3841)/;
        const hiperPattern = /^(6370|637095|637599|637609|637612)/;
        const eloPattern = /^(4011|4312|4389|4514|4573|4576|5041|5066|5067|5090|6277|6362|6363|6500|6504|6505|6506|6507|6509|6516|6550)/;

        if (visaPattern.test(value)) {
            document.getElementById('visa').checked = true;
            cardType = 'visa';
        } else if (mastercardPattern.test(value)) {
            document.getElementById('mastercard').checked = true;
            cardType = 'mastercard';
        } else if (amexPattern.test(value)) {
            document.getElementById('amex').checked = true;
            cardType = 'amex';
        } else if (hipercardPattern.test(value)) {
            document.getElementById('hipercard').checked = true;
            cardType = 'hipercard';
        } else if (hiperPattern.test(value)) {
            document.getElementById('hiper').checked = true;
            cardType = 'hiper';
        } else if (eloPattern.test(value)) {
            document.getElementById('elo').checked = true;
            cardType = 'elo';
        }

        e.target.value = mask.card(value, cardType);
    });

    cardExpirationInput.addEventListener('input', function (e) {
        e.target.value = mask.expiration(e.target.value);
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const cardNumber       = document.getElementById('cc_number').value.replace(/-/g, '');
        const cardExpDate      = document.getElementById('cc_exp_date').value;
        const cardCVV          = document.getElementById('cc_cvv').value;
        const cardNameInput    = document.getElementById('cc_name');
        const cardTypeSelected = document.querySelector('input[name="cc_type"]:checked');

        if (!cardTypeSelected) {
            alert('Número do cartão inválido.');
            return false;
        }

        if (!isValidCardNumber(cardNumber, cardTypeSelected.value)) {
            alert('Número do cartão inválido.');
            return false;
        }

        if (!isValidExpiryDate(cardExpDate)) {
            alert('Data de validade inválida.');
            return false;
        }

        if (!isValidCVV(cardCVV, cardTypeSelected.value)) {
            alert('CVV inválido.');
            return false;
        }

        if (!isValidCardName(cardNameInput.value)) {
            alert("O nome do cartão não pode conter números ou caracteres especiais.");
            return false;
        }

        form.submit();
    });

    function isValidCardNumber(number, cardType) {
        if (cardType === 'elo' || cardType === 'hiper') {
            return true;
        }

        let sum = 0;
        let shouldDouble = false;
        for (let i = number.length - 1; i >= 0; i--) {
            let digit = parseInt(number.charAt(i));

            if (shouldDouble) {
                digit *= 2;
                if (digit > 9) {
                    digit -= 9;
                }
            }

            sum += digit;
            shouldDouble = !shouldDouble;
        }

        return sum % 10 === 0;
    }

    function isValidExpiryDate(date) {
        const [month, year] = date.split('/');
        const expiryDate = new Date(`20${year}`, month - 1);
        const currentDate = new Date();

        return expiryDate > currentDate;
    }

    function isValidCVV(cvv, cardType) {
        const cvvLength = cvv.length;
        return (cardType === 'amex' && cvvLength === 4) || (['visa', 'mastercard', 'hipercard', 'hiper', 'elo'].includes(cardType) && cvvLength === 3);
    }

    function isValidCardName(cardName) {
        const hasNumber = /\d/.test(cardName);
        if (hasNumber) {
            return false;
        }

        const charactersSpecialProhibited = /[!@#$%^*()_+\-=[\]{};':"\\|,.<>/?]+/;
        if (charactersSpecialProhibited.test(cardName)) {
            return false;
        }

        return true;
    }
});
