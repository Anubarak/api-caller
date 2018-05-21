/**
 * API Caller  plugin for Craft CMS
 *
 * API Caller  JS
 *
 * @author    Robin Schambach
 * @copyright Copyright (c) 2018 Robin Schambach
 * @link      https://www.secondred.de/
 * @package   ApiCaller
 * @since     1.0.0
 */


const myApp = angular.module('myApp',[]).config(function($interpolateProvider, $httpProvider){
    $interpolateProvider.startSymbol('{[{').endSymbol('}]}');

    // Use x-www-form-urlencoded Content-Type
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
    /**
     * The workhorse; converts an object to x-www-form-urlencoded serialization.
     * @param {Object} obj
     * @return {String}
     */
    var param = function(obj) {
        var query = '', name, value, fullSubName, subName, subValue, innerObj, i;

        for(name in obj) {
            value = obj[name];

            if(value instanceof Array) {
                for(i=0; i<value.length; ++i) {
                    subValue = value[i];
                    fullSubName = name + '[' + i + ']';
                    innerObj = {};
                    innerObj[fullSubName] = subValue;
                    query += param(innerObj) + '&';
                }
            }
            else if(value instanceof Object) {
                for(subName in value) {
                    subValue = value[subName];
                    fullSubName = name + '[' + subName + ']';
                    innerObj = {};
                    innerObj[fullSubName] = subValue;
                    query += param(innerObj) + '&';
                }
            }
            else if(value !== undefined && value !== null)
                query += encodeURIComponent(name) + '=' + encodeURIComponent(value) + '&';
        }

        return query.length ? query.substr(0, query.length - 1) : query;
    };

    // Override $http service's default transformRequest
    $httpProvider.defaults.transformRequest = [function(data) {
        return angular.isObject(data) && String(data) !== '[object File]' ? param(data) : data;
    }];
});

var test = null;
myApp.controller('settings', ['$scope', '$http', function ($scope, $http) {
    test = $scope;
    $scope.sections = data.sections;
}]);
$(document).ready(function(){

    $('#settings-start-queue').click(function(){

        var data = {
            settings: {
                'sectionId': $('#settings-sectionId').val(),
                'clientId': $('#settings-clientId').val(),
                'folderId': $('#settings-folderId').val(),
                'targetField': $('#settings-targetField').val(),
                'sourceField': $('#settings-sourceField').val(),
            },
            action: 'api-caller/index'
        };

        Craft.postActionRequest('api-caller/default/index', data, function(response){
            console.log(response);
            if(response.success === true){
                Craft.cp.displayNotice(response.message);
            }
        });
    });
});