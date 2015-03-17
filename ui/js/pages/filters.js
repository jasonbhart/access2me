var Filters = function() {

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

        a2mApp.factory('filterService', ['$http', function($http) {
            var metadata = data.metadata;
            return  {
                metadata: metadata,
                getFilters: function() {
                    return data.filters;
                },
                getFilterMeta: function(filter) {
                    filter = filter || {};

                    var info = { id: filter.id, value: filter.value };

                    // common, linkedin
                    var types = metadata.types;
                    info.type = _.find(types, function(type) {
                        return type.id == filter.type;
                    });

                    info.type = info.type || types[0];
                    var properties = info.type.properties;

                    info.property = _.find(properties, function(property) {
                        return property.id == filter.property;
                    });

                    info.property = info.property || properties[0];

                    var methods = metadata.compTypes[info.property.type];

                    info.method = _.find(methods, function(method) {
                        return method.id == filter.method;
                    });

                    info.method = info.method || methods[0];

                    return info;
                },
                save: function(filter) {
                    return $http.post('/ui/filters_xhr.php?action=save', filter);
                },
                delete: function(id) {
                    return $http.post('/ui/filters_xhr.php?action=delete', {id: id});
                }
            };
        }]);


        a2mApp.controller('filtersController', ['$scope', 'filterService', function($scope, filterService) {

            // filter formating
            $scope.formatFilter = function(filter) {
                var metadata = filterService.metadata;

                // common, linkedin
                var type = _.find(metadata.types, function(type) {
                    return type.id == filter.type;
                });

                // lastName, age
                var property = _.find(type.properties, function(property) {
                    return property.id == filter.property;
                });

                // lesser, greater
                var method = _.find(metadata.compTypes[property.type], function(method) {
                    return method.id == filter.method;
                });

                return type.name + ': ' + property.name + ' ' + method.description + ' ' + filter.value;
            }

            // handers
            $scope.addNew = false;

            $scope.create = function(filter) {
                console.log(filter);
                filterService.save(filter).success(function(data) {
                    if (!data.status || data.status != 'success') {
                        App.flashMessages.add(data.message || 'error', 'error');
                        return;
                    }

                    // new value
                    $scope.filters.unshift({
                        id: data.id,
                        type: filter.type,
                        property: filter.property,
                        method: filter.method,
                        value: filter.value
                    });

                    $scope.addNew = false;
                });
            }

            $scope.update = function(filter) {
                console.log(filter);
                return filterService.save(filter).success(function(data) {
                    if (!data.status || data.status != 'success') {
                        App.flashMessages.add(data.message || 'error', 'error');
                        return;
                    }

                    var existing = _.find($scope.filters, function (f) {
                        return f.id == filter.id;
                    });
                    existing.type = filter.type;
                    existing.property = filter.property;
                    existing.method = filter.method;
                    existing.value = filter.value;

                    existing.editing = false;
                });
            }

            $scope.delete = function(filter) {
                if (!confirm('Are you sure you want to delete filter ?')) {
                    return;
                }

                // remove filter
                filterService.delete(filter.id).success(function(data) {
                    if (!data.status || data.status != 'success') {
                        App.flashMessages.add(data.message || 'error', 'error');
                        return;
                    }
                    _.remove($scope.filters, function (f) {
                        return f.id == filter.id;
                    });
                });
            };

            $scope.filters = filterService.getFilters();

        }]);

        a2mApp.directive('a2mFilterEdit', ['filterService', function(filterService) {
            return {
                restrict: 'E',
                scope: {
                    cancel: '&onCancel',
                    save: '&onSave',
                    filter: '='
                },
                controller: function ($scope) {
                    $scope.metadata = filterService.metadata;

                    // initialize model variables
                    var info = filterService.getFilterMeta($scope.filter);
                    $scope.id = info.id;
                    $scope.type = info.type;
                    $scope.property = info.property;
                    $scope.method = info.method;
                    $scope.value = info.value;

                    $scope.$watch('type', function(type) {
                        $scope.property = type.properties[0];
                    });

                    $scope.$watch('property', function(property) {
                        $scope.method = $scope.metadata.compTypes[property.type][0];
                    });

                    $scope.getFilter = function() {
                        return {
                            id: $scope.id,
                            type: $scope.type.id,
                            property: $scope.property.id,
                            method: $scope.method.id,
                            value: $scope.value || ''
                        };
                    }
                },
                templateUrl: 'templates/filter-edit.html'
            }
        }]);
    }

    return {
        init: init
    };
}();