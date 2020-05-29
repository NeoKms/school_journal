<?php
include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
use DateTime;
if(!CModule::IncludeModule("iblock")) die('ошибка битрикс');
if (empty($_REQUEST['id'])) die('не верное занятие');
else {
    $class=$_REQUEST['id'];
}
if (isset($_REQUEST['PAGE'])) $pageNum=$_REQUEST['PAGE'];
if (isset($_REQUEST['QUAR'])) $quarterNum=$_REQUEST['QUAR'];
$userGr=$USER->GetUserGroupArray();
$section=getCurrentSection();
$quarters=getQuarters($quarterNum);
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
$elem=new CIBlockElement();
$filterAll=[
    'IBLOCK_ID'=>12,
    'SECTION_ID'=>$section,
    'PROPERTY_SUBJECT'=>$class,
    ["LOGIC"=>'AND',
        ['>=PROPERTY_EVENT_LENGTH'=>strtotime($quarters['now']['s'])],
        ['<=PROPERTY_EVENT_LENGTH'=>strtotime($quarters['now']['f'])],
    ]
];
if (!in_array(1,$userGr) && !in_array(12,$userGr)){//не админ и без доступа к кл.журналу
    $filterAll['PROPERTY_TEACHER']=$USER->GetID();
}
$countClassesInQuarter=\CIBlockElement::GetList([],$filterAll,[]);
$colWeek=(int)round($countClassesInQuarter/5,PHP_ROUND_HALF_DOWN);
if (($countClassesInQuarter%5)!==0)$colWeek++;
$filterWeek=$filterAll;
$filterWeek[0]=[
    ["LOGIC"=>'AND',
        ['>=PROPERTY_EVENT_LENGTH'=>strtotime($currentWeek[0])],
        ['<=PROPERTY_EVENT_LENGTH'=>strtotime($currentWeek[1])],
    ]
];
$req=$elem::GetList(
    ['PROPERTY_EVENT_LENGTH'=>'ASC'],
    $filterWeek,
    false,false,
    [
        'ID',
        "NAME",
        'IBLOCK_SECTION_ID',
        'PROPERTY_TEACHER',
        'PROPERTY_SUBJECT',
        'PROPERTY_DATE_CLASS',
        'PROPERTY_LESSON_THEME',
        'PROPERTY_EVENT_LENGTH',
		'PROPERTY_COMMENT_CLASS'
    ]);
$classesHeads=[];
$classesBody=[];
$classesIds=[];
$classesMarks=[];
$JournalTypesHead=[];
$dayWeek=new DateTime();
while($obj=$req->Fetch()){
    $JournalTypesHead[$obj['ID']]=[];
    $classesHeads[]=[
        'id'=>$obj['ID'],
        'day'=>$obj['PROPERTY_DATE_CLASS_VALUE'],
		'week'=>switchWeek($dayWeek->setTimestamp($obj['PROPERTY_EVENT_LENGTH_VALUE'])->format('N')),
		'theme'=>$obj['PROPERTY_LESSON_THEME_VALUE'],
		'comment'=>$obj['PROPERTY_COMMENT_CLASS_VALUE'],
		'types_marks'=>[],
    ];
    $classesIds[]=$obj['ID'];
}
$req=$elem::GetList(['DATE_CREATE'=>"ASC"],
	['IBLOCK_ID'=>21,'PROPERTY_SUBJECT'=>$_REQUEST['id'],'PROPERTY_CLASS'=>$classesIds,'PROPERTY_STUDENT'=>array_keys($students)],
	false,false,
	['PROPERTY_CLASS','PROPERTY_STUDENT','PROPERTY_TYPE','PROPERTY_MARK','ID','PROPERTY_TYPE.PROPERTY_SHORT']);
while($obj=$req->Fetch()){
    $studID=$obj['PROPERTY_STUDENT_VALUE'];
    if (!isset($classesMarks[$studID]))$classesMarks[$studID]=[];
    if (!isset($classesMarks[$studID]))$classesMarks[$studID][$obj['PROPERTY_CLASS_VALUE']]=[];
    $classesMarks[$studID][$obj['PROPERTY_CLASS_VALUE']][]=[
    		$obj['PROPERTY_TYPE_VALUE'],
        	$obj['PROPERTY_MARK_VALUE'],
			$obj['ID'],
        	$obj['PROPERTY_TYPE_PROPERTY_SHORT_VALUE']
		];
}
foreach ($students as $firstStud=>$none){break;}
foreach ($classesMarks[$firstStud] as $journalId=>$oneJournal){
	foreach ($oneJournal as $oneMark) {
        $JournalTypesHead[$journalId][] = $oneMark[3];
    }
}

$predMonth=getPredNextMonth($calendar,$nowCalendar,$quarters,-1);
$predMonth_week=$predMonth[0];
$predMonth_quarter=$predMonth[1];

$nexMonth=getPredNextMonth($calendar,$nowCalendar,$quarters,1);
$nexMonth_week=$nexMonth[0];
$nexMonth_quarter=$nexMonth[1];

?>
<style>
    i {
        border: solid white;
        border-width: 0 3px 3px 0;
        display: inline-block;
        padding: 3px;
    }
    .save-btn{
        margin: 0 20px 0 20px;
    }
    .rightArrow {
        transform: rotate(-45deg);
        -webkit-transform: rotate(-45deg);
    }

    .leftArrow {
        transform: rotate(135deg);
        -webkit-transform: rotate(135deg);
    }
    .page_border{
        border: none;
    }
    .table-content tbody tr:hover{
        background-color: #e2e0e0;
    }
    .shedForm{
        margin-left: 20px
    }
    .table-content thead{
        background-color: #a7d5d9
    }
	.calendar, .calendarTextLeft{
		margin-left: 50px;
		margin-right: 50px;
	}
	.calendar td {
		padding: 6px;
	}
	.calendar tbody tr:hover{
		background-color: #e2e0e0;
		cursor: pointer;
	}
    .center {
        text-align: center;
    }
    .scroll {
        overflow: auto;
    }
    .page_navigator a{
        cursor: pointer;
        padding: 4px;
        margin-left: 4px;
    }
    .page_navigator a.active, .page_navigator span {
        color: red;
    }
    .page_navigator{
        margin: 10px;
    }
	.table-content .mark {
		width: 20px;
		font-size: 9pt;
	}
	.table-content .theme {
		 width: 75px;
		 font-size: 9pt;
	 }
	.table-content .comment {
		width: 65px;
		font-size: 9pt;
	}
    .left {
        border-right: 2px solid #bebebe;
		text-align: center !important;
    }
    .shadow {
        box-shadow: 0 0 10px rgba(0,0,0,0.5);
        overflow-y: hidden;
    }
    .headText {
        margin-top: 20px;
    }
</style>
<div class="row center headText" >
	<button class="btn btn-primary" onclick="quar_loader(this)" style="padding: 2px;"
			id="<?=$quarterNum==1?1:($quarterNum-1)?>">
		<i class="arrow leftArrow"></i> четверть</button>
	<span style="font-size: 12pt"><b><?=isset($_REQUEST['QUAR'])?'Выбранная':'Текущая'?><span style="color: red"> четверть №<?=$quarters['now']['num']?></span> с <?=$quarters['now']['s']?> по <?=$quarters['now']['f']?></b></span>
	<button class="btn btn-primary" onclick="quar_loader(this)" style="padding: 2px;"
			id="<?=$quarterNum==4?4:($quarterNum+1)?>">
		четверть <i class="arrow rightArrow"></i></button></br></br>
</div>
<div class="row" style="display: flex;margin-left: 20px">
	<div class="col-md center calendarTextLeft">
		</br></br>
		<p><b><?=isset($_REQUEST['PAGE'])?'Выбранна':'Текущая'?> учебная неделя в данной четверти - №<?=$pageNum?></b></p>
		<p><?=$currentWeek[0]?> - <?=$currentWeek[1]?></p>
	<span><b>Предмет:  </b><?=CIBlockElement::GetByID($class)->Fetch()['NAME']?></span></br></br>
		<?if($quarters['edit']):?>
			<button class="btn btn-danger save-btn" form="journalList" <?=$quarters['edit']?'':'disabled'?>>Сохранить</button>
		<?else:?>
			<button class="btn btn-danger" onclick="back_on_week()">Вернуться на сегодня</button>
        <?endif?>
	</div>
	<div class="col-md center">
		<!-- calendar  -->
		<?if (isset($calendar[$nowCalendar['Y']]) && isset($calendar[$nowCalendar['Y']][$nowCalendar['m']])):
	$mCalendar=$calendar[$nowCalendar['Y']][$nowCalendar['m']]?>
<table class="calendar" id="calendar">
	<thead>
	<tr>
		<th id="<?=$predMonth_week?>" data-content="<?=$predMonth_quarter?>" onclick="pageNquarter_loader(this)" style="cursor: pointer"><i class="arrow leftArrow" style="border: solid red;border-width: 0 3px 3px 0;"></i></th>
		<th colspan="5"><?=switchMonth($nowCalendar['m']).' '.$nowCalendar['Y']?></th>
		<th id="<?=$nexMonth_week?>" data-content="<?=$nexMonth_quarter?>" onclick="pageNquarter_loader(this)" style="cursor: pointer"><i class="arrow rightArrow" style="border: solid red;border-width: 0 3px 3px 0;"></th>
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
<div class="row scroll shadow" id="mouseScrolling">
    <form method="post" class="shedForm" id="journalList">
		<input name="subject" value="<?=$_REQUEST['id']?>" hidden>
            <table class="table table-sm table-content">
                <thead >
                <tr>
                    <th scope="col" class="left" style="text-align: center">Учащиеся</th>
                    <?foreach ($classesHeads as $ind=>$oneClass):
						$colspan=count($JournalTypesHead[$oneClass['id']])?>
                    <th scope="col" id="head_<?=$ind?>"
						colspan="<?=$colspan==0?1:$colspan?>"
						data-content="<?=$ind?>"
						journalId="<?=$oneClass['id']?>"
						class="left"
						style="text-align: center; cursor: pointer"
						onclick="setActive(this);">
						<?=$oneClass['day']." (".$oneClass['week'].")"?>
					</th>
                    <?endforeach;?>
                </tr>
				<tr>
					<th class="left" id="first_sokr">Сокращение</th>
					<?foreach ($classesHeads as $ind=>$oneClass):?>
                    	<?
                        $indType=0;
						foreach ($JournalTypesHead[$oneClass['id']] as $none=>$name):?>
						<th scope="col" id="head_<?=$ind?>_<?=$indType?>" class="left" style="text-align: center;"><?=$name?></th>
                        <?$indType++;
						endforeach;
                        if ($indType==0):?>
						<th scope="col" id="head_<?=$ind?>_<?=$indType?>" class="left" style="text-align: center;"></th>
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
                        <?foreach ($classesHeads as $ind=>$oneClass):?>
                            <?
                            $indMark=0;
                            $markWas=false;
							foreach ($classesMarks[$id_stud][$oneClass['id']] as $indMark=>$oneMark):
								$markWas=true;?>
								<td class="left" id="row_<?=$indStud?>_day_<?=$ind?>_<?=$indMark?>">
								<input id="mark_<?=$oneClass['id']?>_<?=$id_stud?>_<?=$oneMark[2]?>"
									   class="mark"
									<?=$quarters['edit']?'':'disabled'?>
									   value="<?=$oneMark[1]?>"
									   name="marks[<?=$oneClass['id']?>][<?=$id_stud?>][<?=$oneMark[2]?>][mark]"
								>
								</td>
                            <?endforeach;
                            if (!$markWas):?>
								<td class="left" id="row_<?=$indStud?>_day_<?=$ind?>_<?=$indMark?>">
								</td>
							<?endif;?>
                        <?endforeach;?>
                    </tr>
                <?$indStud++;
				endforeach;?>
				<tr>
					<td class="left" style="color: red"><b>Тема урока: </b></td>
                <?foreach ($classesHeads as $ind=>$oneClass):
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
					<td class="left" style="color: red"><b>Комментарий к уроку: </b></td>
                    <?foreach ($classesHeads as $ind=>$oneClass):
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
		$('#journal').load("/journal/schedule.php?id=<?=$_REQUEST['id']?>&classes=<?=$_REQUEST['classes']?>&QUAR="+$(elem).attr("data-content")+"&PAGE="+$(elem).attr("id"),function () {
			//$("#loading").remove();
		});
	}
	function page_loader(elem) {
		obnul();
        $('#journal').load("/journal/schedule.php?id=<?=$_REQUEST['id']?>&classes=<?=$_REQUEST['classes']?>&QUAR=<?=$quarterNum?>&PAGE="+$(elem).attr("id"),function () {
        //$("#loading").remove();
        });
	}
	function quar_loader(elem) {
		obnul();
		$('#journal').load("/journal/schedule.php?id=<?=$_REQUEST['id']?>&classes=<?=$_REQUEST['classes']?>&QUAR="+$(elem).attr("id")+"&PAGE=1",function () {
			//$("#loading").remove();
		});
	}
	function back_on_week(){
		let journalElem=$("#journal");
		loadingShow(journalElem);
		obnul();
		journalElem.load("/journal/schedule.php?id=<?=$_REQUEST['id']?>&classes=<?=$_REQUEST['classes']?>",function () {
			// $("#loading").remove();
		});
	}
	$( "#journalList" ).submit(function( event ){
		event.preventDefault();
		var strData= $( "#journalList" ).serialize();
		$.post( "/journal/index.php", $( "#journalList" ).serialize() ,function( data ) {
			if (data=='success'){
				obnul();
				$('#journal').load("/journal/schedule.php?id=<?=$_REQUEST['id']?>&classes=<?=$_REQUEST['classes']?>");
				alert( "Успешно сохранено" );
			}
		});
	});
</script>
<?php
function getCurrentSection(){
    $group=\CIBlockElement::GetById($_REQUEST['classes'])->Fetch();
    $groupsSections=getIBlockSections(11);
    $group['section_name']=$groupsSections[$group['IBLOCK_SECTION_ID']];
    $journal=getIBlockSections(12);
    $section=array_search($group['section_name'],$journal);
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
    GLOBAL $USER;
    $elem=new CIBlockElement();
    $ClassElem=$elem::GetByID($class)->fetch();
    $teacher=$elem::GetProperty(10,$class,[],['CODE'=>'teacher'])->Fetch()['VALUE'];
    $nameClasses=$ClassElem['NAME'];
    $PROP = Array(
        "TEACHER"			=> $teacher,
        'SUBJECT'=>$class,
//        'MARKS'=>$marks
    );
    $arUpdateValues = Array(
        "MODIFIED_BY" =>        $USER->GetID(),  // элемент изменен текущим пользователем
        "IBLOCK_SECTION_ID" =>  $section,
        "IBLOCK_ID" =>          12,
        "PROPERTY_VALUES"   =>  $PROP,
        "ACTIVE"    =>          "Y",			// активен
    );
    foreach ($days as $oneDay){
        $arUpdateValues['PROPERTY_VALUES']['DATE_CLASS']=$oneDay;
        $arUpdateValues['PROPERTY_VALUES']['EVENT_LENGTH']=strtotime($oneDay);
        $arUpdateValues['NAME']=$oneDay.' '.$nameClasses;
        $NewID = $elem->Add($arUpdateValues);
    }
}
function getStudents($allSection=false){
    $studentsIds=CGroup::GetGroupUser(9);
    $by='name';$ord='asc';
    if ($allSection){
        $allSection=CIBlockElement::GetByID($_REQUEST['classes'])->Fetch()['IBLOCK_SECTION_ID'];
        $res=CIBlockElement::GetList([],['IBLOCK_ID'=>11,'SECTION_ID'=>$allSection],
            false,false,['ID']);
        $allSection=[];
        while ($obj=$res->Fetch()){
            $allSection[]=$obj['ID'];
        }
        $users = CUser::GetList($by, $ord, ['ID' => implode(' | ', $studentsIds),
            'ACTIVE' => 'Y', 'UF_EDU_STRUCTURE' => $allSection], []);
    } else {
        $users = CUser::GetList($by, $ord, ['ID' => implode(' | ', $studentsIds),
            'ACTIVE' => 'Y', 'UF_EDU_STRUCTURE' => $_REQUEST['classes']], []);
    }
    $students=[];
    while ($user=$users->fetch()){
        $students[$user['ID']]="{$user['LAST_NAME']} ".strtoupper(substr($user['NAME'],0,2)).".";
//            echo "{$user['LAST_NAME']} ".strtoupper(substr($user['NAME'],1,1)).".</br>";
    }
    return $students;
}
function getQuarters(&$quarterNum){
    $req=\CIBlockElement::GetList(
        ['PROPERTY_YEAR'=>'ASC'],
        ['IBLOCK_ID'=>19,'PROPERTY_NOW'=>'Y'],
        false,false,
        [
            'ID',
            "NAME",
            'PROPERTY_START1',
            'PROPERTY_START2',
            'PROPERTY_START3',
            'PROPERTY_START4',
            'PROPERTY_FINISH1',
            'PROPERTY_FINISH2',
            'PROPERTY_FINISH3',
            'PROPERTY_FINISH4',
        ])->Fetch();
    $result=[
        [
            's'=>$req['PROPERTY_START1_VALUE'],
            'f'=>$req['PROPERTY_FINISH1_VALUE']
        ],
        [
            's'=>$req['PROPERTY_START2_VALUE'],
            'f'=>$req['PROPERTY_FINISH2_VALUE']
        ],
        [
            's'=>$req['PROPERTY_START3_VALUE'],
            'f'=>$req['PROPERTY_FINISH3_VALUE']
        ],
        [
            's'=>$req['PROPERTY_START4_VALUE'],
            'f'=>$req['PROPERTY_FINISH4_VALUE']
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
    $filter=['IBLOCK_ID'=>12,'PROPERTY_SUBJECT'=>$id,'SECTION_ID'=>$section,'PROPERTY_DATE_CLASS'=>$quarters[0]['s']];
    $count=CIBlockElement::GetList([], $filter, []);
    return $count;
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
