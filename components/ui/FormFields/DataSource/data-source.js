app.directive('psDataSource', function ($timeout, $http) {
    return {
        scope: true,
        compile: function (element, attrs, transclude) {
            return function ($scope, $el, attrs, ctrl) {
                $scope.params = JSON.parse($el.find("data[name=params]").text());
                $scope.paramsGet = JSON.parse($el.find("data[name=params_get]").text());
                $scope.sqlParams = JSON.parse($el.find("data[name=params_default]").text());
                $scope.totalItems = $el.find("data[name=total_item]").text();
                $scope.name = $el.find("data[name=name]").text().trim();
                $scope.class = $el.find("data[name=class_alias]").text().trim();
                $scope.postData = $el.find("data[name=post_data]").text().trim();
                $scope.relationTo = $el.find("data[name=relation_to]").text().trim();
                $scope.insertData = [];
                $scope.updateData = [];

                $scope.deleteData = JSON.parse($el.find("data[name=delete_data]").text());
                $scope.deleteData = $scope.deleteData || [];

                $scope.untrackColumns = [];

                $scope.resetParam = function (key, name) {
                    if (typeof key == "undefined") {
                        for (i in $scope.sqlParams) {
                            delete $scope.sqlParams[i];
                        }
                    } else {
                        if (typeof $scope.sqlParams[name] != "undefined") {
                            delete $scope.sqlParams[name][key];
                        }
                    }
                }

                $scope.isRowEmpty = function (row, except) {
                    except = except || [];
                    for (i in row) {
                        if (row[i] != "" && $scope.untrackColumns.indexOf(i) < 0 && except.indexOf(i) < 0) {
                            return false;
                        }
                    }
                    return true;
                }

                $scope.updateParam = function (key, value, name) {
                    if (typeof name === "undefined") {
                        $scope.sqlParams[key] = value;
                        return true;
                    }

                    if (typeof $scope.sqlParams[name] == "undefined") {
                        $scope.sqlParams[name] = {};
                    }

                    if (typeof $scope.sqlParams[name] == "string" && key && value) {
                        $scope.sqlParams[name] = {};
                    }

                    $scope.sqlParams[name][key] = value;
                }

                $scope.setDebug = function (debug) {
                    if (typeof debug == "undefined") {
                        $scope.debugHTML = "";
                        return true;
                    }
                    $scope.debug = debug;
                    if ($scope.debug.sql) {
                        $scope.debug.sql = $scope.debug.sql.replace(/\r/g, '').replace(/\n/g, '');
                    }
                    if ($scope.debug.countSQL) {
                        $scope.debug.countSQL = $scope.debug.countSQL.replace(/\r/g, '').replace(/\n/g, '');
                    }
                    if ($scope.debug.function) {
                        $scope.debug.function = $scope.debug.function.replace(/\r/g, '').replace(/\n/g, '');
                    }
                    if ($scope.debug.countFunction) {
                        $scope.debug.countFunction = $scope.debug.countFunction.replace(/\r/g, '').replace(/\n/g, '');
                    }
                    $scope.debugHTML = JSON.stringify($scope.debug, undefined, 2);
                };

                if ($el.find("data[name=debug]").length > 0) {
                    $scope.setDebug(JSON.parse($el.find("data[name=debug]").text()));
                } else {
                    $scope.setDebug({});
                }

                $scope.query = function (f) {
                    var model = $scope.model || {};
                    var model_id = model.id || null;

                    $http.post(Yii.app.createUrl('/formfield/DataSource.query', $scope.paramsGet), {
                        model_id: model_id,
                        name: $scope.name,
                        class: $scope.class,
                        params: $.extend(angular.copy($scope.params), $scope.sqlParams)
                    }).success(function (data) {
                        $timeout(function () {
                            $scope.isDataReloaded = true;
                            $scope.data = data.data;
                            $scope.original = angular.copy($scope.data);
                            $scope.totalItems = data.count * 1;
                            $scope.setDebug(data.debug);
                            if (typeof f == "function") {
                                f(true, data);
                            }
                        }, 0);
                    }).error(function (data) {
                        if (typeof f == "function") {
                            f(false, data);
                        }
                    });
                }

                var jsParamExist = false;
                for (i in $scope.params) {
                    var p = $scope.params[i];
                    if (p.indexOf('js:') === 0) {
                        var value = $scope.$parent.$eval(p.replace('js:', ''));
                        var key = i;
                        $scope.$parent.$watch(p.replace('js:', ''), function (newv, oldv) {
                            if (newv != oldv) {
                                $scope.updateParam(key, newv);
                                $scope.query();
                            }
                        }, true);
                        $scope.updateParam(i, value)
                        jsParamExist = true;
                    }
                }

                if (jsParamExist) {
                    $scope.query();
                    $scope.data = [];
                } else {
                    $scope.data = JSON.parse($el.find("data[name=data]").text());
                }

                $scope.isDataReloaded = false;
                $scope.trackChanges = true;

                if ($scope.postData == 'Yes') {
                    $scope.original = angular.copy($scope.data);
                    $scope.$watch('data', function (newval, oldval) {
//                        console.log($scope.insertData, $scope.updateData, $scope.deleteData);

                        if (newval !== oldval && $scope.trackChanges) {
                            if ($scope.isDataReloaded) {
                                $scope.trackChanges = false;

                                for (i in $scope.insertData) {
                                    $scope.data.push($scope.insertData[i]);
                                }

                                for (i in $scope.updateData) {
                                    for (j in $scope.data) {
                                        if ($scope.data[j].id == $scope.updateData[i].id) {
                                            $scope.data[j] = $scope.updateData[i];
                                        }
                                    }
                                }

                                for (var i = $scope.data.length - 1; i >= 0; i--) {
                                    if (typeof $scope.data[i].id == "undefined")
                                        continue;

                                    if ($scope.deleteData.indexOf($scope.data[i].id) >= 0) {
                                        $scope.data.splice(i, 1);
                                    }
                                }

                                $timeout(function () {
                                    $scope.trackChanges = true;
                                    $scope.isDataReloaded = false;
                                }, 0);
                            } else {
                                $scope.insertData = [];
                                $scope.updateData = [];
                                $scope.deleteData = JSON.parse($el.find("data[name=delete_data]").text());

                                // find newly inserted data or updated data
                                for (i in newval) {
                                    var newv = newval[i];
                                    var found = false;
                                    for (k in $scope.original) {
                                        var oldv = $scope.original[k];
                                        if (newv['id'] != null && oldv['id'] == newv['id']) {
                                            found = true;

                                            var isEqual = true;
                                            for (m in oldv) {
                                                if ($scope.untrackColumns.indexOf(m) >= 0)
                                                    continue;

                                                for (n in newv) {
                                                    if ($scope.untrackColumns.indexOf(n) >= 0)
                                                        continue;

                                                    if (oldv[m] !== newv[m]) {
                                                        isEqual = false;
                                                    }
                                                }
                                            }

                                            if (!isEqual) {
                                                $scope.updateData.push(newv);
                                            }
                                        }
                                    }

                                    if (!found) {
                                        var isEmpty = true;
                                        for (x in newv) {
                                            if ($scope.untrackColumns.indexOf(x) >= 0)
                                                continue;

                                            if (newv[x] != '')
                                                isEmpty = false;
                                        }

                                        if (!isEmpty) {
                                            $scope.insertData.push(newv);
                                        }
                                    }
                                }

                                // find deleted data
                                for (i in $scope.original) {
                                    var del = $scope.original[i];
                                    var found = false;
                                    for (k in newval) {
                                        if (del['id'] == newval[k]['id']) {
                                            found = true;
                                        }
                                    }

                                    if (!found) {
                                        $scope.deleteData.push(del['id']);
                                    }
                                }
                            }
                        }

                    }, true);
                }

                $scope.$parent[$scope.name] = $scope;

            }

        }
    };
});