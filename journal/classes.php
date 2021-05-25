<?php
session_start();
define('ROOT', '../');
require_once(ROOT . 'classes/sentry.php');
require_once (ROOT.'database/database.php');
$db=database::getInstance();
if (empty($_REQUEST['id'])) die('не верный класс');
else {
    $section = $_REQUEST['id'];
}
if ($section<0) die('ошибка поиска класса');
$idUser=$_SESSION['user']['id'];
$userGr=$_SESSION['user']['groups'];

$q="select id, name from subjects where active=1 and group_id=?";
$params = [$section];
if (in_array(1,$userGr) || in_array(12,$userGr)){//админ или доступ к кл.журналу
    $is_admin=true;
    $q.=" and teacher_id IS NOT NULL";
} else{
	$q.=" and teacher_id=?";
    $params[] = $idUser;
}
$res = $db->querySafe($q,$params);
?>
<b>Загрузка журнала успеваемости:</b>
<a class='btn noline btn-outline-success' href="<?=ROOT?>journal/create_xls.php?type=1&classId=<?=$_REQUEST['id']?>" role="button">1 четв.</a>
<a class='btn noline btn-outline-success' href="<?=ROOT?>journal/create_xls.php?type=2&classId=<?=$_REQUEST['id']?>" role="button">2 четв.</a>
<a class='btn noline btn-outline-success' href="<?=ROOT?>journal/create_xls.php?type=3&classId=<?=$_REQUEST['id']?>" role="button">3 четв.</a>
<a class='btn noline btn-outline-success' href="<?=ROOT?>journal/create_xls.php?type=4&classId=<?=$_REQUEST['id']?>" role="button">4 четв.</a>
<a class='btn noline btn-outline-danger' href="<?=ROOT?>journal/create_xls.php?type=year&classId=<?=$_REQUEST['id']?>" role="button">За год</a>
<div class="dropdown-divider"></div>
<h5>Перечень предметов:</h5>
<div class="classes-btn">
<?foreach ($res as $obj){
    echo "<button class='btn classes btn-info' id='{$obj['id']}'>{$obj['name']}</button>";
}
?>
</div>
<div class="dropdown-divider"></div>
<script>
	$(".classes-btn button").each(function( index ) {
		let elem=$( this );
		elem.click(function(){
			unactiveClasses();
			elem.addClass('active');
			let journalElem=$("#journal");
			loadingShow(journalElem);
			obnul();
			journalElem.load("<?=ROOT?>/journal/schedule.php?id="+elem.attr('id')+"&classes=<?=$_REQUEST['id']?>",function () {
				// $("#loading").remove();
				let clss=document.getElementById("classes");
				let newmargin=parseInt(clss.offsetHeight)+180;
				$(".rightCol").css('margin-top',newmargin+'px');
			});
		});
	});
	function unactiveClasses() {
		$(".classes-btn button").each(function( index ) {
			let elem=$( this );
			elem.removeClass('active');
		});
	}
	function loadingShow(elem) {
		let text="<div id='loading' style='text-align: center;'><img src='<?=ROOT?>/assets/loadingNew.gif' width='200px'><h2>Пожалуйста, подождите немного</h2></div>";
		elem.html('');
		elem.append(text);
		elem.show();
	}
</script>
