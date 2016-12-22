jQuery(document).ready(function ($) {
    var temp = $('.sample-field, .sample-amount-field').clone();
    $('.sample-field, .sample-amount-field').remove();
    $('#wpbody-content').append(temp);
    //For form background in admin

    $('#background-reset').click(function () {
        $('#background-select-field').attr('src', $('.back-default-img').val());
        $('#background-select-id').val('0');
    });


    $('#background-select').click(function (e) {
        e.preventDefault();
        var file = wp.media({
            title: 'Upload Image',
            multiple: false
        }).open()
        .on('select', function (e) {
            var selected_file = file.state().get('selection').first().toJSON();
            $('#background-select-field').attr('src', selected_file.url);
            $('#background-select-id').val(selected_file.url);
        });
    });

    //For add field in admin
    
    var busy = false;
    $('.add-field-block .add-field').click(function () {
        if (busy)
            return;

        busy = true;

        var customeFields = parseInt($('.last-field').val(), 10) + 1;
        var temp = $('.sample-field').addClass('cloned').clone();
        $('.sample-field.cloned').removeClass('cloned');

        $(this).before(temp);

        setTimeout(function () {
            $('.cloned .field-id').attr({
                'name': $('.settings-name').val() + '[donate_form_fields][custome-field-' + customeFields + '][id]',
                'value' : 'custome-field-' + customeFields
            });
            $('.cloned .field-name').attr({
                'name': $('.settings-name').val() + '[donate_form_fields][custome-field-' + customeFields + '][name]',
                'value' : 'custome-field-' + customeFields
            });
            $('.cloned .field-title').attr('name', $('.settings-name').val() + '[donate_form_fields][custome-field-' + customeFields + '][title]');
            $('.cloned .field-type').attr('name', $('.settings-name').val() + '[donate_form_fields][custome-field-' + customeFields + '][type]');
            $('.cloned .field-active').attr({
                'name': $('.settings-name').val() + '[donate_form_acive_fields][custome-field-' + customeFields + ']',
                'id':  'custome-field-' + customeFields,
            });
            $('.cloned .field-active-label').attr({'for': 'custome-field-' + customeFields});
            $('.cloned .field-required').attr({
                'name': $('.settings-name').val() + '[donate_form_required_fields][custome-field-' + customeFields + ']',
                'id': 'custome-field-' + customeFields + '-req',
            });
            $('.cloned .field-required-label').attr({ 'for': 'custome-field-' + customeFields + '-req'});

            $('.cloned').addClass('custome-field').removeClass('cloned').removeClass('sample-field');
            busy = false;
        },200);
       
        $('.last-field').val(customeFields);
        
    });

    $('.add-field-block').on('click', '.remove-field', function () {
        $(this).parent().remove();
    });


    //For add field in admin
    
    var busy2 = false;
    $('.amount-price-block .add-amount').click(function () {
        if (busy2)
            return;

        busy2 = true;

        var customeAmounts = parseInt($('.last-price').val(), 10) + 1;
        var temp = $('.sample-amount-field').addClass('cloned').clone();
        $('.sample-amount-field.cloned').removeClass('cloned');

        $(this).before(temp);

        setTimeout(function () {
            $('.cloned .amount-field-id').attr({
                'name': $('.settings-name').val() + '[donate_form_amount_field][fixed][donate-amount-' + customeAmounts + '][id]',
                'value': 'donate-amount-' + customeAmounts
            });
            $('.cloned .amount-field-name').attr({
                'name': $('.settings-name').val() + '[donate_form_amount_field][fixed][donate-amount-' + customeAmounts + '][name]',
                'value': 'donate-amount-' + customeAmounts
            });
            $('.cloned .amount-field-type').attr('name', $('.settings-name').val() + '[donate_form_amount_field][fixed][donate-amount-' + customeAmounts + '][type]');
            $('.cloned .amount-field-value').attr({
                'name': $('.settings-name').val() + '[donate_form_amount_field][fixed][donate-amount-' + customeAmounts + '][value]',
                'id': 'donate-amount-' + customeAmounts
            });

            $('.cloned .amount-field-des').attr({
                'name': $('.settings-name').val() + '[donate_form_amount_field][fixed][donate-amount-' + customeAmounts + '][des]',
            });

            $('.cloned .amount-field-symbol').attr({
                'name': $('.settings-name').val() + '[donate_form_amount_field][fixed][donate-amount-' + customeAmounts + '][symbol]',
            });

            $('.cloned .amount-field-show-currency').attr({
                'name': $('.settings-name').val() + '[donate_form_amount_field][fixed][donate-amount-' + customeAmounts + '][show-currency]',
                'id': 'amount-field-show-currency-' + customeAmounts
            });

            $('.cloned .amount-field-show-currency-label').attr({
                'for': 'amount-field-show-currency-' + customeAmounts
            });

            $('.cloned .amount-field-value-label').attr({ 'for': 'donate-amount-' + customeAmounts });

            $('.cloned').addClass('custom-amount-field').removeClass('cloned').removeClass('sample-amount-field');

            $('.last-price').val(customeAmounts);
            busy2 = false;
        }, 200);

    });

    $('.amount-price-block').on('click', '.remove-field', function () {
        $(this).parent().remove();
    });
});