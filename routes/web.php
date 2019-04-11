<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
ini_set('display_errors', 1);

$app->get('/', function () use ($app) {
    return $app->version();
});

$app->post('/login', 'AuthController@login');
$app->post('/get-user', 'AuthController@getUser');
$app->post('/check-access-control', 'AuthController@checkAccessControl');
$app->post('/can-access', 'AuthController@canAccess');
$app->post('/save-access-control', 'AuthController@saveAccessControl');

$app->post('/get-notification', 'NotificationController@getNotification');

$app->post('/get-absence', 'AbsenceController@getAbsence');
$app->post('/get-absence-detail', 'AbsenceController@getAbsenceDetail');

$app->post('/get-notification-absence', 'NotificationController@getNotificationAbsence');
$app->post('/save-approve-absence', 'NotificationController@saveApproveAbsence');


$app->post('/get-notification-requisition', 'NotificationController@getNotificationRequisition');
$app->post('/save-approve-requisition', 'NotificationController@saveApproveRequisition');

$app->post('/get-notification-spkl', 'NotificationController@getNotificationSpkl');
$app->post('/save-approve-spkl', 'NotificationController@saveApproveSpkl');

$app->post('/get-notification-quote', 'NotificationController@getNotificationQuote');
$app->post('/save-approve-quote', 'NotificationController@saveApproveQuote');

$app->post('/get-birthday', 'BirthdayController@getBirthday');

$app->group(['prefix' => 'purchasing'], function () use ($app) {
	$app->group(['prefix' => 'my-requisition'], function () use ($app) {
    	$app->post('/get-requisition', 'Purchasing\MyRequisitionController@getRequisition');
	    $app->post('/get-requisition-detail', 'Purchasing\MyRequisitionController@getRequisitionDetail');
	});
});

