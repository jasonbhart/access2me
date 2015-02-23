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

    var EditForm = function(settings) {
        var $form = $('#form-entry-edit').clone();
        $form.attr('id', null);
        $form.appendTo(settings.el);

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
            hide: function() {
                $form.hide();
            },
            show: function() {
                $form.show();
            },
            save: function() {
                var deff = typeof(settings.save) === 'function' ? settings.save() : null;
                $.when(deff).always(function() {
                    control.close();                    
                });
            },
            cancel: function() {
                var deff = typeof(settings.cancel) === 'function' ? settings.cancel() : null;
                $.when(deff).always(function() {
                    control.close();                    
                });
            },
            close: function() {
                var deff = typeof(settings.close) === 'function' ? settings.close() : null;
                $.when(deff).always(function() {
                    control.hide();                    
                });
            },
            destroy: function() {
                $form.remove();
            }
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
        return control;
    };
    
    var SendersView = function() {

        var editForm;

        function onEntryEdit() {
            if (editForm) {
                editForm.close();
                editForm.destroy();
            }

            var $container = $($(this).parents('tr')[0]);

            // prepare container
            var $placeHolder = $container.find('td:eq(1)');

            // remove unneeded columns
            $container.find('td:eq(2), td:eq(3)').remove();

            // enlarge placeholder
            $placeHolder.attr('colspan', 3);
            $placeHolder.empty();

            // prepare form data
            var entryId = $container.data('id');
            var entry = service.getEntryById(entryId);

            function renderEntry(entry) {
                // render entry content
                var template = $.templates('#entry-content-template');
                var data = service.toViewModel(entry);
                var html = template.render(data);
                $placeHolder.replaceWith(html);
            }

            // prepare form
            editForm = new EditForm({
                el: $placeHolder,
                save: function() {
                    var formData = editForm.getData();
                    return service.save(formData).done(function (response) {
                        if (!response || response.status == 'error') {
                            alert('Can\'t save entry: ' + response.message);
                            return;
                        }

                        // copy values back to entries
                        entry.sender = response.sender;
                        entry.type = formData.type;
                        entry.access = formData.access;
                    });
                },
                close: function() {
                    renderEntry(entry);
                }
            });

            // show show edit form
            editForm.setData(entry);
            editForm.show();
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
        $('#ctrl-new-entry').click(function() {
            if (editForm) {
                editForm.close();
                editForm.destroy();
            }

            editForm = new EditForm({
                id: 'form-new-entry',
                el: $(this).parent(),
                save: function() {    // save
                    var formData = editForm.getData();
                    return service.save(formData).done(function (response) {
                        if (!response || response.status == 'error') {
                            alert('Can\'t save entry: ' + response.message);
                            return;
                        }

                        var entry = service.getEntryById(response.id);
                        // entry will be found if user creates record for already existing sender
                        if (entry) {
                            entry.sender = response.sender;
                            entry.type = formData.type;
                            entry.access = formData.access;
                        } else {
                            formData.id = response.id;
                            formData.sender = response.sender;
                            data.entries.unshift(formData);
                        }

                        controller.render(data);
                    });
                },
                close: function() {
                    editForm.destroy();
                }
            });
            
            editForm.show();
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