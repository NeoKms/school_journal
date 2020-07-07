<?php
if (mb_strpos($_SERVER['REQUEST_URI'], 'journal')) {
    $idUser = $_SESSION['user']['id'];
    $is_admin = in_array(1,$_SESSION['user']['groups']);
    if (!$is_admin) {
    	$groups=$db->query('SELECT group_id from subjects where teacher_id='.$_SESSION['user']['id'].' GROUP by group_id');
    	$groupsIds = [];
    	foreach ($groups as $oneGroup){
            $groupsIds[] = $oneGroup['group_id'];
		}
        $arResult=$db->query("SELECT * from groups where group_id in (".implode(",",$groupsIds).") ORDER BY name ASC");
    } else {
        $arResult=$db->query('SELECT * from groups ORDER BY name ASC');
    }
} else {
    $arResult = [
        ['name' => 'ИНФОРМАЦИЯ'],
        ['name' => 'ДИСТАНЦИОННОЕ ОБУЧЕНИЕ'],
        ['name' => 'ОБРАЗОВАТЕЛЬНАЯ ДЕЯТЕЛЬНОСТЬ'],
        ['name' => 'СВЕДЕНИЯ ОБ ОБРАЗОВАТЕЛЬНОЙ ОРГАНИЗАЦИИ'],
        ['name' => 'ПРЕПОДАВАТЕЛИ'],
        ['name' => 'БАЗОВОЕ РАСПИСАНИЕ'],
        ['name' => 'РАСПИСАНИЕ'],
        ['name' => 'МЕРОПРИЯТИЯ'],
        ['name' => 'ЛЕТОПИСЬ СОБЫТИЙ'],
        ['name' => 'СПИСОК ЛИТЕРАТУРЫ'],
        ['name' => 'ГИМН ШКОЛЫ'],
        ['name' => 'ШКОЛЬНАЯ ФОРМА'],
        ['name' => 'ПУБЛИЧНЫЕ ОТЧЕТЫ'],
        ['name' => 'ПОЛОЖЕНИЕ О КОМИССИИ ПО УРЕГУЛИРОВАНИЮ СПОРОВ'],
        ['name' => 'КОНТАКТЫ'],
        ['name' => 'КАРТА САЙТА'],
    ];
}
?>
<?php
if (!empty($arResult)) {
    if (mb_strpos($_SERVER['REQUEST_URI'], 'journal')) {?>
	<div class="left_menu">
		<ul>
                <?php foreach ($arResult as $arItem) { ?>
					<li><a id="<?=$arItem['id']?>">Классный журнал</br> <?=$arItem["name"]?></a></li>
                    <?php
                }
                if ($is_admin) {
                    echo "<li><a id='quarter'>Управление четвертями</a></li>";
                }
                ?>
			</ul>
		</div>
		<style>
			.left_menu ul li {
				padding: 10px;
				text-align: center;
				cursor: pointer;
			}
		</style>
		<script>
			$(".left_menu ul li").each(function (index) {
				let elem = $(this);
				elem.click(function () {
					unactive();
					elem.children("a").addClass('active');
					if (elem.children('a').text() != 'Управление четвертями') {
						$("#classes").load("<?=ROOT?>journal/classes.php?id=" + elem.children("a").attr('id'));
					} else {
						$("#classes").load("<?=ROOT?>journal/quarter.php?year=" +<?=date('Y')?>);
					}
					$("#journal").hide();
				});
			});

			function unactive() {
				$(".left_menu ul li").each(function (index) {
					let elem = $(this).children("a");
					elem.removeClass('active');
				});
			}
		</script>
        <?php
    }
    else {
    	?>
<div class="left_menu">
	<ul>
		<?
        foreach ($arResult as $arItem) {?>
			<li><a><?=$arItem["name"]?></a></li>
        <?}?>
	</ul>
</div>
		<script>
			$(".left_menu ul li").each(function (index) {
				let elem = $(this);
				elem.click(function () {
					unactive();
					elem.children("a").addClass('active');
				});
			});

			function unactive() {
				$(".left_menu ul li").each(function (index) {
					let elem = $(this).children("a");
					elem.removeClass('active');
				});
			}
		</script>
		<?
	}
}
function getSections($id)
{
    $sect=[];
    return $sect;
}
?>

