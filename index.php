<?php
session_start();
define('ROOT','');
$page = 'На главную';
$title = 'Главная';
$users = [
    'admin' => [
            'id' => 1,
            'groups' => [1],
            'name' =>'Администратор'
    ],
    'teacher1' => [
        'id' => 2,
        'groups' => [2],
        'name' =>'Васильев В.В.'
    ],
    'teacher2' => [
        'id' => 3,
        'groups' => [2],
        'name' =>'Петров А.Ю.'
    ],
    'student1' => [
        'id' => 4,
        'groups' => [3],
        'name' =>'Сереньтьев В.Г.'
    ],
    'student2' => [
        'id' => 5,
        'groups' => [3],
        'name' =>'Мужичок К.У.'
    ]
];
if (isset($_REQUEST['user'])) $_SESSION['user'] = $users[$_REQUEST['user']];
else  $_SESSION['user'] = $users['admin'];
require ("header.php");
echo (" Текущий пользователь: {$_SESSION['user']['name']}</br> id: {$_SESSION['user']['id']}</br>  groups: ".implode(',',$_SESSION['user']['groups']));
if (isset($_REQUEST['reinit'])) {
    $db->reinitDb();
}

require ("footer.php");
