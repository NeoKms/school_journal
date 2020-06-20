<?php
define('ROOT', '../');
session_start();
ob_start();
$page = $title = 'Журнал';
require(ROOT."header.php");
$arGroups = $_SESSION['user']['groups'];
if (isset($_REQUEST['id_quarter'])){
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
			'now'=>'N',
		);
		if ($quar==$_REQUEST['NOW'])$PROP['now']='Y';
		$q = "update quarters set ";
		foreach ($PROP as $name=>$val) {
			if ($name=='now') {
                $q.="$name='{$val}'";
			} else {
                $q .= "$name='{$val}',";
            }
		}
		$q.=" where id=".$quar;
        $res = database::getInstance()->query($q);
    }
}
if (isset($_REQUEST['marks'])){
	$marksArr=$_REQUEST['marks'];
	$daysArr=$_REQUEST['day'];
	$subject=$_REQUEST['subject'];
    $db = database::getInstance();
    $res=$db->query('select id,short from type_marks');
    $arrTypesMarks=[];
   foreach ($res as $obj){
        $arrTypesMarks[$obj['short']]=$obj['id'];
	}
    foreach ($daysArr as $id => $dayData){
        $q = "update journal set ";
        if ($dayData['theme']!=''&&$dayData['comment']!=''){
            $q.="theme = '{$dayData['theme']}'";
            $q.=", comment = '{$dayData['comment']}'";
		} elseif ($dayData['theme']!=''){
    		$q.="theme = '{$dayData['theme']}'";
		} elseif ($dayData['comment']!=''){
    		$q.="comment = '{$dayData['comment']}'";
		} else {
        	continue;
		}
    	$q.=" where id=".$id;
        $db->query($q);
	}
    foreach ($marksArr as $journalId=>$ArrStud){
        foreach ($ArrStud as $studID=>$marks){
        	foreach ($marks as $id => $value){
                $PROP = Array(
                    "mark"		=> 	$value['mark'],
                    'student_id'	=>	$studID,
                    'type_id' 		=> $arrTypesMarks[$value['type'] ?? ''] ?? '',
					'journal_id'		=>	$journalId,
					'subject_id'	=> $subject,
					'date_create'=>time(),
                );
                if ($id>0){
                	$q = "update marks set mark = '{$value['mark']}' where id=".$id;
                	$db->query($q);
				} else {
                    $q="insert into marks (".implode(",",array_keys($PROP)).") values ('".implode("','",$PROP)."')";
                    $db->query($q);
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
  <h3> Выберите класс</h3>
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
<?php require(ROOT."footer.php"); ?>
