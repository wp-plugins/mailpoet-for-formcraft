FormCraftApp.controller('MailPoetController', function($scope, $http) {
	$scope.addMap = function(){
		if ($scope.SelectedList=='' || $scope.SelectedColumn==''){return false;}
		$scope.$parent.Addons.MailPoet = $scope.$parent.Addons.MailPoet || {};
		$scope.$parent.Addons.MailPoet.Map = $scope.$parent.Addons.MailPoet.Map || [];
		$scope.$parent.Addons.MailPoet.Map.push({
			'listID': $scope.SelectedList,
			'listName': jQuery('#mailpoet-map .select-list option:selected').text(),
			'columnID': $scope.SelectedColumn,
			'columnName': jQuery('#mailpoet-map .select-column option:selected').text(),
			'formField': jQuery('#mailpoet-map .select-field').val()
		});
	}
	$scope.removeMap = function ($index)
	{
		$scope.$parent.Addons.MailPoet.Map.splice($index, 1);
	}
});