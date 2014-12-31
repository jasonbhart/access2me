var UserSenders = function() {

    var data;

    var service = {
        getEntryById: function (id) {
            var a = _.find(data.entries, function(entry) {
                return entry.id == id;
            });
            return a;
        },

        checkSenderUrl: function() {
            return '/ui/user_senders_xhr.php?action=check-sender';
        },

        save: function(entry) {
            return $.post('/ui/user_senders_xhr.php?action=save',
                entry,
                null,
                'json'
            );
        },

        delete: function($id) {
            return $.post('/ui/user_senders_xhr.php?action=delete',
                {id: $id},
                null,
                'json'
            );
        },
        
        toViewModel: function(entry) {
            return {
                'id': entry.id || 0,
                'sender': entry.sender,
                'type': data.types[entry.type],
                'access': data.accessTypes[entry.access]
            }
        }
    };

    var editForm = function() {
        var callbacks = {};
        var $form = $('#form-entry-edit');

        function hideForm() {
            $form.detach();
        }

        function closeForm() {
            if (typeof(callbacks.close) === 'function') {
                callbacks.close();
            }

            callbacks = {};
            hideForm();
        }

        var control = {
            getData: function() {
                var entry = {
                    'id': $form.find('.entry-id').val(),
                    'sender': $form.find('.entry-sender').val(),
                    'type': $form.find('.entry-type').val(),
                    'access': $form.find('.entry-access').val()
                };

                return entry;
            },
            setData: function(entry) {
                $form.find('.entry-id').val(entry.id);
                $form.find('.entry-sender').val(entry.sender);
                $form.find('.entry-type').val(entry.type);
                $form.find('.entry-access').val(entry.access);
            },
            hide: hideForm,
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
            },
            cancel: function() {
                if (typeof(callbacks.cancel) === 'function') {
                    callbacks.cancel();
                }
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
                'entry-sender': {
                    required: true,
                    minlength: 1,
/*
 * todo:
                    remote: {
                        url: service.checkSenderUrl,
                        data: {
                            sender:,
                            type: 
                        }
                    }
*/
                },
                'entry-type': {
                    required: true
                },
                'entry-access': {
                    required: true,
                    
                }
            },
            messages: {
                'entry-sender': {
                    required: 'Please enter sender',
                    remote: 'Invalid sender'
                },
                'entry-type': {
                    required: 'Please select sender\'s type'
                },
                'entry-access': {
                    required: 'Please select sender\'s access'
                }
            }
        });

        // handlers
        $form.find('.form-cancel').click(control.cancel);

        $form.detach().show();

        return control;
    }();
    
    var SendersView = function() {

        function onEntryEdit() {
            var $container = $($(this).parents('tr')[0]);

            editForm.close();

            // tweak container
            var $placeHolder = $container.find('td:eq(1)');

            // remove unneeded columns
            $container.find('td:eq(2), td:eq(3)').remove();

            // enlarge placeholder
            $placeHolder.attr('colspan', 3);
            $placeHolder.empty();

            // show edit form
            var entryId = $container.data('id');
            var entry = service.getEntryById(entryId);

            function renderEntry(entry) {
                // render new entry content
                var template = $.templates('#entry-content-template');
                var data = service.toViewModel(entry);
                var html = template.render(data);
                
                // need to hide form here, because next line will remove it
                editForm.hide();
                $placeHolder.replaceWith(html);
            }

            editForm.setData(entry);
            editForm.open(
                $placeHolder,
                function() {    // save
                    var data = editForm.getData();
                    service.save(data).done(function (response) {
                        if (!response || response.status == 'error') {
                            alert('Can\'t save entry: ' + response.message);
                            return;
                        }

                        // copy values back to entries
                        entry.sender = data.sender;
                        entry.type = data.type;
                        entry.access = data.access;

                        renderEntry(entry);
                    });
                },
                function() {    // cancel
                    renderEntry(entry);
                },
                function() {    // close
                    renderEntry(entry);
                }
            );
        }

        function onEntryDelete() {
            if (!confirm('Are you sure you want to delete entry ?')) {
                return;
            }

            // get filter id;
            var $placeHolder = $($(this).parents('tr')[0]);
            var id = $placeHolder.data('id');
            service.delete(id).done(function(response) {
                if (!response || response.status == 'error') {
                    alert('Can\'t delete entry');
                    return;
                }

                _.remove(data.entries, function(e) {
                    return e.id == id;
                });
                
                editForm.close();
                $placeHolder.remove();
            });
        }

        var controller = {
            render: function(data) {
                var records = [];
                // prepare template data
                for (var i = 0; i < data.entries.length; i++) {
                    records.push(service.toViewModel(data.entries[i]));
                }

                var template = $.templates('#entry-template');
                var $html = $(template.render(records));
                // bind event handlers
                $html.find('.entry-edit').click(onEntryEdit);
                $html.find('.entry-delete').click(onEntryDelete);
                // show content
                $('#entries-holder').html($html);
            }
        };

        // handler for `Add new filter`
        $('#entry-new').click(function() {
            editForm.close();
            
            editForm.open(
                $(this).parent(),
                function() {    // save
                    var formData = editForm.getData();
                    service.save(formData).done(function (response) {
                        if (!response || response.status == 'error') {
                            alert('Can\'t save entry: ' + response.message);
                            return;
                        }

                        formData.id = response.id;

                        data.entries.unshift(formData);
                        editForm.close();
                        
                        controller.render(data);
                    });
                }
            );
        });

        return controller;
    }();

    return {
        init: function(viewData) {
            data = viewData;
            SendersView.render(data);
        }
    };
}();