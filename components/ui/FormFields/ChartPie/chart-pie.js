app.directive('psChartPie', function ($timeout) {
    return {
        scope: true,
        link: function ($scope, $el, attrs) {
		
			/*********************** DEEP EXTEND ********************************/
			var deepExtend = function (destination, source) {
			  for (var property in source) {
				if (source[property] && source[property].constructor &&
				 source[property].constructor === Object) {
				  destination[property] = destination[property] || {};
				  arguments.callee(destination[property], source[property]);
				} else {
				  destination[property] = source[property];
				}
			  }
			  return destination;
			}
			
			$scope.chartTitle = $el.find("data[name=chartTitle]").text();
			$scope.chartType = $el.find("data[name=chartType]").text().toLowerCase();
			$scope.chartName = $el.find("data[name=chartName]").text();
			$scope.series = JSON.parse($el.find("data[name=series]").text());
			$scope.options = JSON.parse($el.find("data[name=options]").text());	
			
			var chartData = [];				
			for(i in $scope.series)
			{
				var tmp = {};
				tmp['name'] = $scope.series[i].label;
				tmp['y'] = parseInt($scope.series[i].value);
				tmp['color'] = $scope.series[i].color;
				
				chartData.push(tmp);
			}
			
			var defaultOptions = {
				chart : {
					type: $scope.chartType,
					renderTo : $scope.chartType + 'Container' + $scope.chartName
				},
				credits : {
					enabled : false
				}
			}
			
			if($scope.options == null) {
				$scope.options = {};
			}
			
			$scope.options = deepExtend($scope.options, defaultOptions);
			
			var chart = new Highcharts.Chart($scope.options);
			
			chart.setTitle({ text: $scope.chartTitle });
			chart.addSeries({
				name: 'value', 
				data: chartData
			});
		}
	}
});