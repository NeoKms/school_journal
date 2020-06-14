<!DOCTYPE html>
<html lang="ru">
<?php
$user = $_SESSION['user'];
$is_student = in_array(3, $user['groups']);
?>
<head>
    <meta charset="UTF-8">
    <title><?=$title?></title>
    <link rel="stylesheet" type="text/css" href="<?=ROOT?>css/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="<?=ROOT?>css/bootstrap-grid.css">
    <link rel="stylesheet" type="text/css" href="<?=ROOT?>css/style.css">
    <script src="<?=ROOT?>js/jquery-3.5.1.min.js"></script>
</head>
<body>
<div class="header_middle">
    <div class="logo">
        <a href=""><img src="<?=ROOT?>assets/баннер.png" width="980px" height="222px"></a>
    </div>
</div>
<div class="header_menu">
    <ul class="header_menu_ul">
        <li class="header_menu_item">
            <a href="<?=ROOT?>index.php?user=admin">Админ</a>
        </li>
        <li class="header_menu_item">
            <a href="<?=ROOT?>index.php?user=teacher1">Препод 1</a>
        </li>
        <li class="header_menu_item">
            <a href="<?=ROOT?>index.php?user=teacher2">Препод&nbsp; 2</a>
        </li>
        <li class="header_menu_item">
            <a href="<?=ROOT?>index.php?user=student1">Ученик 1</a>
        </li>
        <li class="header_menu_item" <?=$is_student ? 'hidden' : ''?>>
            <a href="<?=ROOT?>journal" >Журнал</a>
        </li>
        <li class="header_menu_item">
            <a href="<?=ROOT?>diary">Дневник</a>
        </li>
        <li class="header_menu_item">
            <a href="<?=ROOT?>index.php?user=student2">Ученик 2</a>
        </li>
    </ul>
</div>
<?php
require (ROOT.'database/database.php');
$db=database::getInstance();
?>
<div class="middle">
    <div class="my_container">
        <div class="leftCol">
            <?php include_once ('left.menu.php');?>
        </div>
        <div class="rightCol">
            <?php
			if (mb_strpos($_SERVER['REQUEST_URI'],'journal')) {
                //получение списка сокращений
                $data = $db->query('select * from type_marks');
                $arrSokr = [];
                foreach ($data as $oneData) {
                    $arrSokr[$oneData['short']] = $oneData['full'];
                }
                ?>
                <h5>Список сокращений</h5>
                <?php
                foreach ($arrSokr as $key => $oneSokr) {
                    echo "<p><a id='{$key}' onclick='add_on_elem(this)' class='sokr'><b>{$key}</b> - {$oneSokr}</a></p>";
                }
            } elseif (!mb_strpos($_SERVER['REQUEST_URI'],'class_journal')) {
                include_once('right_news.php');
            }
            ?>
        </div>
        <div class="centerCol">
            <div class="content">



