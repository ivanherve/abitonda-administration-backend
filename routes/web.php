<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use Illuminate\Http\Request;

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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// Route OPTIONS globale pour gérer toutes les pré-requêtes CORS
$router->options('{any:.*}', function() {
    return response('', 200);
});

$router->group(['prefix' => '/api'], function () use ($router) {
    // AUTHENTICATION
    $router->post('/signin', 'AuthController@signIn');
    $router->get('/signout', 'AuthController@signOut');

    // STUDENTS
    $router->get('/searchstudent', 'StudentController@searchStudent');
    $router->get('/students', 'StudentController@getStudents');
    $router->get('/soras', 'StudentController@getSorasList');
    $router->get('/birthdaymonth', 'StudentController@getMonthlyBirthday');
    $router->get('/birthdaylistperclasse', 'StudentController@getBirthdayListPerClass');
    $router->get('/presencelistperclasse', 'StudentController@getPresenceListPerClasse');
    $router->get('/students/pagination', 'StudentController@getTenStudents');
    $router->get('/sclasse', 'StudentController@getStudentPerClasse');
    $router->get('/neighborhoods', 'StudentController@getNeighborhoods');
    $router->get('/numbstudentperneighborhoods', 'StudentController@getNumberStudentPerNeighborhood');
    $router->get('/numbstudentpersector', 'StudentController@getNumberStudentPerSector');
    $router->get('/pastbday', 'StudentController@getPastBday');
    $router->get('/comingbday', 'StudentController@getComingBday');
    $router->post('/studentspicture', 'StudentController@getStudentsPicture');
    $router->get('/studentsregistrationsincomplete', 'StudentController@getRegistrationIncomplete');
    $router->get('/newstudents', 'StudentController@getNewStudents');
    $router->get('/schoolsite', 'StudentController@getSchoolSiteList');
    $router->get('/kindergardensite', 'StudentController@getKinderGardenSite');

    $router->post('/student/create', 'StudentController@addStudent');
    $router->post('/student/createmany', 'StudentController@addStudentCSV');
    $router->post('/editstudent', 'StudentController@editStudent');
    $router->get('/passtonextclass', 'StudentController@PassToNextClass');
    $router->get('/backtopreviousclass', 'StudentController@BackToPreviousClass');
    $router->get('/canteen', 'StudentController@getCanteen');
    $router->post('/student/update-transport', 'StudentController@updateTransport');
    $router->get('/student/{id}/pickups', 'StudentController@getStudentPickups');
    $router->get('/student/{id}/family', 'StudentController@getFamilyFromSibling');

    // Route pour récupérer les frères et sœurs
    $router->get('/students/{studentId}/siblings', 'StudentController@getSiblings');

    // FEES
    $router->get('/fees', 'FeeController@index');
    $router->post('fees', 'FeeController@store');
    $router->post('fees/{id}', 'FeeController@update');
    $router->delete('fees/{id}', 'FeeController@destroy');

    // EMPLOYEE
    $router->get('/employees', 'EmployeeController@getEmployees');
    $router->get('/banks', 'EmployeeController@getBanks');
    $router->get('/jobs', 'EmployeeController@getJobs');

    $router->post('/addemployee', 'EmployeeController@addEmployee');
    $router->post('/editemployee', 'EmployeeController@EditEmployee');

    // CLASSES
    $router->get('/classes', 'ClasseController@getClasses');
    $router->get('/getlistcontactperclasse', 'ClasseController@getListContactPerClasse');

    $router->post('/addteacher', 'ClasseController@AddTeacher');
    $router->post('/removeteacher', 'ClasseController@RemoveTeacher');
    $router->post('/addassistant', 'ClasseController@AddAssistant');
    $router->post('/removeassistant', 'ClasseController@RemoveAssistant');

    // PARENTS
    $router->get('/parents', 'StudentController@getStudentParents');
    $router->get('/listparents', 'ParentController@getListParent');
    $router->get('/allparents', 'ParentController@index');
    $router->post('/parents/create', 'ParentController@addParentOfOneStudent');
    $router->post('/removelinkparent', 'ParentController@removeLinkParent');
    $router->post('/editparent', 'ParentController@editParent');

    // INVOICES
    $router->get('/invoices', 'InvoicesController@getInvoices');
    $router->post('/addinvoices', 'InvoicesController@addInvoices');

    // TRANSPORT
    $router->get('/transport', 'StudentController@getTransportList');
    $router->get('/no-transport', 'StudentController@getNoTransportList');
    $router->get('/bus', 'BusController@index');
    $router->get('/bus/{id}', 'BusController@show');
    $router->post('/bus', 'BusController@store');
    $router->put('/bus/{id}', 'BusController@update');
    $router->delete('/bus/{id}', 'BusController@destroy');
    $router->get('/bus/{id}/students', 'BusController@getBusLinesStudents');
    $router->post('/bus/busteam', 'BusController@updateTeam');

    $router->get('/pickup', 'PickupController@index');
    $router->get('/pickup/{id}', 'PickupController@show');
    $router->post('/pickup', 'PickupController@store');
    $router->put('/pickup/{id}', 'PickupController@update');
    $router->delete('/pickup/{id}', 'PickupController@destroy');

    $router->get('/student-pickup', 'StudentPickupController@index');
    $router->post('/student-pickup', 'StudentPickupController@store');
    $router->put('/student-pickup/{studentId}/{directionId}/{dayOfWeek}', 'StudentPickupController@update');
    $router->delete('/student-pickup/{studentId}/{directionId}/{dayOfWeek}', 'StudentPickupController@destroy');
    $router->post('/student/unset-pickup-point', 'StudentController@unsetPickupPoint');
});
