<?php
define('ROOT', '../');
session_start();
require (ROOT.'database/database.php');
$db = database::getInstance();
if (empty($_REQUEST['type'])) die('не выбран период'); else $type=$_REQUEST['type'];
if (empty($_REQUEST['classId'])) die('не выбран класс'); else $studClass=$_REQUEST['classId'];

/** PHPExcel,  PHPExcel_IOFactory */
@require_once(ROOT.'classes/PHPExcel.php');
@require_once(ROOT.'classes/PHPExcel/IOFactory.php');
@$objPHPExcel = new \PHPExcel();
@$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
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
$q='select journal.id, journal.date_int, journal.name,journal.teacher,journal.subject,journal.date,journal.theme,journal.comment, subjects.name as subj_name
		from journal inner join subjects on journal.subject = subjects.id 
		where journal.group_id='.$section.' 
		and journal.date_int>='.strtotime($quarters['now']['s']).' 
		and journal.date_int<='.strtotime($quarters['now']['f']);
if (!in_array(1,$_SESSION['user']['groups'])){//не админ
   $q.=' and teacher='.$_SESSION['user']['id'];
}
$q.=' order by date_int asc';
$res = $db->query($q);
$classesMarks=[];
$subjectIDS=[];
$studentsArray=getStudents($studClass);
$journalIDS=[];
//собираем сначала все дни
foreach ($res as $obj){
    $date=$obj['date'];
    $classID=$obj['subject'];
    $journalID=$obj['id'];
    $journalIDS[]=$journalID;
    $subjectIDS[]=$classID;
    if (!isset($classesMarks[$classID])) $classesMarks[$classID]=[
        'NAME'=>$obj['subj_name'],
        'DATA'=>[],
    ];
    foreach ($studentsArray as $stid=>$oneStudent){
        if (!isset($classesMarks[$classID]['DATA'][$stid])) $classesMarks[$classID]['DATA'][$stid]=['NAME'=>$oneStudent,'DATA'=>[]];
        $classesMarks[$classID]['DATA'][$stid]['DATA'][$journalID]=['theme'=>$obj['theme'],
            'comment'=>$obj['comment'],'DATA'=>[]];
    }
}
//теперь все оценки
$q='select marks.subject_id,marks.journal_id,marks.type_id,marks.student_id,marks.id,marks.mark, type_marks.short'.
    ' from marks inner join type_marks on marks.type_id = type_marks.id '.
    "where subject_id in ('".implode("','",$subjectIDS)."') and journal_id in ('".implode("','",$journalIDS)."')".
    "and student_id in(".implode(',',array_keys($studentsArray)).") order by marks.mark asc";
$res = $db->query($q);
foreach ($res as $obj){
    $studID=$obj['student_id'];
    $journalID=$obj['journal_id'];
    $subjectID=$obj['subject_id'];
    $classesMarks[$subjectID]['DATA'][$studID]['DATA'][$journalID]['DATA'][]=[$obj['mark'],$obj['short']];
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
    $req=database::getInstance()->query("select * from quarters where now='Y' order by year asc")[0];
    $format='d.m.Y';
    return [
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
    $res = database::getInstance()->query("select users.id,users.name from users inner join students_groups sg on users.id = sg.student_id where sg.classes_id={$studClass}");
    $students=[];
    foreach ($res as $user){
        $students[$user['id']]="{$user['name']}";
//                    echo "{$user['name']}</br>";
    }
    return $students;
}
function getCurrentSection($studClass){
    return database::getInstance()->query("select group_id from subjects where id={$studClass}")[0]['group_id'];
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
