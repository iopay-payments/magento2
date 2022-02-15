require(
    [
        'uiComponent',
        'jquery',
        'IoPay_Core/js/util/jquery.mask.latest',
        'domReady!'
    ],
    function(Component, $) {
        'use strict';

        /*
        $(".card_document").keydown(function(e){
            if (e.keyCode !== 13 && e.keyCode !== 9) {
                try {
                    $(".card_document").unmask();
                } catch (e) {}

                var tamanho = $(".card_document").val().length;
                if(tamanho < 11){
                    $(".card_document").mask("999.999.999-99");
                } else if(tamanho >= 11){
                    $(".card_document").mask("99.999.999/9999-99");
                }

                // ajustando foco
                var elem = this;
                setTimeout(function(){
                    // mudo a posição do seletor
                    elem.selectionStart = elem.selectionEnd = 10000;
                }, 0);
                // reaplico o valor para mudar o foco
                var currentValue = $(this).val();
                $(this).val('');
                $(this).val(currentValue);
            }
        }); */
        $(".card_document").mask("999.999.999-99");
        $(".card_number").mask("9999 9999 9999 9999");
        $(".cc_cid").mask("9999");

        //Listners
        $(document).on('blur','.card_number', function() {tokenize();});
        $(document).on('blur','.card_holder', function() {tokenize();});
        $(document).on('blur','.cc_cid', function() {tokenize();});
        $(document).on('change', '#creditCardInstallments, .select-year, .select-month', function() {
            if (this.selectedIndex) {
                tokenize();
            }
        });

        function tokenize() {
            var holder_name         = $(".card_holder").val();
            var card_number         = $(".card_number").val();
            var security_code       = $(".cc_cid").val();
            var expiration_year     = $(".select-year").val();
            var expiration_month    = $(".select-month").val();

            var cardToken           = $("#authCardToken").val();
            var environment         = $("#environment").val();

            if (!cardToken || cardToken == null) {
                alert('IoPay: Falha ao obter token do cartão de crédito. Recarregue a página e tente novamente.');
                return;
            }

            if (!isEmpty(holder_name) &&
                !isEmpty(card_number) &&
                !isEmpty(security_code) &&
                !isEmpty(expiration_year) &&
                !isEmpty(expiration_month) &&
                card_number.length == 19
            ) {
                var _data = {
                    "holder_name": holder_name,
                    "card_number": card_number.replace(/\D/g, ''),
                    "security_code": security_code,
                    "expiration_year": expiration_year.replace("20", ""),
                    "expiration_month": expiration_month
                }

                var urlTokenize = 'https://api.iopay.com.br/api/v1/card/tokenize/token';
                if (environment == 'sandbox') {
                    urlTokenize = 'https://sandbox.api.iopay.com.br/api/v1/card/tokenize/token';
                }

                var settings = {
                    "url": urlTokenize,
                    "method": "POST",
                    "timeout": 0,
                    "headers": {
                        "Authorization": "Bearer "+cardToken
                    },
                    "data": _data,
                };

                console.log(settings);
                $.ajax(
                    settings
                ).done(function (response) {
                    var id = response['id'];
                    console.log(response);
                    console.log(id);
                    console.log(response.id);
                    if (id != null || id != '') {
                        document.querySelector("#creditCardToken").value = id;
                    } else {
                        if (response.error) {
                            console.log(response.error);
                            alert(response.error.message);
                        }
                        alert('Não foi possível criptografar os dados do cartão de crédito!');
                    }
                }).fail(function(data, textStatus, xhr) {
                    console.log("code status", data.status);
                    if (data.status == 402) {
                        console.log(data.responseJSON.error)
                        var errorMessage = data.responseJSON.error['message'];
                        alert(errorMessage);
                    } else if (data.status == 401) {
                        console.log(data.responseJSON.error)
                        var errorMessage = data.responseJSON.error['message'];
                        alert(errorMessage);
                    }
                });
            }
        }

        function isEmpty(strValue)
        {
            if (!strValue || strValue.trim() === "" || (strValue.trim()).length === 0) {
                return true;
            }
            return false;
        }
});
