$(document).ready(function() {

    /*
     * 種類
     */
    $('select[name=kind]').on('change', function() {
        if ($(this).val() == 'digital') {
            $('.for-digital').show();
        } else {
            $('.for-digital').hide();
        }
    });
    if ($('select[name=kind]').val() == 'digital') {
        $('.for-digital').show();
    } else {
        $('.for-digital').hide();
    }

    /*
     * 提供方法
     */
    $('select[name=provide]').on('change', function() {
        if ($(this).val() == 'delivery') {
            $('.for-delivery').show();
        } else {
            $('.for-delivery').hide();
        }
        if ($(this).val() == 'direct') {
            $('.for-email, .for-not_direct').hide();
        } else {
            $('.for-email, .for-not_direct').show();
        }
    });
    if ($('select[name=provide]').val() == 'delivery') {
        $('.for-delivery').show();
    } else {
        $('.for-delivery').hide();
    }
    if ($('select[name=provide]').val() == 'direct') {
        $('.for-email, .for-not_direct').hide();
    } else {
        $('.for-email, .for-not_direct').show();
    }

    /*
     * 配送日
     */
    $('input[name=shipping_date]').datetimepicker({
        format: 'Y-m-d',
        timepicker: false
    });

    /*
     * 商品行 - 直接入力
     */
    function toggleOrderRecordItemRow(row) {
        if (row.find('select[name="spec_id[]"]').val() == '__custom__') {
            row.find('.order_record_item_name, .order_record_item_price').show();
        } else {
            row.find('.order_record_item_name, .order_record_item_price').hide();
        }
    }
    $(document).on('change', '#order_record_item_rows select[name="spec_id[]"]', function() {
        toggleOrderRecordItemRow($(this).closest('.order_record_item_row'));
    });
    $('#order_record_item_rows .order_record_item_row').each(function() {
        toggleOrderRecordItemRow($(this));
    });

    /*
     * 商品行の追加
     */
    $('#order_record_item_add').on('click', function() {
        var row = $('#order_record_item_rows .order_record_item_row').first().clone();
        row.find('select').val('');
        row.find('input[name="quantity[]"]').val('1');
        row.find('.order_record_item_name, .order_record_item_price').val('').hide();
        $('#order_record_item_rows').append(row);
    });

});
