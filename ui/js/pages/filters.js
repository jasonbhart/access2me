var Filters = function() {

    return {
        init: function() {
            $('#form-filter-edit').validate({
                errorClass: 'help-block animation-slideUp', // You can change the animation class for a different entrance animation - check animations page
                errorElement: 'div',
                errorPlacement: function(error, e) {
                    e.parents('.form-group').append(error);
                },
                highlight: function(e) {
                    $(e).closest('.form-group').removeClass('has-success has-error').addClass('has-error');
                    $(e).closest('.help-block').remove();
                },
                success: function(e) {
                    if (e.closest('.form-group').find('.help-block').length === 2) {
                        e.closest('.help-block').remove();
                    } else {
                        e.closest('.form-group').removeClass('has-success has-error');
                        e.closest('.help-block').remove();
                    }
                },
                submitHandler: function (e) {
                    var $form = $(e);
                    var record = {
                        'field-name': $form.find('#field-name').val(),
                        'filter-type': $form.find('#filter-type').val(),
                        'value': $form.find('#value').val()
                    };
                    $.post('/ui/filters_xhr.php?action=addfilter',
                        record,
                        function(response) {
                            console.log(response);
                        },
                        'json'
                    );
                },
                rules: {
                    'field-name': {
                        required: true
                    },
                    'filter-type': {
                        required: true
                    },
                    'value': {
                        required: true,
                        minlength: 1
                    }
                },
                messages: {
                    'field-name': {
                        required: 'Please select field'
                    },
                    'filter-type': {
                        required: 'Please select '
                    },
                    'value': {
                        required: 'Please enter value'
                    }
                }
            });
        }
    };
}();