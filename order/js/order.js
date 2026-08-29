$(document).ready(function() {
    /*
     * 登録した住所から選ぶ
     */
    $('#address_select').on('change', function() {
        var option = $(this).find('option:selected');
        if (!option.val()) {
            return;
        }

        $('input[name=name_01]').val(option.data('name_01'));
        $('input[name=name_02]').val(option.data('name_02'));
        $('input[name=kana_01]').val(option.data('kana_01'));
        $('input[name=kana_02]').val(option.data('kana_02'));
        $('input[name=zipcode]').val(option.data('zipcode'));
        $('input[name=prefecture]').val(option.data('prefecture'));
        $('input[name=address_01]').val(option.data('address_01'));
        $('input[name=address_02]').val(option.data('address_02'));
        $('input[name=telephone]').val(option.data('telephone'));
    });
});
