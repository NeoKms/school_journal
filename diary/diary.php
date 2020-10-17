<?php
define('ROOT', '../');
require_once (ROOT.'database/database.php');
require_once(ROOT . 'classes/sentry.php');
session_start();
if (empty($_REQUEST['id'])) die('неверный ид ученика');
else {
    $studentID=$_REQUEST['id'];
}
if (isset($_REQUEST['PAGE'])) $pageNum=(int)$_REQUEST['PAGE'];
if (isset($_REQUEST['QUAR'])) $quarterNum=(int)$_REQUEST['QUAR'];
if (isset($_REQUEST['allQuar'])) {
	$allQuar=true;
}
else {
	$allQuar=false;
}
$section=getCurrentSection($studentID);
$quarters=getQuarters($quarterNum);
$currentWeek=getWeek($quarters,$pageNum);
if ($allQuar) {
    $currentWeek = [$quarters['now']['s'], $quarters['now']['f']];
}
$db = database::getInstance();
$q='select journal.id, journal.date_int, journal.name,journal.teacher,journal.subject,journal.date,journal.theme,journal.comment, subjects.name as subj_name
		from journal inner join subjects on journal.subject = subjects.id 
		where journal.group_id='.$section.' 
		and journal.date_int>='.strtotime($currentWeek[0]).' 
		and journal.date_int<='.strtotime($currentWeek[1]).'
		order by date_int asc';
$res = $db->query($q);
$classesHeads=[];
$classesMarks=[];
$subjectIDS=[];
$journalIDS=[];
$dayWeek=new DateTime();
foreach ($res as $obj){
    $date=$obj['date'];
    $classID=$obj['subject'];
    $journalId=$obj['id'];
    $journalIDS[]=$journalId;
    $comment=$obj['comment'];
    $theme=$obj['theme'];
    $subjectIDS[]=$obj['subject'];
    if (!isset($classesHeads[$date])) $classesHeads[$date] = switchWeek($dayWeek->setTimestamp($obj['date_int'])->format('N'));
    if (!isset($classesMarks[$classID]))$classesMarks[$classID]=['NAME'=>$obj["subj_name"],'DATES'=>[]];
    if (!isset($classesMarks[$classID]['DATES'][$journalId])) {
    	$data_content='';
    	if (!empty($theme)) $data_content.="Тема: {$theme} \n";
        if (!empty($comment)) $data_content.="Комментарий: {$comment}";
        $classesMarks[$classID]['DATES'][$journalId] = [
            'date' => $date,
            'data_content' => $data_content,
            'marks' => []
        ];
    }
}
$subjectIDS=array_unique($subjectIDS);
$journalIDS=array_unique($journalIDS);
$existMarksIds=[];
$q='select marks.subject_id,marks.journal_id,marks.type_id,marks.student_id,marks.id,marks.mark, type_marks.short'.
    ' from marks inner join type_marks on marks.type_id = type_marks.id '.
    "where marks.subject_id in ('".implode("','",$subjectIDS)."') and marks.journal_id in ('".implode("','",$journalIDS)."')".
    " and marks.student_id=".$studentID." order by marks.mark asc";
$res = $db->query($q);
foreach ($res as $obj){
    $studID=$obj['student_id'];
    $journalID=$obj['journal_id'];
    $subjectID=$obj['subject_id'];
    if (isset($classesMarks[$subjectID]['DATES'][$journalID])){
    	if (!empty($obj['mark'])) {
            $existMarksIds[] = $subjectID;
            $classesMarks[$subjectID]['DATES'][$journalID]['marks'][] = [
                $obj['mark'],
                $obj['short']
            ];
        }
	}
}
$delSubjects=array_diff($subjectIDS,$existMarksIds);
foreach ($delSubjects as $oneId){
	unset($classesMarks[$oneId]);
}
//echo "<pre>";
//print_r($classesMarks);
//echo "</pre>";
//exit;
$countClassesInQuarter=$db->query("select count(*) from journal 
									where group_id={$section} 
									and subject={$classID} 
									and date_int>=".strtotime($quarters['now']['s'])."
									and date_int<=".strtotime($quarters['now']['f']))[0]['count(*)'];
$colWeek=(int)round($countClassesInQuarter/5,PHP_ROUND_HALF_DOWN);
if (($countClassesInQuarter%5)!==0)$colWeek++;
?>
    <div class="row center headText">
        <h4><?=isset($_REQUEST['QUAR'])?'Выбранная':'Текущая'?> четверть №<?=$quarters['now']['num']?> с <?=$quarters['now']['s']?> по <?=$quarters['now']['f']?></h4>
        <h6><?if($allQuar):echo 'Просмотр четверти';else:?><?=isset($_REQUEST['PAGE'])?'Выбранная':'Текущая'?> учебная неделя №<?=$pageNum?>
            с <?=$currentWeek[0]?> по <?=$currentWeek[1]?><?endif;?></h6>
        <div class="page_navigator">
            <button class="btn btn-primary" onclick="quar_loader(this)"
                    id="<?=$quarters['now']['num']==1?1:$quarters['now']['num']-1?>">
                <i class="arrow leftArrow"></i> четверть</button>
            <button class="btn btn-primary" onclick="page_loader(this)"
                    id="<?=$pageNum==1?1:($pageNum==NULL?1:($pageNum-1))?>">
                <i class="arrow leftArrow"></i> неделя
            </button>
            <?if ($allQuar):?>
            <button class="btn btn-warning" onclick="page_loader(this)"
                    id="<?=$pageNum==NULL?1:$pageNum?>">
                На неделе
            </button>
            <?else:?>
                <button class="btn btn-warning" onclick="quar_loader(this)"
                        id="<?=$quarters['now']['num']?>" data-action="allQuar">
                    За четверть
                </button>
            <?endif;?>
            <button class="btn btn-primary" onclick="page_loader(this)"
                    id="<?=$pageNum==$colWeek?$colWeek:($pageNum+1)?>">
                неделя <i class="arrow rightArrow"></i>
            </button>
            <button class="btn btn-primary" onclick="quar_loader(this)"
                    id="<?=$quarters['now']['num']==4?4:($quarters['now']['num']+1)?>">
                четверть <i class="arrow rightArrow"></i></button>
        </div>
    </div>
    <?if (!empty($classesMarks)):?>
    <div class="row scroll shadow" id="mouseScrolling">
        <form method="post" class="shedForm" id="journalList">
            <table class="table table-sm table-content table-bordered">
                <thead >
                <tr>
                    <th scope="col" class="left" style="text-align: center">День</th>
                    <?foreach ($classesHeads as $day=>$week):?>
                        <th scope="col"  class="left mark_elem" style="text-align: center"><?=$day." (".$week.")"?></th>
                    <?endforeach;?>
                </tr>
                </thead>
                <tbody>
                <?foreach ($classesMarks as $idClass=>$oneClass):?>
                    <tr id="<?=$idClass?>">
                        <td class="left">
                            <?=$oneClass['NAME']?>
                        </td>
                        <?foreach ($oneClass['DATES'] as $idDay=>$dayMarks):?>
							<td class="left">
							<?foreach ($dayMarks['marks'] as $oneMark):?>
									<p class="studmark" title="<?=$dayMarks['data_content']?>"><?=$oneMark[1].' | '.$oneMark[0]?></p>
                            <?endforeach;?>
							</td>
                        <?endforeach;?>
                    </tr>
                <?endforeach;?>
                </tbody>
            </table>
        </form>
    </div>
    <?else:?>
    <div class="center">
        <img src="../assets/loadingNew.gif" width="150px">
        <h3>Нет оценок на неделе</h3></br>
    </div>
    <?endif;?>
    <div class="row page_navigator center" <?=$allQuar?'hidden':''?>>
        <span> Чет. №<?=$quarters['now']['num']?>, неделя <?=$pageNum?>/<?=$colWeek?></span>
        <?php for($i=0;$i<$colWeek;$i++):?>
            <a id="<?=($i+1)?>" class="<?=$pageNum==($i+1)?'active':''?>" onclick="page_loader(this)"><?=($i+1)?></a>
        <?php endfor;?>
    </div>
    <script>
		function page_loader(elem) {
			$('#diary').load("<?=ROOT?>diary/diary.php?id=<?=$studentID?>&QUAR=<?=$quarterNum?>&PAGE="+$(elem).attr("id"),function () {
				//$("#loading").remove();
			});
		}
		function quar_loader(elem) {
            if ($(elem).attr('data-action')=='allQuar'){
				$('#diary').load("<?=ROOT?>diary/diary.php?id=<?=$studentID?>&QUAR="+$(elem).attr("id")+"&allQuar=Y");
            } else {
				$('#diary').load("<?=ROOT?>diary/diary.php?id=<?=$studentID?>&QUAR=" + $(elem).attr("id") + "&PAGE=1");
			}
		}
    </script>
<?php
function getCurrentSection($studentID){
    return database::getInstance()->query('select classes_id from students_groups where student_id='.$studentID)[0]['classes_id'];
}
function getIBlockSections($id)
{
    $sect=[];
    $obSection = CIBlockSection::GetTreeList(['IBLOCK_ID' => $id]);
    while ($arResult = $obSection->GetNext()) {
        $sect[$arResult['ID']]= $arResult['NAME'];
    }
    return $sect;
}
function getQuarters(&$quarterNum){
    $req=database::getInstance()->query("select * from quarters where now='Y' order by year asc")[0];
    $format='d.m.Y';
    $result=[
        [
            's'=>date($format,strtotime($req['start1'])),
            'f'=>date($format,strtotime($req['finish1']))
        ],
        [
            's'=>date($format,strtotime($req['start2'])),
            'f'=>date($format,strtotime($req['finish2']))
        ],
        [
            's'=>date($format,strtotime($req['start3'])),
            'f'=>date($format,strtotime($req['finish3']))
        ],
        [
            's'=>date($format,strtotime($req['start4'])),
            'f'=>date($format,strtotime($req['finish4']))
        ],
    ];
    $now=time();
    if ($quarterNum){
        $result['now']=[
            's'=>$result[$quarterNum-1]['s'],
            'f'=>$result[$quarterNum-1]['f'],
            'num'=>($quarterNum),
        ];
    } else {
        $result['now'] = [
            's'=>$result[0]['s'],
            'f'=>$result[0]['f'],
            'num'=>1,
        ];
        foreach ($result as $num=>$oneQuarter){
            if (strtotime($oneQuarter['s'])<=$now && strtotime($oneQuarter['f'])>=$now){
                $result['now']=[
                    's'=>$oneQuarter['s'],
                    'f'=>$oneQuarter['f'],
                    'num'=>($num+1),
                ];
                $quarterNum=($num+1);
                break;
            }
        }
    }
    $result['edit']=strtotime($result['now']['s'])<=$now && strtotime($result['now']['f'])>=$now;
    return $result;
}
function getQuartersDays($q){
    $arr=[];
    $date=(new DateTime($q['s']));
    while ($q['f']!=$date->format('d.m.Y')){
        if (!in_array($date->format('N'),[6,7])) $arr[]=$date->format('d.m.Y');
        $date->modify("+1 day");
    }
    if (!in_array($date->format('N'),[6,7])) $arr[]=$date->format('d.m.Y');
    return $arr;
}
function getWeek($quarters,&$pageNum){
    $date = new DateTime();
    if (!$pageNum) {
        $num = $date->format('N');
        if ($num != 1) {
            $pn = $date->modify('-' . ($num - 1) . ' days')->format('d.m.Y');
        } else {
            $pn = $date->format('d.m.Y');
        }
        $pt = $date->modify('+4 days')->format('d.m.Y');
        $daysInQ = getQuartersDays($quarters['now']);
        //проверка на выход из учебного года
        //если кончился
        if (strtotime($pn)>strtotime($daysInQ[count($daysInQ)-1])){
            $num=$date->setTimestamp(strtotime($daysInQ[count($daysInQ)-1]))->format('N');
            if ($num != 1) {
                $pn = $date->modify('-' . ($num - 1) . ' days')->format('d.m.Y');
            } else {
                $pn = $date->format('d.m.Y');
            }
            $pt = $date->modify('+4 days')->format('d.m.Y');
        }
        //если еще не начался
        if (strtotime($pt)<strtotime($daysInQ[0])){
            $num=$date->setTimestamp(strtotime($daysInQ[0]))->format('N');
            if ($num != 1) {
                $pn = $date->modify('-' . ($num - 1) . ' days')->format('d.m.Y');
            } else {
                $pn = $date->format('d.m.Y');
            }
            $pt = $date->modify('+4 days')->format('d.m.Y');
        }
        //
        $numDay = array_search($pn, $daysInQ) + 1;
        $numWeek = (int)round($numDay / 5);
        if (($numDay % 5) !== 0)
            $numWeek++;
        $pageNum=$numWeek;
    } else {
        $num=$pageNum==1?1:$pageNum+(($pageNum-1)*4);
        $daysInQ = getQuartersDays($quarters['now']);
        $pn=$daysInQ[$num-1];
        $pt=$date->setTimestamp(strtotime($pn))->modify('+4 days')->format('d.m.Y');
    }
    return [$pn, $pt];
}
function switchWeek($num){
    if ($num==1) return 'пн';
    if ($num==2) return 'вт';
    if ($num==3) return 'ср';
    if ($num==4) return 'чт';
    if ($num==5) return 'пт';
    return 'н\а';
}
