<?php
include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
if(!CModule::IncludeModule("iblock")) die('ошибка битрикс');
if (empty($_REQUEST['id'])) die('не верный класс');
else {
    $group=\CIBlockElement::GetById($_REQUEST['id'])->Fetch();
    $groupsSections=getSections(11);
    $group['section_name']=$groupsSections[$group['IBLOCK_SECTION_ID']];
}
$classesSections=getSections(10);
$section=array_search($group['section_name'],$classesSections);
if ($section<0) die('ошибка поиска класса');
$idUser=$USER->GetID();
$userGr=$USER->GetUserGroupArray();
if (in_array(1,$userGr) || in_array(12,$userGr)){//админ или доступ к кл.журналу
    $is_admin=true;
}
$filter=["IBLOCK_ID"=>10,'ACTIVE'=>'Y','SECTION_ID'=>$section,'!PROPERTY_teacher'=>false];
if (!$is_admin) $filter['PROPERTY_teacher']=$idUser;
//$filter['PROPERTY_teacher']=37;
$res=CIBlockElement::GetList(['NAME'=>'ASC'],$filter,false,false,['ID','NAME']);
?>
<b>Загрузка журнала успеваемости:</b>
<a class='btn noline btn-success' href="/journal/create_xls.php?type=1&classId=<?=$_REQUEST['id']?>" role="button">1 четв.</a>
<a class='btn noline btn-success' href="/journal/create_xls.php?type=2&classId=<?=$_REQUEST['id']?>" role="button">2 четв.</a>
<a class='btn noline btn-success' href="/journal/create_xls.php?type=3&classId=<?=$_REQUEST['id']?>" role="button">3 четв.</a>
<a class='btn noline btn-success' href="/journal/create_xls.php?type=4&classId=<?=$_REQUEST['id']?>" role="button">4 четв.</a>
<a class='btn noline btn-danger' href="/journal/create_xls.php?type=year&classId=<?=$_REQUEST['id']?>" role="button">За год</a>
<h2>Перечень предметов:</h2>
<div class="classes-btn">
<?while ($obj=$res->fetch()){
    $obj=\CIBlockElement::GetById($obj['ID'])->Fetch();
    echo "<button class='btn classes btn-info' id='{$obj['ID']}'>{$obj['NAME']}</button>";
}
?>
</div>
<style>
    .classes {
        margin: 4px;
        padding: 4px 8px 4px 4px;
    }
	.noline {
		text-decoration: none;
		padding: 2px 4px 2px 4px;
	}
</style>
<script>
	$(".classes-btn button").each(function( index ) {
		let elem=$( this );
		elem.click(function(){
			unactiveClasses();
			elem.addClass('active');
			let journalElem=$("#journal");
			loadingShow(journalElem);
			obnul();
			journalElem.load("/journal/schedule.php?id="+elem.attr('id')+"&classes=<?=$_REQUEST['id']?>",function () {
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
		let text="<div id='loading' style='text-align: center;'><img src='/journal/loading.gif' width='200px'><h2>Пожалуйста, подождите немного</h2></div>";
		elem.html('');
		elem.append(text);
		elem.show();
	}
</script>

<?php
function getSections($id)
{
    $sect=[];
    $obSection = CIBlockSection::GetTreeList(['IBLOCK_ID' => $id]);
    while ($arResult = $obSection->GetNext()) {
        $sect[$arResult['ID']]= $arResult['NAME'];
    }
    return $sect;
}
?>
