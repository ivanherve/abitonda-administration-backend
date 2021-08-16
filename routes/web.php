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

$router->group(['prefix' => '/api'], function () use ($router) {
    // AUTHENTICATION
    $router->post('/signin', 'AuthController@signIn');
    $router->get('/signout', 'AuthController@signOut');

    // STUDENTS
    $router->get('/students', 'StudentController@getStudents');
    $router->get('/soras', 'StudentController@getSorasList');
    $router->get('/transport', 'StudentController@getTransportList');
    $router->get('/birthdaylistperclasse', 'StudentController@getBirthdayListPerClass');
    $router->get('/presencelistperclasse', 'StudentController@getPresenceListPerClasse');
    $router->get('/students/pagination', 'StudentController@getTenStudents');
    $router->post('/student/create', 'StudentController@addStudent');
    $router->post('/student/createmany', 'StudentController@addStudentCSV');
    $router->get('/sclasse', 'StudentController@getStudentPerClasse');
    $router->post('/editstudent', 'StudentController@editStudent');
    $router->get('/searchstudent', 'StudentController@searchStudent');
    $router->get('/neighborhoods', 'StudentController@getNeighborhoods');
    $router->get('/numbstudentperneighborhoods', 'StudentController@getNumberStudentPerNeighborhood');
    $router->get('/numbstudentpersector', 'StudentController@getNumberStudentPerSector');
    $router->get('/pastbday', 'StudentController@getPastBday');
    $router->get('/comingbday', 'StudentController@getComingBday');
    $router->get('/passtonextclass', 'StudentController@PassToNextClass');

    // EMPLOYEE
    $router->get('/employees', 'EmployeeController@getEmployees');
    $router->get('/banks', 'EmployeeController@getBanks');
    $router->get('/jobs', 'EmployeeController@getJobs');
    $router->post('/addemployee', 'EmployeeController@addEmployee');
    $router->post('/editemployee', 'EmployeeController@EditEmployee');

    // CLASSES
    $router->get('/classes', 'ClasseController@getClasses');
    $router->post('/addteacher', 'ClasseController@AddTeacher');
    $router->post('/removeteacher', 'ClasseController@RemoveTeacher');
    $router->post('/addassistant', 'ClasseController@AddAssistant');
    $router->post('/removeassistant', 'ClasseController@RemoveAssistant');
    $router->get('/getlistcontactperclasse', 'ClasseController@getListContactPerClasse');

    // PARENTS
    $router->get('/parents', 'StudentController@getStudentParents');
    $router->post('/parents/create', 'ParentController@addParentOfOneStudent');
    $router->get('/listparents', 'ParentController@getListParent');
    $router->post('/removelinkparent', 'ParentController@removeLinkParent');
    $router->post('/editparent', 'ParentController@editParent');

    // INVOICES
    $router->get('/invoices', 'InvoicesController@getInvoices');
    $router->post('/addinvoices', 'InvoicesController@addInvoices');
});
