<?php
// подключение служебной части пролога
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/include.php");

global $USER;
if(!$USER->IsAuthorized()) die('Not Autorizate!');
if(!\CModule::IncludeModule("iblock")) die('Not install module iblock!');
if (empty($_REQUEST['type'])) die('не выбран период'); else $type=$_REQUEST['type'];
if (empty($_REQUEST['classId'])) die('не выбран класс'); else $studClass=$_REQUEST['classId'];

/** PHPExcel,  PHPExcel_IOFactory */
require_once($_SERVER["DOCUMENT_ROOT"].'/classes/PHPExcel.php');
require_once($_SERVER["DOCUMENT_ROOT"].'/classes/PHPExcel/IOFactory.php');
$objPHPExcel = new \PHPExcel();
$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');

// ID колонок для Excel'я
$arCol = array();
for ($i = 1; $i < 27; $i++) $arCol[$i] = chr($i + 64);
for ($j = 1; $j < 10; $j++) for ($k = 1; $k < 27; $k++)
    $arCol[$i++] = chr($j + 64).chr($k + 64);
//Оформление
// Делаем у ячеек рамки
$arrStyle = array(
    'font' => array('size' => 9,),
    'borders' => array(
        'inside' => array(
            'style' => \PHPExcel_Style_Border::BORDER_THIN,
            'color' => array('argb'=>'FF000000')
        ),
        'outline' => array(
            'style' => \PHPExcel_Style_Border::BORDER_THIN,
            'color' => array('argb'=>'FF000000')
        )
    ),
    'alignment' => array(
        'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
        'wrap'=>true,
    )
);
// Делаем рамки у заголовка
$arrStyleTop = array(
    'font' => array('bold' => true,'size' => 9,),
    'borders' => array(
        'inside' => array(
            'style' => \PHPExcel_Style_Border::BORDER_THIN,
            'color' => array('argb'=>'FF000000')
        ),
        'outline' => array(
            'style' => \PHPExcel_Style_Border::BORDER_THIN, //BORDER_MEDIUM
            'color' => array('argb' => 'FF000000'),
        )
    ),
    'alignment' => array(
        'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
        'wrap'=>true,

    )
);
$section=getCurrentSection($studClass);
$quarters=getPeriod($type);
$periodDates=$quarters[1];
$quarters=$quarters[0];
$elem=new CIBlockElement();
$filter=[
    'IBLOCK_ID'=>12,
    'SECTION_ID'=>$section,
    ["LOGIC"=>'AND',
        ['>=PROPERTY_EVENT_LENGTH'=>strtotime($quarters['now']['s'])],
        ['<=PROPERTY_EVENT_LENGTH'=>strtotime($quarters['now']['f'])],
    ]
];
if (!in_array(1,$USER->GetUserGroupArray())){//не админ
    $filter['PROPERTY_TEACHER']=$USER->GetID();
}

$req=$elem::GetList(
    ['PROPERTY_EVENT_LENGTH'=>'ASC'],
    $filter,
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
$classesMarks=[];
$subjectIDS=[];
$studentsArray=getStudents($studClass);
$journalIDS=[];
//собираем сначала все дни
while($obj=$req->Fetch()){
    $date=$obj['PROPERTY_DATE_CLASS_VALUE'];
    $classID=$obj['PROPERTY_SUBJECT_VALUE'];
    $journalID=$obj['ID'];
    $journalIDS[]=$journalID;
    $subjectIDS[]=$classID;
    if (!isset($classesMarks[$classID])) $classesMarks[$classID]=[
        'NAME'=>$obj['PROPERTY_SUBJECT_NAME'],
        'DATA'=>[],
    ];
    foreach ($studentsArray as $stid=>$oneStudent){
        if (!isset($classesMarks[$classID]['DATA'][$stid])) $classesMarks[$classID]['DATA'][$stid]=['NAME'=>$oneStudent,'DATA'=>[]];
        $classesMarks[$classID]['DATA'][$stid]['DATA'][$journalID]=['theme'=>$obj['PROPERTY_LESSON_THEME_VALUE'],
            'comment'=>$obj['PROPERTY_COMMENT_CLASS_VALUE'],'DATA'=>[]];
    }
}
//теперь все оценки
$req=$elem::GetList(['PROPERTY_MARK'=>"ASC"],
    ['IBLOCK_ID'=>21,'PROPERTY_SUBJECT'=>$subjectIDS,'PROPERTY_CLASS'=>$journalIDS,'PROPERTY_STUDENT'=>array_keys($studentsArray)],
    false,false,
    ['PROPERTY_CLASS','PROPERTY_STUDENT','PROPERTY_TYPE','PROPERTY_MARK','ID','PROPERTY_SUBJECT','PROPERTY_TYPE.PROPERTY_SHORT']);
while($obj=$req->Fetch()){
    $studID=$obj['PROPERTY_STUDENT_VALUE'];
    $journalID=$obj['PROPERTY_CLASS_VALUE'];
    $subjectID=$obj['PROPERTY_SUBJECT_VALUE'];
    $classesMarks[$subjectID]['DATA'][$studID]['DATA'][$journalID]['DATA'][]=[$obj['PROPERTY_MARK_VALUE'],$obj['PROPERTY_TYPE_PROPERTY_SHORT_VALUE']];
}
//echo "<pre>";
//print_r($classesMarks);
//echo "</pre>";
//exit;
$act_list=0;
$col=1;
foreach ($classesMarks as $oneClass){
    if ($act_list>0) $objPHPExcel->createSheet();
    $excelList=$objPHPExcel->setActiveSheetIndex($act_list);
    $row=2;
    $excelList->setTitle(str_replace(['*'],'',$oneClass['NAME']));
    $excelList->setCellValue($arCol[$col].$row,"Журнал успеваемости от ".date('d.m.Y'));
    $row+=2;
    $excelList->setCellValue($arCol[$col].$row,'Предмет: '.$oneClass['NAME']);
    $row+=4;
    setHeader($periodDates,$excelList,$arCol,$arrStyleTop);
    $col=1;
    $excelList->getColumnDimension($arCol[1])->setWidth(15);
    $themeRow=null;$commentRow=null;;$flwas=true;
    foreach ($oneClass['DATA'] as $oneStudent){
        if ($themeRow==null){
            $themeRow=$row;$row++;////////////////////////////////////////////////////////////////////////////////////////////
            $commentRow=$row;$row++;//////////////////////////////////////////////////////////////////////////////////////////
            $excelList->setCellValue($arCol[1].$themeRow,'Тема урока');///////////////////////////////////
            $excelList->setCellValue($arCol[1].$commentRow,'Комментарий');////////////////////////////////
        }
        $excelList->setCellValue($arCol[$col].$row,$oneStudent['NAME']);
        foreach ($oneStudent['DATA'] as $date=>$oneMarksDate){
            $col++;
            $markList='';
            $markWas=false;
            foreach ($oneMarksDate['DATA'] as $oneMark){
                $markList.=$oneMark[1].' | '.$oneMark[0]."\n";
                if (!empty($oneMark[0])) $markWas=true;
            }
            if (!$markWas) continue;
            $excelList->setCellValue($arCol[$col].$row,$markList);
            $excelList->setCellValue($arCol[$col].$themeRow,$oneMarksDate['theme']);//////////////////////////////////////
            $excelList->setCellValue($arCol[$col].$commentRow,$oneMarksDate['comment']);//////////////////////////////////
            if (!empty($oneMarksDate['theme']) || !empty($oneMarksDate['comment'])) $excelList->getColumnDimension($arCol[$col])->setWidth(15);///////////////////////////////////////////////
        }
        $excelList->getStyle($arCol[1].$row.':'.$arCol[$col].$row)->applyFromArray($arrStyle);
        if ($flwas) {
            $excelList->getStyle($arCol[2] . $themeRow . ':' . $arCol[$col] . $themeRow)->applyFromArray($arrStyle);////////////////
            $excelList->getStyle($arCol[2] . $commentRow . ':' . $arCol[$col] . $commentRow)->applyFromArray($arrStyle);////////////
            $excelList->getStyle($arCol[1] . $themeRow)->applyFromArray($arrStyleTop);////////////////////////////////////////////////
            $excelList->getStyle($arCol[1] . $commentRow)->applyFromArray($arrStyleTop);//////////////////////////////////////////////
            $flwas = false;
        }
        $col=1;
        $row++;
    }
    $row++;
//    $excelList->setCellValue($arCol[1].$row,'Оценки: [д/з]/[р/у]/[с/р]/[т.д]');
    $act_list++;
}
$objPHPExcel->setActiveSheetIndex(0);
//Создаём файл
$objWriter =\PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$file_name = 'Жрунал успеваемости от '.date('d_m_Y').'.xls';

ob_clean();
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="'.$file_name.'"');
header('Cache-Control: max-age=0');

$objWriter->save('php://output');

function setHeader($arrDays,&$excel,$arCol,$style){
    $arrMonth=$arrDays[1];
    $arrDays=$arrDays[0];
    $col=1;
    $row=6;
    $excel->setCellValue($arCol[$col].$row, "Месяц");$col++;
    foreach ($arrMonth as $oneMonth){
        $excel->setCellValue($arCol[$col].$row, $oneMonth['name']);
        $col+=$oneMonth['col'];
        $excel->mergeCells($arCol[$col-$oneMonth['col']].$row.':'.$arCol[$col-1].$row);
    }
    $excel->getStyle($arCol[1].$row.':'.$arCol[$col-1].$row)->applyFromArray($style);
    $row++;$col=1;
    $excel->setCellValue($arCol[$col].$row, "День");$col++;
    foreach ($arrDays as $oneDay){
        $excel->setCellValue($arCol[$col].$row, $oneDay);
        $col++;
    }
    $excel->getStyle($arCol[1].$row.':'.$arCol[$col-1].$row)->applyFromArray($style);
}
function getPeriod($type){
    $quarters=getQuarters();
    $arrDates=[];
    if ($type=='year'){
        $quarters['now']=[
            's'=>$quarters[0]['s'],
            'f'=>$quarters[3]['f']
        ];
        $q1=getQuartersDays($quarters[0]);
        $q2=getQuartersDays($quarters[1]);
        $q3=getQuartersDays($quarters[2]);
        $q4=getQuartersDays($quarters[3]);
        $arrDates[0]=array_merge($q1[0],$q2[0],$q3[0],$q4[0]);
        $arrDates[1]=array_merge($q1[1],$q2[1],$q3[1],$q4[1]);
    } else {
        $quarters['now']=[
            's'=>$quarters[$type-1]['s'],
            'f'=>$quarters[$type-1]['f']
        ];
        $arrDates=getQuartersDays($quarters['now']);
    }
    return [$quarters,$arrDates];
}
function getQuartersDays($q){
    $days=[];
    $month=[];
    $date=(new DateTime($q['s']));
    while ($q['f']!=$date->format('d.m.Y')){
        if (!isset($month[$date->format('m')])) {
            $month[$date->format('m')]=[
                'num'=>$date->format('m'),
                'name'=>setMonth($date->format('m')),
                'col'=>0
            ];
        }
        if (!in_array($date->format('N'),[6,7])){
            $days[]=$date->format('d');
            $month[$date->format('m')]['col']++;
        }
        $date->modify("+1 day");
    }
    if (!in_array($date->format('N'),[6,7])){
        $days[]=$date->format('d');
        $month[$date->format('m')]['col']++;
    }
    return [$days,$month];
}
function getQuarters(){
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
    return [
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
}
function setMonth($id){
    $id.='';
    switch ($id){
        case '1':
            return 'Январь';
        case '2':
            return 'Февраль';
        case '3':
            return 'Март';
        case '4':
            return 'Апрель';
        case '5':
            return 'Май';
        case '6':
            return 'Июнь';
        case '7':
            return 'Июль';
        case '8':
            return 'Август';
        case '9':
            return 'Сентябрь';
        case '10':
            return 'Октябрь';
        case '11':
            return 'Ноябрь';
        case '12':
            return 'Декабрь';
    }
}
function getStudents($studClass){
    $studentsIds=CGroup::GetGroupUser(9);
    $by='name';$ord='asc';
    $users = CUser::GetList($by, $ord, ['ID' => implode(' | ', $studentsIds),
        'ACTIVE' => 'Y', 'UF_EDU_STRUCTURE' => $studClass], []);
    $students=[];
    while ($user=$users->fetch()){
        $students[$user['ID']]="{$user['LAST_NAME']} ".strtoupper(substr(trim($user['NAME']),0,2)).".";
        //            echo "{$user['LAST_NAME']} ".strtoupper(substr($user['NAME'],1,1)).".</br>";
    }
    return $students;
}
function getCurrentSection($studClass){
    $group=\CIBlockElement::GetById($studClass)->Fetch();
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
