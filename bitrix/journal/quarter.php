<?php
include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
if(!CModule::IncludeModule("iblock")) die('ошибка битрикс');
$year=(int)$_REQUEST['year'];
$elem=new CIBlockElement();
$next=$elem::GetList(['PROPERTY_YEAR'=>'DESC'],['PROPERTY_YEAR'=>($year),'IBLOCK_ID'=>19],[]);
$nextYear=$year+1;
if ($next<=0) $elem->Add(["MODIFIED_BY" => $USER->GetID(),"IBLOCK_ID" => 19, "PROPERTY_VALUES"   =>  ['year'=>$year], "NAME"  => $year.'-'.$nextYear, "ACTIVE" =>"Y"]);
$req=$elem::GetList(
    ['PROPERTY_YEAR'=>'ASC'],
    ['IBLOCK_ID'=>19],
    false,false,
    [
        'ID',
        "NAME",
        'PROPERTY_NOW',
        'PROPERTY_START1',
        'PROPERTY_START2',
        'PROPERTY_START3',
        'PROPERTY_START4',
        'PROPERTY_FINISH1',
        'PROPERTY_FINISH2',
        'PROPERTY_FINISH3',
        'PROPERTY_FINISH4',
    ]);
?>
    <form method="post">
        <div class="center-block" style="overflow: scroll;overflow-y: hidden;">
        <table class="table table-sm table-quarter">
    <thead class="thead-dark">
    <tr>
        <th scope="col">Год</th>
		<th scope="col">Текущий</th>
        <th scope="col" colspan="2">1 четверть</th>
        <th scope="col" colspan="2">2 четверть</th>
        <th scope="col" colspan="2">3 четверть</th>
        <th scope="col" colspan="2">4 четверть</th>
    </tr>
    <tr>
        <th scope="col"></th>
		<th scope="col"></th>
        <th scope="col">Начало</th>
        <th scope="col">Конец</th>
        <th scope="col">Начало</th>
        <th scope="col">Конец</th>
        <th scope="col">Начало</th>
        <th scope="col">Конец</th>
        <th scope="col">Начало</th>
        <th scope="col">Конец</th>
    </tr>
    </thead>
    <tbody>
<?
while($obj=$req->Fetch()){
    ?>
    <tr>
        <td><?=$obj['NAME']?></td>
        <input type="hidden" name="id_quarter[]" value="<?=$obj['ID']?>">
		<td><input  name="NOW" value="<?=$obj['ID']?>" type="radio" class="checkbox" <?=$obj['PROPERTY_NOW_VALUE']=='Y'?'checked':''?>></td>
        <td><input  name="s1[]" type="date" value="<?=date('Y-m-d',strtotime($obj['PROPERTY_START1_VALUE']))?>"></td>
        <td><input  name="f1[]" type="date" value="<?=date('Y-m-d',strtotime($obj['PROPERTY_FINISH1_VALUE']))?>"></td>
        <td><input  name="s2[]" type="date" value="<?=date('Y-m-d',strtotime($obj['PROPERTY_START2_VALUE']))?>"></td>
        <td><input  name="f2[]" type="date" value="<?=date('Y-m-d',strtotime($obj['PROPERTY_FINISH2_VALUE']))?>"></td>
        <td><input  name="s3[]" type="date" value="<?=date('Y-m-d',strtotime($obj['PROPERTY_START3_VALUE']))?>"></td>
        <td><input  name="f3[]" type="date" value="<?=date('Y-m-d',strtotime($obj['PROPERTY_FINISH3_VALUE']))?>"></td>
        <td><input  name="s4[]" type="date" value="<?=date('Y-m-d',strtotime($obj['PROPERTY_START4_VALUE']))?>"></td>
        <td><input  name="f4[]" type="date" value="<?=date('Y-m-d',strtotime($obj['PROPERTY_FINISH4_VALUE']))?>"></td>
    </tr>
<?
}
?>
    </tbody>
</table>
</div>
        <button type="submit" class="btn btn-info" style="margin-top: 25px;margin-left: 50px"> Сохранить </button>
    </form>
<style>
	.table-quarter input {
		width: 130px;
	}
	.table-quarter input.checkbox {
		width: 25px;
	}
</style>
<script>
	// function checkNowYerOnce(elem) {
	// 	console.log('kek');
	// 	let fl=true;
	// 	$(".checkbox").each(function( index ) {
	// 		if ($( this ).attr('checked')=='checked') fl=false;
	// 	});
	// 	console.log(fl);
	// 	if (fl)
	// 		$(elem).attr('checked',false);
	// 	else
	// 		$(elem).attr('checked',true);
	// }
</script>
