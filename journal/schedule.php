<?php
session_start();
define('ROOT', '../');
require_once(ROOT . 'classes/sentry.php');
require_once (ROOT.'database/database.php');
$db=database::getInstance();

if (empty($_REQUEST['id'])) die('не верное занятие');
else {
    $class=$_REQUEST['id'];
    $className=$db->query('select name from subjects where id='.$class)[0]['name'];
}
if (isset($_REQUEST['PAGE'])) $pageNum=$_REQUEST['PAGE'];
if (isset($_REQUEST['QUAR'])) $quarterNum=$_REQUEST['QUAR'];
$userGr=$_SESSION['user']['groups'];
$section=getCurrentSection();
$quarters=getQuarters($quarterNum);
$endOfYear = strtotime($quarters[3]['f'])<time();
$currentWeek=getWeek($quarters,$pageNum);
$quarters['edit']=strtotime($currentWeek[0])<=time() && strtotime($currentWeek[1])>=time();
if (in_array(1,$userGr)) $quarters['edit']=true;
$calendar=getCalendarData($quarters['now'],$currentWeek);
$nowCalendar=$calendar[1];
$calendar=$calendar[0];
$students=getStudents();
if (checkExist($quarters,$section,$class)<=0) {
    addInQuarter($quarters,$section,$class);
}
$q = "from journal where group_id={$section} and subject={$class}";
$date = " and date_int>=".strtotime($quarters['now']['s'])." and date_int<=".strtotime($quarters['now']['f']);
if (!in_array(1,$userGr) && !in_array(12,$userGr)){//не админ и без доступа к кл.журналу
    $q.=' and teacher='.$_SESSION['user']['id'];
}
$countClassesInQuarter=$db->query("select count(*) ".$q.$date)[0]['count(*)'];
$colWeek=(int)round($countClassesInQuarter/5,PHP_ROUND_HALF_DOWN);
if (($countClassesInQuarter%5)!==0)$colWeek++;
$date2 = " and date_int>=".strtotime($currentWeek[0])." and date_int<=".strtotime($currentWeek[1]);
$res=$db->query("select * ".$q.$date2." order by date_int asc");
$journalHeads=[];
$classesBody=[];
$journalIds=[];
$classesMarks=[];
$JournalTypesHead=[];
$dayWeek=new DateTime();
foreach ($res as $obj){
    $JournalTypesHead[$obj['id']]=[];
    $journalHeads[]=[
        'id'=>$obj['id'],
        'day'=>$obj['date'],
		'week'=>switchWeek($dayWeek->setTimestamp($obj['date_int'])->format('N')),
		'theme'=>$obj['theme'],
		'comment'=>$obj['comment'],
		'types_marks'=>[],
    ];
    $journalIds[]=$obj['id'];
}
$q='select marks.subject_id,marks.journal_id,marks.type_id,marks.student_id,marks.id,marks.mark, type_marks.short'.
    ' from marks inner join type_marks on marks.type_id = type_marks.id '.
    'where subject_id='.$_REQUEST['id']." and journal_id in ('".implode("','",$journalIds)."')".
    "and student_id in ('".implode("','",array_keys($students))."') order by date_create asc";
$req=$db->query($q);
foreach ($req as $obj){
    $studID=$obj['student_id'];
    if (!isset($classesMarks[$studID]))$classesMarks[$studID]=[];
    if (!isset($classesMarks[$studID]))$classesMarks[$studID][$obj['journal_id']]=[];
    $classesMarks[$studID][$obj['journal_id']][]=[
    		$obj['type_id'],
        	$obj['mark'],
			$obj['id'],
        	$obj['short']
		];
}
if (!empty($classesMarks)) {
    foreach ($students as $firstStud => $none) {
        break;
    }
    foreach ($classesMarks[$firstStud] as $journalId => $oneJournal) {
        foreach ($oneJournal as $oneMark) {
            $JournalTypesHead[$journalId][] = $oneMark[3];
        }
    }
}
$predMonth=getPredNextMonth($calendar,$nowCalendar,$quarters,-1);
$predMonth_week=$predMonth[0];
$predMonth_quarter=$predMonth[1];

$nexMonth=getPredNextMonth($calendar,$nowCalendar,$quarters,1);
$nexMonth_week=$nexMonth[0];
$nexMonth_quarter=$nexMonth[1];
?>
<div class="row center headText" >
	<div id="<?=$quarters['now']['num']==1?1:($quarters['now']['num']-1)?>" onclick="quar_loader(this)" class="arrow-btn" style="cursor: pointer;display: initial;"><i class="arrow leftArrow" style="border: solid red;border-width: 0 3px 3px 0;"></i></div>
	<span class="quarter-name"><b><?=isset($_REQUEST['QUAR'])?'Выбранная':'Текущая'?><span class="text-red"> четверть №<?=$quarters['now']['num']?></span> с <?=$quarters['now']['s']?> по <?=$quarters['now']['f']?></b></span>
	<div id="<?=$quarters['now']['num']==4?4:($quarters['now']['num']+1)?>" onclick="quar_loader(this)" class="arrow-btn" style="cursor: pointer;display: initial;"><i class="arrow rightArrow" style="border: solid red;border-width: 0 3px 3px 0;"></i></div>
</div>
	<div class="dropdown-divider"></div>
<div class="row week-block">
	<div class="col-md center calendarTextLeft">
		</br></br>
		<p><b><?=isset($_REQUEST['PAGE'])?'Выбранна':'Текущая'?> учебная неделя в данной четверти - №<?=$pageNum?></b></p>
		<p><?=$currentWeek[0]?> - <?=$currentWeek[1]?></p>
	<span><b>Предмет:  </b><?=$className?></span></br></br>
		<?if($quarters['edit']):?>
			<button class="btn btn-danger save-btn" form="journalList" <?=$quarters['edit']?'':'disabled'?>>Сохранить</button>
		<?else:?>
			<button class="btn btn-danger" onclick="back_on_week()" <?=$endOfYear?'disabled':''?>><?=$endOfYear?'Год закончился':'Вернуться на сегодня'?></button>
        <?endif?>
	</div>
	<div class="col-md center">
		<!-- calendar  -->
		<?if (isset($calendar[$nowCalendar['Y']]) && isset($calendar[$nowCalendar['Y']][$nowCalendar['m']])):
	$mCalendar=$calendar[$nowCalendar['Y']][$nowCalendar['m']]?>
<table class="calendar" id="calendar">
	<thead>
	<tr>
		<th id="<?=$predMonth_week?>" data-content="<?=$predMonth_quarter?>" onclick="pageNquarter_loader(this)" class="arrow-btn"><i class="arrow leftArrow" style="border: solid red;border-width: 0 3px 3px 0;"></i></th>
		<th colspan="5"><?=switchMonth($nowCalendar['m']).' '.$nowCalendar['Y']?></th>
		<th id="<?=$nexMonth_week?>" data-content="<?=$nexMonth_quarter?>" onclick="pageNquarter_loader(this)" class="arrow-btn"><i class="arrow rightArrow" style="border: solid red;border-width: 0 3px 3px 0;"></th>
	</tr>
	<tr>
		<th></th>
		<th>пн</th><th>вт</th><th>ср</th><th>чт</th><th>пт</th>
		<th></th>
	</tr>
	</thead>
	<tbody>
<?foreach ($mCalendar as $weekNum=>$oneWeek):?>
	<tr <?=$weekNum==$nowCalendar['w']?'style="background-color: #83be8c;"':''?> id="<?=$weekNum?>" onclick="page_loader(this)">
		<td></td>
        <?foreach ($oneWeek as $oneDay):?>
		<td>
			<?=$oneDay?>
		</td>
        <?endforeach;?>
		<td></td>
	</tr>
<?endforeach;?>
	</tbody>
</table>
<?endif;?>
		<!-- calendar  -->
	</div>
</div>
</br>
	<div class="dropdown-divider"></div>
<div class="row text-red"> * кликните на день, а потом на список сокращений, чтобы добавить оценку.</div>
<div class="row scroll shadow" id="mouseScrolling">
    <form method="post" class="shedForm" id="journalList">
		<input name="subject" value="<?=$_REQUEST['id']?>" hidden>
            <table class="table table-sm table-content">
                <thead >
                <tr>
                    <th scope="col" class="left align-content-center">Учащиеся</th>
                    <?foreach ($journalHeads as $ind=> $oneClass):
						$colspan=count($JournalTypesHead[$oneClass['id']])?>
                    <th scope="col" id="head_<?=$ind?>"
						colspan="<?=$colspan==0?1:$colspan?>"
						data-content="<?=$ind?>"
						journalId="<?=$oneClass['id']?>"
						class="left date-head"
						onclick="setActive(this);">
						<?=$oneClass['day']." (".$oneClass['week'].")"?>
					</th>
                    <?endforeach;?>
                </tr>
				<tr>
					<th class="left" id="first_sokr">Сокращение</th>
					<?foreach ($journalHeads as $ind=> $oneClass):?>
                    	<?
                        $indType=0;
						foreach ($JournalTypesHead[$oneClass['id']] as $none=>$name):?>
						<th scope="col" id="head_<?=$ind?>_<?=$indType?>" class="left align-content-center"><?=$name?></th>
                        <?$indType++;
						endforeach;
                        if ($indType==0):?>
						<th scope="col" id="head_<?=$ind?>_<?=$indType?>" class="left align-content-center"></th>
						<?endif?>
						<script>
							window.arrHead.push(<?=$indType==0?0:$indType?>);
						</script>
                    <?endforeach;?>
				</tr>
                </thead>
                <tbody>
                <?
				$indStud=0;
				foreach ($students as $id_stud=>$oneStudent):?>
				<script>
					window.studentIds.push(<?=$id_stud?>);
				</script>
                    <tr id="<?=$id_stud?>">
                        <td class="left">
                            <?=$oneStudent?>
                        </td>
                        <?foreach ($journalHeads as $ind=> $oneClass):?>
                            <?
                            $indMark=0;
                            $markWas=false;
                            if (isset($classesMarks[$id_stud]) && isset($classesMarks[$id_stud][$oneClass['id']])) {
                                foreach ($classesMarks[$id_stud][$oneClass['id']] as $indMark => $oneMark):
                                    $markWas = true; ?>
									<td class="left" id="row_<?=$indStud?>_day_<?=$ind?>_<?=$indMark?>">
										<input id="mark_<?=$oneClass['id']?>_<?=$id_stud?>_<?=$oneMark[2]?>"
											   class="mark"
                                            <?=$quarters['edit'] ? '' : 'disabled'?>
											   value="<?=$oneMark[1]?>"
											   name="marks[<?=$oneClass['id']?>][<?=$id_stud?>][<?=$oneMark[2]?>][mark]"
										>
									</td>
                                <?endforeach;
                            }
                            if (!$markWas):?>
								<td class="left" id="row_<?=$indStud?>_day_<?=$ind?>_<?=$indMark?>">
								</td>
							<?endif;?>
                        <?endforeach;?>
                    </tr>
                <?$indStud++;
				endforeach;?>
				<tr>
					<td class="left text-red"><b>Тема урока: </b></td>
                <?foreach ($journalHeads as $ind=> $oneClass):
                    $colspan=count($JournalTypesHead[$oneClass['id']])?>
					<td class="left" id="theme_<?=$ind?>" colspan="<?=$colspan==0?1:$colspan?>">
							<input id="theme_<?=$ind?>_input"
								   class="theme"  style="width: <?=$colspan*50+75?>px;"
                                <?=$quarters['edit']?'':'disabled'?>
								   value="<?=$oneClass['theme']?>"
								   name="day[<?=$oneClass['id']?>][theme]"
							>
					</td>
                <?endforeach;?>
				</tr>
				<tr>
					<td class="left text-red"><b>Комментарий к уроку: </b></td>
                    <?foreach ($journalHeads as $ind=> $oneClass):
                        $colspan=count($JournalTypesHead[$oneClass['id']])?>
						<td class="left" id="comment_<?=$ind?>" colspan="<?=$colspan==0?1:$colspan?>">
							<textarea id="comment_<?=$ind?>_textarea"
								   class="comment"
									  rows="5"
									  style="width: <?=$colspan*40+65?>px;"
                                <?=$quarters['edit']?'':'disabled'?>
								   name="day[<?=$oneClass['id']?>][comment]"
							><?=$oneClass['comment']?></textarea>
						</td>
                    <?endforeach;?>
				</tr>
                </tbody>
            </table>
    </form>
</div>

<div class="row page_navigator center">
    <span> Чет. №<?=$quarters['now']['num']?>, неделя <?=$pageNum?>/<?=$colWeek?></span>
    <?php for($i=0;$i<$colWeek;$i++):?>
    <a id="<?=($i+1)?>" class="<?=$pageNum==($i+1)?'active':''?>" onclick="page_loader(this)"><?=($i+1)?></a>
    <?php endfor;?>
</div>
<script>
	window.masseditMarks='<?=(string)$quarters['edit']?>';
	var colRows=<?=count($students)?>;
	function setActive(elem) {
		if (window.activ_elem_type!==null){
			window.activ_elem_type.css('color','black');
		}
		window.activ_elem_type=$(elem);
		activ_elem_type_ind=parseInt(window.activ_elem_type.attr('data-content'));
		window.activ_elem_type.css('color','red');
	}
	function pageNquarter_loader(elem) {
		obnul();
		$('#journal').load("<?=ROOT?>/journal/schedule.php?id=<?=$_REQUEST['id']?>&classes=<?=$_REQUEST['classes']?>&QUAR="+$(elem).attr("data-content")+"&PAGE="+$(elem).attr("id"),function () {
			//$("#loading").remove();
		});
	}
	function page_loader(elem) {
		obnul();
        $('#journal').load("<?=ROOT?>/journal/schedule.php?id=<?=$_REQUEST['id']?>&classes=<?=$_REQUEST['classes']?>&QUAR=<?=$quarterNum?>&PAGE="+$(elem).attr("id"),function () {
        //$("#loading").remove();
        });
	}
	function quar_loader(elem) {
		obnul();
		$('#journal').load("<?=ROOT?>/journal/schedule.php?id=<?=$_REQUEST['id']?>&classes=<?=$_REQUEST['classes']?>&QUAR="+$(elem).attr("id")+"&PAGE=1",function () {
			//$("#loading").remove();
		});
	}
	function back_on_week(){
		let journalElem=$("#journal");
		loadingShow(journalElem);
		obnul();
		journalElem.load("<?=ROOT?>/journal/schedule.php?id=<?=$_REQUEST['id']?>&classes=<?=$_REQUEST['classes']?>",function () {
			// $("#loading").remove();
		});
	}
	$( "#journalList" ).submit(function( event ){
		event.preventDefault();
		var strData= $( "#journalList" ).serialize();
		$.post( "<?=ROOT?>/journal/index.php", $( "#journalList" ).serialize() ,function( data ) {
			if (data=='success'){
				obnul();
				$('#journal').load("<?=ROOT?>/journal/schedule.php?id=<?=$_REQUEST['id']?>&classes=<?=$_REQUEST['classes']?>");
				alert( "Успешно сохранено" );
			}
		});
	});
</script>
<?php
function getCurrentSection(){
    $section = database::getInstance()->query('SELECT group_id from subjects where id='.$_REQUEST['classes'])[0]['group_id'];
    if ($section<0) die('ошибка раздела журнала');
    return $section;
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
function addInQuarter($quarters,$section,$class){
    $days=array_merge(getQuartersDays($quarters[0]),getQuartersDays($quarters[1]),getQuartersDays($quarters[2]),getQuartersDays($quarters[3]));
    $db = database::getInstance();
    $ClassElem=$db->query('select name,teacher_id from subjects where id='.$class)[0];
    $nameClasses=$ClassElem['name'];
    foreach ($days as $oneDay){
        $PROP = Array(
            'subject'=>$class,
            "group_id" => $section,
			'date_int'=>strtotime($oneDay),
			'date'=>$oneDay,
            'teacher' => $ClassElem['teacher_id'],
			'name' =>$oneDay.' '.$nameClasses,
			'theme'=>'',
			'comment'=>'',
        );
        $q="insert into journal (subject, group_id, date_int, date, teacher, name, theme, comment) values ('".implode("','",$PROP)."')";
    	$db->query($q);
    }
}
function getStudents($allSection=false){
	//ToDo таблица юзер-студент-класс. взять студентов по классу из реквеста.
	$res = database::getInstance()->query("select users.id, users.name from users inner join students_groups sg on users.id = sg.student_id where sg.classes_id={$_REQUEST['classes']}");
	$students=[];
    foreach ($res as $oneStud) {
        $students[$oneStud['id']]="{$oneStud['name']}";
    }
    return $students;
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
function checkExist($quarters,$section,$id){
    $res = database::getInstance()->query("select * from journal where date_int=".strtotime($quarters[0]['s']).' and group_id='.$section.' and subject='.$id);
    return count($res);
}
function getQuartersDays($q,$calendar=false){
    $arr=[];
    $date=(new DateTime($q['s']));
    while ($q['f']!=$date->format('d.m.Y')){
        if (!in_array($date->format('N'),[6,7])){
        	if ($calendar){
        		$tmp=$date->format('d.m.Y');
        		$dmY=explode('.',$tmp);
                $arr[] = [
                    'date'=>$tmp,
					'd'=>$dmY[0],
					'm'=>$dmY[1],
					'Y'=>$dmY[2],
					'N'=>$date->format('N')
				];
			} else {
                $arr[] = $date->format('d.m.Y');
            }
		}
        $date->modify("+1 day");
    }
    if (!in_array($date->format('N'),[6,7])) {
        if ($calendar) {
            $tmp=$date->format('d.m.Y');
            $dmY=explode('.',$tmp);
            $arr[] = [
                'date'=>$tmp,
                'd'=>$dmY[0],
                'm'=>$dmY[1],
                'Y'=>$dmY[2],
                'N'=>$date->format('N')
            ];
        } else {
            $arr[] = $date->format('d.m.Y');
        }
	}
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
function getCalendarData($quarter,$week){
    $days=getQuartersDays($quarter,true);
    $calendar=[];
    $weekNum=0;
    $monthNum=-1;
    $ind=0;
    $now=['m'=>'','Y'=>'','w'=>''];
    $weekSdvigFlag=false;
    foreach ($days as $key=>$oneDay){
    	if ($monthNum!=$oneDay['m']){
    		if ($weekNum!=0) {
                if ($days[$key - 1]['N'] != 5) {
                    $weekSdvigFlag=true;
                    for ($i = 0; $i < (5 - $days[$key - 1]['N']); $i++) {
                        $calendar[$days[$key - 1]['Y']][$days[$key - 1]['m']][$weekNum][] = '';
                        $ind++;
                    }
                }
            }
		}
        if ($ind%5==0 && !$weekSdvigFlag) $weekNum++;
        $weekSdvigFlag=false;
        if ($week[0]==$oneDay['date']) {
            $now=[
            	'm'=>$oneDay['m'],
				'Y'=>$oneDay['Y'],
				'w'=>$weekNum
            	];
		}
        if (!isset($calendar[$oneDay['Y']])) $calendar[$oneDay['Y']]=[];
        if (!isset($calendar[$oneDay['Y']][$oneDay['m']])) $calendar[$oneDay['Y']][$oneDay['m']]=[];
        if (!isset($calendar[$oneDay['Y']][$oneDay['m']][$weekNum])) $calendar[$oneDay['Y']][$oneDay['m']][$weekNum]=[];
        if ($monthNum!=$oneDay['m']){
            if ($oneDay['N']!=1){
                for ($i=0; $i<($oneDay['N']-1);$i++){
                    $calendar[$oneDay['Y']][$oneDay['m']][$weekNum][]='';
                    $ind++;
                }
            }
            $monthNum=$oneDay['m'];
        }
        $ind++;
        $calendar[$oneDay['Y']][$oneDay['m']][$weekNum][]=$oneDay['d'];
	}
	return [$calendar,$now];
}
function switchWeek($num){
    if ($num==1) return 'пн';
    if ($num==2) return 'вт';
    if ($num==3) return 'ср';
    if ($num==4) return 'чт';
    if ($num==5) return 'пт';
    return 'н\а';
}
function switchMonth($num){
    if ($num==1) return 'Январь';
    if ($num==2) return 'Февраль';
    if ($num==3) return 'Март';
    if ($num==4) return 'Апрель';
    if ($num==5) return 'Май';
    if ($num==6) return 'Июнь';
    if ($num==7) return 'Июль';
    if ($num==8) return 'Август';
    if ($num==9) return 'Сентябрь';
    if ($num==10) return 'Октябрь';
    if ($num==11) return 'Ноябрь';
    if ($num==12) return 'Декабрь';
    return 'н\а';
}
function getPredNextMonth($calendar,$nowCalendar,$quarters,$num){
    $predMonth=((int)$nowCalendar['m']+$num)>=10?(int)$nowCalendar['m']+$num:'0'.((int)$nowCalendar['m']+$num);
	if (isset($calendar[$nowCalendar['Y']][$predMonth])){
        $arrMonth=$calendar[$nowCalendar['Y']][$predMonth];
	} else {
        $arrMonth=$calendar[$nowCalendar['Y']][$nowCalendar['m']];
    }
    foreach($calendar[$nowCalendar['Y']] as $key => $unused) {
        $firstMonth = $key;
        break;
    }
    foreach($calendar[$nowCalendar['Y']] as $key => $unused) {
        $lastMonth = $key;
    }
    if ($nowCalendar['m']==$lastMonth && $num==1){
        $numQuarter=$quarters['now']['num']==4?1:++$quarters['now']['num'];
        $calendarDop=getCalendarData($quarters[$numQuarter-1],[0=>''])[0];
        foreach($calendarDop as $year => $unusedY) {
        	$dopYearFirst=$year;
            foreach($unusedY as $month => $unusedM) {
                $dopMonthFirst=$month;
                foreach($unusedM as $week => $unusedW) {
                    $dopWeekFirst=$week;
                    break;
                }
                break;
            }
            break;
        }
        return [$dopWeekFirst,$numQuarter];
	}

    if ($nowCalendar['m']==$firstMonth && $num==-1){
        $numQuarter=$quarters['now']['num']==1?4:--$quarters['now']['num'];
        $calendarDop=getCalendarData($quarters[$numQuarter-1],[0=>''])[0];
        foreach($calendarDop as $year => $unusedY) {
            $dopYearLast=$year;
            foreach($unusedY as $month => $unusedM) {
                $dopMonthLast=$month;
                foreach($unusedM as $week => $unusedW) {
                    $dopWeekLast=$week;
                }
            }
            break;
        }
        return [$dopWeekLast,$numQuarter];
    }
    if ($predMonth==$firstMonth){
        foreach($arrMonth as $weekNum => $unused) {
            return [$weekNum,$quarters['now']['num']]; //firstWeekInMonth
        }
	} else {
    	foreach ($calendar[$nowCalendar['Y']][$firstMonth] as $weekNumInFirstMonth=>$unused){}
        foreach($arrMonth as $weekNum => $unused) {
            $firstWeekInMonth = $weekNum; //firstWeekInMonth
			break;
        }
        if ($weekNumInFirstMonth==$firstWeekInMonth){
        	return [++$firstWeekInMonth,$quarters['now']['num']];
		} else {
            return [$firstWeekInMonth,$quarters['now']['num']];
		}
	}
    return 1;
}
