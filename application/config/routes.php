<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'posts';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// 회원가입 폼, 요청
$route['auth/register']['get'] = 'auth/register_form';
$route['auth/register']['post'] = 'auth/register_process';

// 로그인 폼, 요청
$route['auth/login']['get'] = 'auth/login_form';
$route['auth/login']['post'] = 'auth/login_process';
$route['auth/logout'] = 'auth/logout';

// 글쓰기 폼, 요청
$route['posts/write']['get'] = 'posts/write_form';
$route['posts/write']['post'] = 'posts/write_process';
// 글 삭제
$route['posts/delete/(:num)'] = 'posts/delete/$1';

// 댓글 crd
$route['comments/list']['get'] = 'comments/list';
$route['comments/create']['post'] = 'comments/create';
$route['comments/delete']['post'] = 'comments/delete';

//파일 다운로드
$route['files/download/(:num)'] = 'files/download/$1';
//파일 삭제
$route['files/delete/(:num)'] = 'files/delete/$1';



