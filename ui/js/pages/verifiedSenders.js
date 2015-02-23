var VerifiedSenders = function() {

    var data;

    var service = {
        enroll: function(access, email) {
            var type = 2;   //email
            var access = access == 'whitelist' ? 1 : 2;
            return $.post('/ui/user_senders_xhr.php?action=save',
                {
                    'sender': email,
                    'type': type,
                    'access': access
                },
                null,
                'json'
            );
        },

        toViewModel: function(entry) {
            return entry;
        }
    };

    var SendersView = function() {
        function onEntryEnroll(access) {
            var $container = $($(this).parents('tr')[0]);
            var email = $container.data('email');

            return service.enroll(access, email).done(function (response) {
                if (!response || response.status == 'error') {
                    alert('Can\'t save entry: ' + response.message);
                    return;
                }

                _.remove(data.entries, function(e) {
                    return e == email;
                });
                
                $('#alert-content').html(' <strong>'+ email +'</strong> is added to ' + '<strong>' + access + '</strong>');
                $('#alert-div').show().delay(5000).fadeOut();
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
                $html.find('.entry-whitelist').click($.proxy(onEntryEnroll, null, 'whitelist'));
                $html.find('.entry-blacklist').click($.proxy(onEntryEnroll, null, 'blacklist'));
                // show content
                $('#entries-holder').html($html);
            }
        };

        return controller;
    }();

    return {
        init: function(viewData) {
            data = viewData;
            SendersView.render(data);
        }
    };
}();