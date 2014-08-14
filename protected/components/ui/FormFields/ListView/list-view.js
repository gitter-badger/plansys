app.directive('listView', function($timeout, $templateCache) {
    return {
        require: '?ngModel',
        scope: true,
        compile: function(element, attrs, transclude) {
            if (attrs.ngModel && !attrs.ngDelay) {
                attrs.$set('ngModel', '$parent.' + attrs.ngModel, false);
            }
            $templateCache.put('layout', element.find(".list-view-layout").html());

            return function($scope, $el, attrs, ctrl) {
                // when ng-model is changed from inside directive

                $scope.updateItem = function(value) {
                    $scope.updateItemInternal(value);
                    if (typeof ctrl != 'undefined' && value) {
                        $timeout(function() {
                            ctrl.$setViewValue($scope.selectedText);
                        }, 0);
                    }
                };

                $scope.updateItemInternal = function(value) {
                    if (typeof value != 'undefined') {
                        var ar = $scope.selected;
                        if (ar.indexOf(value) >= 0) {
                            ar.splice(ar.indexOf(value), 1);
                            $scope.selectedText = ar.join(",");
                        } else {
                            ar.push(value.replace(/,/g, ''));
                            $scope.selectedText = ar.join(",");
                        }
                    }
                }

                // when ng-model, or ng-form-list is changed from outside directive
                if (attrs.ngFormList) {
                    //ng-form-list, replace entire list using js instead of rendered from server
                    function changeFieldList() {
                        $timeout(function() {
                            $scope.formList = $scope.$eval(attrs.ngFormList);
                            $scope.updateItemInternal($scope.value);
                        }, 0);
                    }
                    $scope.$watch(attrs.ngFormList, changeFieldList);
                }

                if (typeof ctrl != 'undefined') {
                    ctrl.$render = function() {
                        if ($scope.inEditor && !$scope.$parent.fieldMatch($scope))
                            return;

                        if (typeof ctrl.$viewValue != 'undefined') {
                            $scope.selected = [];
                            ctrl.$viewValue.split(',').map(function(item) {
                                $scope.selected.push(item);
                                $scope.selectedText = $scope.selected.join(",");
                            });
                        }
                    };
                }

                // set default value
                $scope.formList = JSON.parse($el.find("data[name=form_list]:eq(0)").text());
                $scope.selected = JSON.parse($el.find("data[name=selected]").text());
                $scope.modelClass = $el.find("data[name=model_class]").html();
                if (typeof $scope.selected == "string") {
                    $scope.selected = $scope.selected.split(',').map(function(item) {
                        return(item.trim());
                    });
                }
                $scope.selectedText = $scope.selected.join(',');
                $scope.inEditor = typeof $scope.$parent.inEditor != "undefined";

                //if ngModel is present, use that instead of value from php
                if (attrs.ngModel) {
                    $timeout(function() {
                        var ngModelValue = $scope.$parent.$eval(attrs.ngModel);
                        if (typeof ngModelValue != "undefined") {
                            $scope.updateItemInternal(ngModelValue);
                        }
                    }, 0);
                }
            }
        }
    };
});