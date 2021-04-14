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
    $router->get('/students/pagination', 'StudentController@getTenStudents');
    $router->post('/student/create', 'StudentController@addStudent');
    $router->get('/sclasse', 'StudentController@getStudentPerClasse');

    // CLASSES
    $router->get('/classes', 'ClasseController@getClasses');

    // PARENTS
    $router->get('/parents', 'StudentController@getParents');
    $router->post('/parents/create', 'ParentController@addParentOfOneStudent');
});
