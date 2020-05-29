<?php
include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
use DateTime;

if(!CModule::IncludeModule("iblock")) die('ошибка битрикс');
if (empty($_REQUEST['id'])) die('неверный ид ученика');
else {
    $studentID=$_REQUEST['id'];
}
if (isset($_REQUEST['PAGE'])) $pageNum=$_REQUEST['PAGE'];
if (isset($_REQUEST['QUAR'])) $quarterNum=$_REQUEST['QUAR'];
if (isset($_REQUEST['allQuar'])) $allQuar=true;
$section=getCurrentSection($studentID);
$quarters=getQuarters($quarterNum);
$currentWeek=getWeek($quarters,$pageNum);
if ($allQuar) {
    $currentWeek = [$quarters['now']['s'], $quarters['now']['f']];
}
$elem=new CIBlockElement();
$filterWeek=[
    'IBLOCK_ID'=>12,
    'SECTION_ID'=>$section,
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
        'PROPERTY_MARKS',
        'EVENT_LENGTH',
        'PROPERTY_SUBJECT.NAME',
        'PROPERTY_LESSON_THEME',
        'PROPERTY_COMMENT_CLASS',
    ]);
$classesHeads=[];
$classesMarks=[];
$subjectIDS=[];
$journalIDS=[];
$dayWeek=new DateTime();
while($obj=$req->Fetch()){
    $date=$obj['PROPERTY_DATE_CLASS_VALUE'];
    $classID=$obj['PROPERTY_SUBJECT_VALUE'];
    $journalId=$obj['ID'];
    $journalIDS[]=$journalId;
    $comment=$obj['PROPERTY_COMMENT_CLASS_VALUE'];
    $theme=$obj['PROPERTY_LESSON_THEME_VALUE'];
    $subjectIDS[]=$obj['PROPERTY_SUBJECT_VALUE'];
    if (!isset($classesHeads[$date])) $classesHeads[$date] = switchWeek($dayWeek->setTimestamp($obj['PROPERTY_EVENT_LENGTH_VALUE'])->format('N'));
    if (!isset($classesMarks[$classID]))$classesMarks[$classID]=['NAME'=>$obj["PROPERTY_SUBJECT_NAME"],'DATES'=>[]];
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
$req=$elem::GetList(['PROPERTY_MARK'=>"ASC"],
    ['IBLOCK_ID'=>21,'PROPERTY_SUBJECT'=>$subjectIDS,'PROPERTY_CLASS'=>$journalIDS,'PROPERTY_STUDENT'=>$studentID],
    false,false,
    ['PROPERTY_CLASS','PROPERTY_STUDENT','PROPERTY_TYPE','PROPERTY_MARK','ID','PROPERTY_SUBJECT','PROPERTY_TYPE.PROPERTY_SHORT']);
while($obj=$req->Fetch()){
    $studID=$obj['PROPERTY_STUDENT_VALUE'];
    $journalID=$obj['PROPERTY_CLASS_VALUE'];
    $subjectID=$obj['PROPERTY_SUBJECT_VALUE'];
    if (isset($classesMarks[$subjectID]['DATES'][$journalID])){
    	if (!empty($obj['PROPERTY_MARK_VALUE'])) {
            $existMarksIds[] = $subjectID;
            $classesMarks[$subjectID]['DATES'][$journalID]['marks'][] = [
                $obj['PROPERTY_MARK_VALUE'],
                $obj['PROPERTY_TYPE_PROPERTY_SHORT_VALUE']
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

$filterAll=[
    'IBLOCK_ID'=>12,
    'SECTION_ID'=>$section,
    'PROPERTY_SUBJECT'=>$classID,
    ["LOGIC"=>'AND',
        ['>=PROPERTY_EVENT_LENGTH'=>strtotime($quarters['now']['s'])],
        ['<=PROPERTY_EVENT_LENGTH'=>strtotime($quarters['now']['f'])],
    ]
];
$countClassesInQuarter=\CIBlockElement::GetList([],$filterAll,[]);
$colWeek=(int)round($countClassesInQuarter/5,PHP_ROUND_HALF_DOWN);
if (($countClassesInQuarter%5)!==0)$colWeek++;
?>
    <style>
		.table-content .studmark {
			font-size: 8pt;
		}
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
        .table-content input {
            width: 20px;
        }
        .left {
            border-right: 1px solid #bebebe;
        }
        .shadow {
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
            overflow-y: hidden;
        }
        .headText {
            margin-top: 20px;
        }
        .mark {
            margin: 10px;
        }
    </style>
    <div class="row center headText">
        <h1><?=isset($_REQUEST['QUAR'])?'Выбранная':'Текущая'?> четверть №<?=$quarters['now']['num']?> с <?=$quarters['now']['s']?> по <?=$quarters['now']['f']?></h1>
        <h3><?if($allQuar):echo 'Просмотр четверти';else:?><?=isset($_REQUEST['PAGE'])?'Выбранная':'Текущая'?> учебная неделя №<?=$pageNum?>
            с <?=$currentWeek[0]?> по <?=$currentWeek[1]?><?endif;?></h3>
        <div class="page_navigator">
            <button class="btn btn-primary" onclick="quar_loader(this)"
                    id="<?=$quarterNum==1?1:($quarterNum-1)?>">
                <i class="arrow leftArrow"></i> четверть</button>
            <button class="btn btn-primary" onclick="page_loader(this)"
                    id="<?=$pageNum==1?1:($pageNum-1)?>">
                <i class="arrow leftArrow"></i> неделя
            </button>
            <?if ($allQuar):?>
            <button class="btn btn-warning" onclick="page_loader(this)"
                    id="<?=$pageNum?>">
                На неделе
            </button>
            <?else:?>
                <button class="btn btn-warning" onclick="quar_loader(this)"
                        id="<?=$quarterNum?>" data-action="allQuar">
                    За четверть
                </button>
            <?endif;?>
            <button class="btn btn-primary" onclick="page_loader(this)"
                    id="<?=$pageNum==$colWeek?$colWeek:($pageNum+1)?>">
                неделя <i class="arrow rightArrow"></i>
            </button>
            <button class="btn btn-primary" onclick="quar_loader(this)"
                    id="<?=$quarterNum==4?4:($quarterNum+1)?>">
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
                        <th scope="col"  class="left mark" style="text-align: center"><?=$day." (".$week.")"?></th>
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
        <img src="../journal/loading.gif" width="150px">
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
		(function() {
			function scrollHorizontally(e) {
				e = window.event || e;
				var delta = Math.max(-1, Math.min(1, (e.wheelDelta || -e.detail)));
				document.getElementById('mouseScrolling').scrollLeft -= (delta*10); // Multiplied by 10
				e.preventDefault();
			}
			if (document.getElementById('mouseScrolling').addEventListener) {
				// IE9, Chrome, Safari, Opera
				document.getElementById('mouseScrolling').addEventListener("mousewheel", scrollHorizontally, false);
				// Firefox
				document.getElementById('mouseScrolling').addEventListener("DOMMouseScroll", scrollHorizontally, false);
			} else {
				// IE 6/7/8
				document.getElementById('mouseScrolling').attachEvent("onmousewheel", scrollHorizontally);
			}
		})();
		function page_loader(elem) {
			$('#diary').load("/diary/diary.php?id=<?=$studentID?>&QUAR=<?=$quarterNum?>&PAGE="+$(elem).attr("id"),function () {
				//$("#loading").remove();
			});
		}
		function quar_loader(elem) {
            if ($(elem).attr('data-action')=='allQuar'){
				$('#diary').load("/diary/diary.php?id=<?=$studentID?>&QUAR="+$(elem).attr("id")+"&allQuar=Y");
            } else {
				$('#diary').load("/diary/diary.php?id=<?=$studentID?>&QUAR=" + $(elem).attr("id") + "&PAGE=1");
			}
		}
    </script>
<?php
function getCurrentSection($studentID){
    $userClass = CUser::GetList($by, $ord, ['ID' => $studentID,
        'ACTIVE' => 'Y'], [ 'SELECT'=>['UF_EDU_STRUCTURE']])->Fetch()['UF_EDU_STRUCTURE'];
    $group=\CIBlockElement::GetById($userClass)->Fetch();
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
