/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    'jquery',
    'mage/translate',
    'underscore',
    'mage/url',
    'Magento_Ui/js/model/messageList'
], function ($, $t, _, url, messageList) {
    'use strict';

    return function () {
        let cardSelector = $("#vp-card-selector");
        let productId = $("#product-id").text();
        let submitButton = $("#vp-payment-oneclickbuy");
        let cvvField = $("#vp-cvv");
        let qtyField = $("#qty");

        submitButton.on("click", function (event) {
            event.preventDefault();
            event.stopPropagation();
            
            if (!cardSelector.val()) {
                messageList.addErrorMessage({ 
                    message: $t('Por favor, selecione um cartão de crédito.') 
                });
                return;
            }
            
            if (!cvvField.val() || cvvField.val().length < 3) {
                messageList.addErrorMessage({ 
                    message: $t('Por favor, digite um CVV válido.') 
                });
                cvvField.focus();
                return;
            }
            
            if (!productId) {
                messageList.addErrorMessage({ 
                    message: $t('Produto não encontrado. Recarregue a página e tente novamente.') 
                });
                return;
            }

            let qty = parseInt(qtyField.val()) || 1;
            if (qty < 1) {
                messageList.addErrorMessage({ 
                    message: $t('Por favor, digite uma quantidade válida.') 
                });
                qtyField.focus();
                return;
            }

            submitButton.prop('disabled', true).text($t('Processando...'));
            
            let param = {
                profile: cardSelector.val(),
                productId: productId,
                cvv: cvvField.val(),
                qty: qty
            };

            $.ajax({
                showLoader: true,
                url: BASE_URL + 'vindi_vp/oneclickbuy/transaction',
                data: param,
                type: "POST",
                dataType: 'json',
                timeout: 30000
            }).done(function (response) {
                if (response.success) {
                    let redirectUrl = response.redirect_url || (BASE_URL + 'checkout/onepage/success');
                    
                    messageList.clear();
                    
                    messageList.addSuccessMessage({ 
                        message: $t('Compra realizada com sucesso! Redirecionando...') 
                    });
                    
                    setTimeout(function() {
                        window.location.href = redirectUrl;
                    }, 500);
                    
                } else {
                    submitButton.prop('disabled', false).text($t('Finalizar Compra'));
                    
                    let errorMessage = response.message || $t('Não foi possível concluir a compra. Tente novamente.');
                    messageList.addErrorMessage({ message: errorMessage });
                    
                    cvvField.val('');
                }
            }).fail(function (jqXHR, textStatus, errorThrown) {
                submitButton.prop('disabled', false).text($t('Finalizar Compra'));
                
                let errorMessage;
                
                if (textStatus === 'timeout') {
                    errorMessage = $t('A requisição demorou muito para responder. Tente novamente.');
                } else if (jqXHR.status === 0) {
                    errorMessage = $t('Erro de conexão. Verifique sua internet e tente novamente.');
                } else if (jqXHR.status >= 500) {
                    errorMessage = $t('Erro interno do servidor. Tente novamente em alguns minutos.');
                } else {
                    errorMessage = $t('Erro de comunicação com o servidor. Tente novamente.');
                }
                
                messageList.addErrorMessage({ message: errorMessage });
                
                cvvField.val('');
                
                if (typeof console !== 'undefined' && console.error) {
                    console.error('One Click Buy Error:', {
                        status: jqXHR.status,
                        statusText: textStatus,
                        error: errorThrown,
                        responseText: jqXHR.responseText
                    });
                }
            });
        });

        cvvField.on('input', function() {
            let cvv = $(this).val();
            cvv = cvv.replace(/\D/g, '');
            cvv = cvv.substring(0, 4);
            $(this).val(cvv);
        });

        // Enter key no CVV submete o formulário
        cvvField.on('keypress', function(e) {
            if (e.which === 13) { 
                submitButton.trigger('click');
            }
        });

        return $.mage;
    }
});