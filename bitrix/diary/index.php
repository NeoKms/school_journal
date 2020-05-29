<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Дневник ученика");
global $USER;
$arGroups = $USER->GetUserGroupArray();
if (!in_array(9,$arGroups)) {
    echo "<div style='text-align: center;' > 
<h1>Вы не ученик. Увы :(</h1>
<img style='box-shadow: 0 0 30px rgba(0,0,0,0.5);' src='giphy.gif'> </div>";
    die();
}
$studId=$USER->getId();
$APPLICATION->SetTitle("Дневник ученика: ".$USER->GetFullName());
//$studId=251;// для тестов
?>
<div id="diary" class="container">

</div>
<script>
	let text="<div id='loading' style='text-align: center;'><img src='/journal/loading.gif' width='200px'><h2>Пожалуйста, подождите немного</h2></div>";
	$('#diary').append(text);
	$('#diary').load("/diary/diary.php?id=<?=$studId?>");
</script>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
