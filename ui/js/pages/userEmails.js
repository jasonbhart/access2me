var UserEmails = function() {

    var init = function(angular, data) {
        'use strict';

        var a2mApp = angular.module('access2me', []);

        // send requests encoded as form data
        a2mApp.config(function ($httpProvider) {
            $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';
            $httpProvider.defaults.transformRequest = function(data) {
                if (data === undefined) {
                    return data;
                }

                return jQuery.param(data);
            }
        });

        a2mApp.factory('userEmailService', ['$http', function($http) {
            return  {
                getRecords: function() {
                    return data.records;
                },
                save: function(record) {
                    return $http.post('/ui/user_emails_xhr.php?action=save', record);
                },
                delete: function(id) {
                    return $http.post('/ui/user_emails_xhr.php?action=delete', {id: id});
                }
            };
        }]);


        a2mApp.controller('userEmailsController', ['$scope', 'userEmailService', function($scope, userEmailService) {
            // formating
            $scope.format = function(record) {
                return record.email;
            }

            // handers
            $scope.addNew = false;

            $scope.create = function(record) {
                userEmailService.save(record).success(function (data) {
                    if (!data.status || data.status != 'success') {
                        App.flashMessages.add(data.message || 'error', 'error');
                        return;
                    }

                    // new value
                    $scope.records.unshift({
                        id: data.id,
                        email: record.email
                    });

                    $scope.addNew = false;
                });
            }

            $scope.update = function(record) {
                return userEmailService.save(record).success(function(data) {
                    if (!data.status || data.status != 'success') {
                        App.flashMessages.add(data.message || 'error', 'error');
                        return;
                    }

                    var existing = _.find($scope.records, function (r) {
                        return r.id == record.id;
                    });
                    existing.email = record.email;

                    existing.editing = false;
                });
            }

            $scope.delete = function(record) {
                if (!confirm('Are you sure you want to delete email ?')) {
                    return;
                }

                // remove
                userEmailService.delete(record.id).success(function(data) {
                    if (!data.status || data.status != 'success') {
                        App.flashMessages.add(data.message || 'error', 'error');
                        return;
                    }
                    _.remove($scope.records, function (r) {
                        return r.id == record.id;
                    });
                });
            };

            $scope.records = userEmailService.getRecords();

        }]);

        a2mApp.directive('a2mUserEmailEdit', [function() {
            return {
                restrict: 'E',
                scope: {
                    visible: "=",
                    cancel: '&onCancel',
                    save: '&onSave',
                    record: '='
                },
                controller: function ($scope) {
                    // reset elements on show
                    var reset = function() {
                        var record = $scope.record || {};
                        $scope.data = $scope.$new(true);
                        $scope.data.id = record.id;
                        $scope.data.email = record.email;
                    };

                    reset();

                    $scope.$watch('visible', function(visible) {
                        if (visible) {
                            reset();
                        }
                    });

                    $scope.getData = function() {
                        return {
                            id: $scope.data.id || 0,
                            email: $scope.data.email || ''
                        };
                    }
                },
                templateUrl: 'js/templates/user-email-edit.html'
            }
        }]);
    }

    return {
        init: init
    };
}();
