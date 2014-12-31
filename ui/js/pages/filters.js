var Filters = function() {

    var data;

    var service = {
        getFilterById: function (id) {
            var a = _.find(data.filters, function(filter) {
                return filter.id == id;
            });
            return a;
        },

        save: function(filter, callback) {
            return $.post('/ui/filters_xhr.php?action=save',
                filter,
                callback,
                'json'
            );
        },

        delete: function($id, callback) {
            return $.post('/ui/filters_xhr.php?action=delete',
                {id: $id},
                callback,
                'json'
            );
        },
        
        toViewModel: function(filter) {
            return {
                'id': filter.id || 0,
                'field': data.fields[filter.field],
                'condition': data.conditions[filter.type],
                'value': filter.value
            }
        }
    };

    var editForm = function() {
        var callbacks = {};
        var $form = $('#form-filter-edit');

        function closeForm() {
            if (typeof(callbacks.close) === 'function') {
                callbacks.close();
            }

            callbacks = {};
            $form.detach();
        }

        var control = {
            getData: function() {
                var record = {
                    'id': $form.find('.filter-id').val(),
                    'field': $form.find('.field-name').val(),
                    'type': $form.find('.filter-type').val(),
                    'value': $form.find('.filter-value').val()
                };

                return record;
            },
            setData: function(filter) {
                $form.find('.filter-id').val(filter.id);
                $form.find('.field-name').val(filter.field);
                $form.find('.filter-type').val(filter.type);
                $form.find('.filter-value').val(filter.value);
            },
            open: function(el, saveCallback, cancelCallback, closeCallback) {
                callbacks.save = saveCallback;
                callbacks.cancel = cancelCallback;
                callbacks.close = closeCallback;
                // todo: do not show button if handler is not passed
                $form.appendTo(el);
            },
            save: function() {
                if (typeof(callbacks.save) === 'function') {
                    callbacks.save();
                }
                closeForm();
            },
            cancel: function() {
                if (typeof(callbacks.cancel) === 'function') {
                    callbacks.cancel();
                }
                closeForm();
            },
            close: closeForm
        };

        // validation
        $form.validate({
            errorClass: 'help-block animation-slideUp',
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
                control.save();
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
                    required: 'Please select field to compare with'
                },
                'filter-type': {
                    required: 'Please select condition'
                },
                'value': {
                    required: 'Please enter value'
                }
            }
        });
        
        // handlers
        $form.find('.form-cancel').click(control.cancel);

        $form.detach().show();

        return control;
    }();
    
    var FiltersView = function() {

        function onFilterEdit() {
            var $placeHolder = $($(this).parents('td')[0]).prev();

            editForm.close();

            // hide filter content
            var $filterContent = $placeHolder.find('.filter-content');
            $filterContent.hide();

            // show edit form
            var filterId = $placeHolder.find('.filter-content').data('id');
            var filter = service.getFilterById(filterId);
            
            editForm.setData(filter);
            editForm.open(
                $placeHolder,
                function() {    // save
                    var data = editForm.getData();
                    service.save(data, function (response) {
                        if (!response || response.status == 'error') {
                            alert('Can\'t save filter');
                            return;
                        }

                        // render new filter content
                        var template = $.templates('#filter-content-template');

                        // copy values back to filters
                        filter.field = data.field;
                        filter.type = data.type;
                        filter.value = data.value;

                        data = service.toViewModel(data);
                        var html = template.render(data);
                        $filterContent.replaceWith(html);
                        editForm.close();
                    });
                },
                null,           // cancel
                function() {    // close
                    $placeHolder.find('.filter-content').show();
                }
            );
        }

        function onFilterDelete() {
            if (!confirm('Are you sure you want to delete filter ?')) {
                return;
            }

            // get filter id;
            var $placeHolder = $($(this).parents('td')[0]).prev();
            var filterId = $placeHolder.find('.filter-content').data('id');
            service.delete(filterId, function(response) {
                if (!response || response.status == 'error') {
                    alert('Can\'t delete filter');
                    return;
                }

                _.remove(data.filters, function(f) {
                    return f.id == filterId;
                });
                
                editForm.close();
                $placeHolder.parent().remove();
            });
        }

        var controller = {
            render: function(data) {
                var records = [];
                // prepare template data
                for (var i = 0; i < data.filters.length; i++) {
                    records.push(service.toViewModel(data.filters[i]));
                }

                var template = $.templates('#filter-template');
                var $html = $(template.render(records));
                // bind event handlers
                $html.find('.filter-edit').click(onFilterEdit);
                $html.find('.filter-delete').click(onFilterDelete);
                // show content
                $('#filters-holder').html($html);
            }
        };

        // handler for `Add new filter`
        $('#filter-new').click(function() {
            editForm.close();
            
            editForm.open(
                $(this).parent(),
                function() {    // save
                    var formData = editForm.getData();
                    service.save(formData, function (response) {
                        if (!response || response.status == 'error') {
                            alert('Can\'t save filter');
                            return;
                        }

                        formData.id = response.id;

                        data.filters.unshift(formData);
                        editForm.close();
                        
                        controller.render(data);
                    });
                }
            );
        });

        return controller;
    }();

    return {
        init: function(filterData) {
            data = filterData;
            FiltersView.render(data);
        }
    };
}();