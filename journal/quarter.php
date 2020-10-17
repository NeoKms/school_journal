<?php
session_start();
define('ROOT', '../');
require_once (ROOT.'database/database.php');
require_once(ROOT . 'classes/sentry.php');
$db=database::getInstance();
$year=(int)$_REQUEST['year'];
$q='select id from quarters where year='.$year.' order by year desc';
$next = $db->query($q);
$nextYear=$year+1;
if (empty($next)) {
	$q = "insert into quarters (name, now, year, start1, finish1, start2, finish2, start3, finish3, start4, finish4) values ('{$year}-{$nextYear}','N','{$year}',0,0,0,0,0,0,0,0)";
	$res = $db->query($q);
}
$q = "select * from quarters order by year asc";
$req=$db->query($q);
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
foreach ($req as $obj){
    ?>
    <tr>
        <td><?=$obj['name']?></td>
        <input type="hidden" name="id_quarter[]" value="<?=$obj['id']?>">
		<td><input  name="NOW" value="<?=$obj['id']?>" type="radio" class="checkbox" <?=$obj['now']=='Y'?'checked':''?>></td>
        <td><input  name="s1[]" type="date" value="<?=date('Y-m-d',strtotime($obj['start1']))?>"></td>
        <td><input  name="f1[]" type="date" value="<?=date('Y-m-d',strtotime($obj['finish1']))?>"></td>
        <td><input  name="s2[]" type="date" value="<?=date('Y-m-d',strtotime($obj['start2']))?>"></td>
        <td><input  name="f2[]" type="date" value="<?=date('Y-m-d',strtotime($obj['finish2']))?>"></td>
        <td><input  name="s3[]" type="date" value="<?=date('Y-m-d',strtotime($obj['start3']))?>"></td>
        <td><input  name="f3[]" type="date" value="<?=date('Y-m-d',strtotime($obj['finish3']))?>"></td>
        <td><input  name="s4[]" type="date" value="<?=date('Y-m-d',strtotime($obj['start4']))?>"></td>
        <td><input  name="f4[]" type="date" value="<?=date('Y-m-d',strtotime($obj['finish4']))?>"></td>
    </tr>
<?
}
?>
	</tbody>
		</table>
		</div>
		<button type="submit" class="btn btn-info" style="margin-top: 25px;margin-left: 50px"> Сохранить </button>
	</form>
