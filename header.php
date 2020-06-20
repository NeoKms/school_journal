<!DOCTYPE html>
<html lang="ru">
<?php
$user = $_SESSION['user'];
$is_student = in_array(3, $user['groups']);
if (!isset($page)) $page='';
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
<div class="header_middle" hidden>
    <div class="logo">
<!--        <a href=""><img src="--><?//=ROOT?><!--assets/баннер.png" width="980px" height="222px"></a>-->
    </div>
</div>
<nav class="navbar navbar-expand-lg navbar-light bg-light header_menu">
	<a class="navbar-brand" href="index.php"><img src="<?=ROOT?>assets/saints.png" alt="logo" width="100"></a>
	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
		<span class="navbar-toggler-icon"></span>
	</button>
	<div class="collapse navbar-collapse">
		<ul class="navbar-nav mr-auto">
			<li class="nav-item">
				<a class="nav-link" href="<?=ROOT?>index.php">На главную</a>
			</li>
			<li class="nav-item dropdown">
				<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Пользователи</a>
				<div class="dropdown-menu" aria-labelledby="navbarDropdown">
					<a class="dropdown-item" href="<?=ROOT?>index.php?user=admin">Админ</a>
					<div class="dropdown-divider"></div>
					<a class="dropdown-item" href="<?=ROOT?>index.php?user=teacher1">Препод 1 </a>
					<a class="dropdown-item" href="<?=ROOT?>index.php?user=teacher2">Препод 2 </a>
					<div class="dropdown-divider"></div>
					<a class="dropdown-item" href="<?=ROOT?>index.php?user=student1">Ученик 1 </a>
					<a class="dropdown-item" href="<?=ROOT?>index.php?user=student2">Ученик 2 </a>
				</div>
			</li>
			<li class="nav-item">
				<a class="nav-link disabled">О школе</a>
			</li>
			<li class="nav-item"  <?=$is_student ? "hidden" : ""?>>
				<a class="nav-link" href="<?=ROOT?>journal/">Журнал</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="<?=ROOT?>diary/">Дневник</a>
			</li>
			<li class="nav-item">
				<a class="nav-link disabled" href="">Контакты</a>
			</li>
			<li class="nav-item">
				<a class="nav-link disabled" id="">Лицензии</a>
			</li>
			<li class="nav-item">
				<a class="nav-link disabled" id="">Аккредитация</a>
			</li>
		</ul>
	</div>
</nav>
<script>
	$(".nav-link").each(function (index) {
		let elem = $(this);
		let pageName='<?=htmlspecialchars($page)?>';
		if (pageName==elem.text()) {
			elem.addClass('active');
		}
	});
</script>
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



