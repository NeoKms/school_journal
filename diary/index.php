<?
define('ROOT', '../');
session_start();
$title  = 'Дневник ученика';
$page = 'Дневник';
require(ROOT."header.php");
$arGroups = $_SESSION['user']['groups'];
if (!in_array(3,$arGroups)):?>
    <div class="center"
		<h1>Вы не ученик. Увы :(</h1>
			<img style='box-shadow: 0 0 30px rgba(0,0,0,0.5); margin-top: 50px' src='<?=ROOT?>assets/giphy.gif' width="500">
	</div>
<?
die();
endif;
$studId=$_SESSION['user']['id'];
?>
<div id="diary" class="container">
</div>
<script>
	let text="<div id='loading' style='text-align: center;'><img src='<?=ROOT?>assets/loadingNew.gif' width='200px'><h2>Пожалуйста, подождите немного</h2></div>";
	$('#diary').append(text);
	$('#diary').load("<?=ROOT?>diary/diary.php?id=<?=$studId?>");
</script>

<? require(ROOT."footer.php");?>
