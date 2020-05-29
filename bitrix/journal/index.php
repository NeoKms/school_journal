<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Журнал");
global $USER;
if(!CModule::IncludeModule("iblock")) die();
$arGroups = $USER->GetUserGroupArray();
if (isset($_REQUEST['id_quarter'])){
    $elem_qu=new CIBlockElement();
    foreach ($_REQUEST['id_quarter'] as $ind=>$quar){
		$PROP = Array(
			"start1" => date('d.m.Y',strtotime($_REQUEST['s1'][$ind])),
            "finish1" => date('d.m.Y',strtotime($_REQUEST['f1'][$ind])),
            "start2" => date('d.m.Y',strtotime($_REQUEST['s2'][$ind])),
            "finish2" => date('d.m.Y',strtotime($_REQUEST['f2'][$ind])),
            "start3" => date('d.m.Y',strtotime($_REQUEST['s3'][$ind])),
            "finish3" => date('d.m.Y',strtotime($_REQUEST['f3'][$ind])),
            "start4" => date('d.m.Y',strtotime($_REQUEST['s4'][$ind])),
            "finish4" => date('d.m.Y',strtotime($_REQUEST['f4'][$ind])),
			'NOW'=>'N',
		);
		if ($quar==$_REQUEST['NOW'])$PROP['NOW']='Y';
        CIBlockElement::SetPropertyValuesEx($quar, 19, $PROP);
    }
}
if (isset($_REQUEST['marks'])){
	$marksArr=$_REQUEST['marks'];
	$daysArr=$_REQUEST['day'];
	$subject=$_REQUEST['subject'];
    ob_end_clean();
    $res=CIBlockElement::getList([],['IBLOCK_ID'=>20],false,false,['ID','PROPERTY_short']);
    $arrTypesMarks=[];
    while ($obj=$res->fetch()){
        $arrTypesMarks[$obj['PROPERTY_SHORT_VALUE']]=$obj['ID'];
	}
//    echo "<pre>";
//    var_dump($marksArr);
//    var_dump($daysArr);
//    var_dump($subject);
//    var_dump($arrTypesMarks);
//    echo "</pre>";
//    exit;
    $elem=new CIBlockElement();
    foreach ($daysArr as $id => $dayData){
        $elem->SetPropertyValuesEx($id,12,['LESSON_THEME'=>$dayData['theme'],'comment_class'=>$dayData['comment']]);
	}
    foreach ($marksArr as $journalId=>$ArrStud){
        foreach ($ArrStud as $studID=>$marks){
        	foreach ($marks as $id => $value){
                $PROP = Array(
                    "mark"		=> 	$value['mark'],
                    'student'	=>	$studID,
                    'type' 		=> $arrTypesMarks[$value['type']],
					'class'		=>	$journalId,
					'subject'	=> $subject,
                );
                if ($id>0){
                    $elem->SetPropertyValuesEx($id,21,['mark'=>$value['mark']]);
				} else {
                    $arUpdateValues = Array(
                        "MODIFIED_BY" =>        $USER->GetID(),
                        "IBLOCK_ID" =>          21,
                        "PROPERTY_VALUES"   =>  $PROP,
                        "ACTIVE"    =>          "Y",
						'NAME'=>$journalId.' '.$studID,
                    );
                    (new CIBlockElement())->Add($arUpdateValues);
				}
            }
		}
    }
    ob_end_clean();
	echo 'success';
	exit;
}
?>
<div id="classes" class="container">
  <h1> Выберите класс</h1>
</div>
<div id="journal" class="container">
<script>
	var activ_elem_type=null;
	var activ_elem_type_ind=null;
	var arrHead=[];
	var COUNTER_IDS=-1;
	var studentIds=[];
	var masseditMarks=null;
	function add_on_elem(elem) {
		if (window.activ_elem_type!==null && window.masseditMarks=='1'){
			journalID=activ_elem_type.attr('journalId');
			if (window.arrHead[window.activ_elem_type_ind]==0){
				let elemApp=$('#head_'+window.activ_elem_type_ind+'_0');
				elemApp.append(elem.id);
				for (let i=0;i<window.colRows;i++){
					let inputHidden= document.createElement('input');
					inputHidden.hidden=true;
					inputHidden.name="marks["+journalID+"]["+window.studentIds[i]+"]["+window.COUNTER_IDS+"][type]";
					inputHidden.value=elem.id;
					let input0 = document.createElement('input');
					// input0.id="mark_"+day+"_"+student+"_"+new_Id;
					input0.className='mark';
					input0.name="marks["+journalID+"]["+window.studentIds[i]+"]["+window.COUNTER_IDS+"][mark]";
					$('#row_'+i+'_day_'+window.activ_elem_type_ind+'_0').append(inputHidden);
					$('#row_'+i+'_day_'+window.activ_elem_type_ind+'_0').append(input0);
				}
				window.COUNTER_IDS--;
				window.arrHead[window.activ_elem_type_ind]++;
			} else {
				let appAfter=$("#head_"+window.activ_elem_type_ind+"_"+(window.arrHead[window.activ_elem_type_ind]-1));
				appAfter.after("" +
					"<th " +
					"scope='col'" +
					" id='head_"+window.activ_elem_type_ind+"_"+window.arrHead[window.activ_elem_type_ind]+"' " +
					"class='left' style='text-align:center'>"+elem.id+"</th>");
				let nowColspan=parseInt($("#head_"+window.activ_elem_type_ind).attr('colspan'))+1;
				$("#head_"+window.activ_elem_type_ind).attr('colspan',nowColspan);
				$("#theme_"+window.activ_elem_type_ind).attr('colspan',nowColspan);
				$("#comment_"+window.activ_elem_type_ind).attr('colspan',nowColspan);
				let commentText=document.getElementById("comment_"+window.activ_elem_type_ind+"_textarea");
				let newwidth=parseInt(commentText.offsetWidth)+40;
				$(commentText).css('width',newwidth+'px');
				let inputTheme=document.getElementById("theme_"+window.activ_elem_type_ind+"_input");
				newwidth=parseInt(inputTheme.offsetWidth)+50;
				$(inputTheme).css('width',newwidth+'px');
				for (let i=0;i<window.colRows;i++){
					let tdAfter=$("#row_"+i+'_day_'+window.activ_elem_type_ind+'_'+(window.arrHead[window.activ_elem_type_ind]-1));
					let td0=document.createElement('td');
					td0.className='left';
					td0.id="row_"+i+'_day_'+window.activ_elem_type_ind+'_'+window.arrHead[window.activ_elem_type_ind];
					let input0 = document.createElement('input');
					input0.className='mark';
					input0.name="marks["+journalID+"]["+window.studentIds[i]+"]["+window.COUNTER_IDS+"][mark]";
					let inputHidden= document.createElement('input');
					inputHidden.hidden=true;
					inputHidden.value=elem.id;
					inputHidden.name="marks["+journalID+"]["+window.studentIds[i]+"]["+window.COUNTER_IDS+"][type]";
					td0.append(input0);
					td0.append(inputHidden);
					tdAfter.after(td0);
				}
				window.COUNTER_IDS--;
				window.arrHead[window.activ_elem_type_ind]++;
			}
		}
	}
	function obnul() {
		window.activ_elem_type=null;
		window.activ_elem_type_ind=null;
		window.arrHead=[];
		window.COUNTER_IDS=-1;
		window.studentIds=[];
		window.masseditMarks=null;
	}
</script>
</div>
 <?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
