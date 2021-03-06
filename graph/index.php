<?php
require '../../../config.php';
require 'marker_param.php';
require_once '../../lib.php';



$courseid_1 = required_param('cid1', PARAM_INT);
if (!$course = $DB->get_record('course',array('id'=> $courseid_1))) {
	echo "No course found for id=".$courseid_1.".";
	print_error('nocourseid');
}

require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course->id);

////

if(isset($_POST['update']))

{
	
	$contextid = required_param('contextid', PARAM_INT);
	if (!$context = get_context_instance_by_id($contextid)) {
		error('Incorrect context id');
		
	}
	
	if (!$course = $DB->get_record('course',array('id'=> $courseid_1))) {
		echo "No course found for id=".$courseid_1.".";
		print_error('nocourseid');
	}
	
	
	$letters=grade_get_letters($context);
	


	if ($records = $DB->get_records('grade_letters', array('contextid'=> $context->id), 'lowerboundary ASC')) {
		$old_ids = array_keys($records);

	}
	$action_label = "";
	$action_update_count = 0;
	$action_insert_count = 0;
	foreach($letters as $boundary=>$letter){
		$letter = trim($letter);
		if($letter==''){
			continue;
		}
		$ori_letter = $letter;
		$letter=str_replace("+","1",$letter);
		$newboundary = $_POST['textbox_'.$letter];//required_param('textbox_'.$letter,PARAM_INT);
		$record = new stdClass();
		$record->letter        = $ori_letter;
		$record->lowerboundary = $newboundary;
		$record->contextid     = $context->id;
		if ($old_id = array_pop($old_ids)) {
			$record->id = $old_id;
			$action_update_count++;
			$DB->update_record('grade_letters', $record);
		} else {
			$action_insert_count++;
			$DB->insert_record('grade_letters', $record);
		}
		
			
	}
	$letters=grade_get_letters($context);
	$letters=grade_get_letters($context);

}


////



$context = get_context_instance(CONTEXT_COURSE, $course->id);
$letters=grade_get_letters($context);
//	print_r($letters);
//echo $context->id;

$where = " WHERE c1.courseid=$courseid_1";
$select = " SELECT min(c1.id) ";
$from = " FROM mdl_grade_items c1 ";
$context = get_context_instance(CONTEXT_COURSE,$courseid_1, MUST_EXIST);
//echo $context->id;
$sql=" SELECT count(ra.userid) FROM mdl_role_assignments ra WHERE ra.roleid =5 and contextid =$context->id ";
$usrs = $DB->count_records_sql($sql);
$cat=$DB->get_record_sql("select path from {course_categories} where id=(SELECT category from {course} where id=$course->id)");
$path=explode("/", $cat->path);
$paths="/".$path[1]."/".$path[2];
$program=$DB->get_record_sql("select name from {course_categories} where path='$paths'");

$grades_result = $DB->get_records_sql("SELECT  u.idnumber as idnumber,u.firstname, u.lastname,ROUND(gg.finalgrade,0) AS finalgrade FROM {user} u,{role_assignments} ra , {role} r,{grade_grades} gg,{grade_items} gi
                 WHERE u.id=gg.userid AND
                 ra.userid = u.id
                 and ra.roleid = r.id and
                 gg.itemid=gi.id AND
                 itemtype='course' and gi.courseid=?
                 and ra.roleid =5 and contextid =? order by idnumber", array($courseid_1,$context->id));

foreach($grades_result as $row)
{
	$sum+=$row->finalgrade;
}
$average= $sum/($usrs);
$strgrades = get_string('grades');
$pagename  = get_string('letters', 'grades');

if ($admin) {


} else {
	$navigation = grade_build_nav(__FILE__, $pagename, $courseid_1);
	$navlinks[] = array('name' => "Graph", 'link' => null, 'type' => 'activityinstance');
	$navigation = build_navigation($navlinks);
	print_header('Grades', 'Grades', $navigation, '', '', true, '', user_login_string($SITE).$langmenu);

}

?>
<!--
<link
	href="layout.css" rel="stylesheet" type="text/css"></link>-->
<script
	language="javascript" type="text/javascript" src="js/excanvas.min.js"></script>
<script
	language="javascript" type="text/javascript" src="js/jquery.js"></script>
<script
	language="javascript" type="text/javascript" src="js/jquery.flot.js"></script>
<script language="javascript"
	type="text/javascript" src="js/jquery.flot.selection.js"></script>
<?php
require_once $CFG->libdir.'/gradelib.php';
//rebuild_course_cache($course->id);
$letters=grade_get_letters($context);

if($program->name=="Postgraduate Programs"){
	$letters = array_diff($letters, array('D'));//Added By Hina Yousuf
}

$letters[$average] = 'Average';
$data = array();

?>

<script id="source" language="javascript" type="text/javascript">
var plotHeight = 320;
var plotTop = 3;
var pointersLeft = 690;

var yaxisFrom = 3;
var yaxisTo = 100;
var grade_markers = new Array();

<?php

$max = 100;
$marker_count=0;
$marker_obj="";
foreach($letters as $boundary=>$letter) {
    $letter=str_replace("+","1",$letter);
if(in_array("D",$letters,true)==false){
		if($upperLetter[$letter]=="D"){
			$upperLetter[$letter]="C";
		}
		if($bottomLetter[$letter]=="D"){
			$bottomLetter[$letter]="F";
		}
  }
    
    if($letter=="Average"){
  $max=$boundary;
   echo "var marker".$letter." = {  yaxis: {
                                       from: ".format_float($max,0).",
                                       to: ".format_float($boundary,0)."
                                     },
                                     color:\"rgba(".$marker_color[$marker_count][0].",".$marker_color[$marker_count][1].",".$marker_color[$marker_count][2].",0.5)\",
				     
                                   };\n";
   echo "var marker".$letter."_e = {  yaxis: {
                                       from: ".format_float($max,0).",
                                       to: ".format_float($max,0)."
                                     },
                                  color:\"rgba(0,0,255, .9)\",

                                   };\n";
    if($marker_obj==""){
	      $marker_obj .= "marker$letter";
	      $marker_obj .= ",marker".$letter."_e";
	    }
	    else{
	      $marker_obj .= ",marker$letter";
	      $marker_obj .= ",marker".$letter."_e";
	    }
    }else{
   
  if($upperLetter[$letter]!=null ){
   echo "var marker".$upperLetter[$letter]." = {  yaxis: {
                                       from: ".format_float($max,0).",
                                       to: ".format_float($boundary,0)."
                                     },
                                     color:\"rgba(".$marker_color[$marker_count][0].",".$marker_color[$marker_count][1].",".$marker_color[$marker_count][2].",0.5)\",
				     
                                   };\n";
   echo "var marker".$upperLetter[$letter]."_e = {  yaxis: {
                                       from: ".format_float($max,0).",
                                       to: ".format_float($max,0)."
                                     },
                                  color:\"rgba(0,0,0,0.8)\",

                                   };\n";
  }
if($bottomLetter[$letter]==null){
   echo "var marker".$letter." = {  yaxis: {
                                       from: ".format_float($boundary,0).",
                                       to: ".format_float(0,0)."
                                     },
                                     color:\"rgba(".$marker_color[$marker_count][0].",".$marker_color[$marker_count][1].",".$marker_color[$marker_count][2].",0.5)\",
				     
                                   };\n";
  
    echo "var marker".$letter."_e = {  yaxis: {
                                       from: ".format_float($boundary,0).",
                                       to: ".format_float($boundary,0)."
                                     },
                                    color:\"rgba(0,0,0 ,0)\",
                                   };\n";
}
  
     if($upperLetter[$letter]!=null){
	    if($marker_obj==""){
	      $marker_obj .= "marker$upperLetter[$letter]";
	      $marker_obj .= ",marker".$upperLetter[$letter]."_e";
	    }
	    else{
	      $marker_obj .= ",marker$upperLetter[$letter]";
	      $marker_obj .= ",marker".$upperLetter[$letter]."_e";
	    }
     }
 if($bottomLetter[$letter]==null){
	    if($marker_obj==""){
	      $marker_obj .= "marker$letter";
	      $marker_obj .= ",marker".$letter."_e";
	    }
	    else{
	      $marker_obj .= ",marker$letter";
	      $marker_obj .= ",marker".$letter."_e";
	    }
     }
    }
    $max = $boundary ;
   
    $marker_count++;

}

?>

function markers(axes) {
    
    return [<?php echo $marker_obj;?>];

}

function insidePlot(posY) {
    return (posY>plotTop && posY<(plotTop+plotHeight));
}
function changeGrades(letter){
  var newVal = document.getElementById('textbox_m_'+letter).value;

  var totalTicks = yaxisTo - yaxisFrom;
  var tickRatio = plotHeight/totalTicks;
  calVal = (yaxisTo - newVal) * tickRatio;
  var obj = { title: letter };
  updateMarkers(obj,calVal);

}
function updateMarkers(obj, posY) {

    var totalTicks = yaxisTo - yaxisFrom;

    var tickRatio = plotHeight/totalTicks;
   var yaxisnew = (posY-plotTop)/tickRatio;
    move_diff = yaxisTo-yaxisnew;
   <?php
$marker_count=0;
foreach($letters as $boundary=>$letter) {
  $letter=str_replace("+","1",$letter);
  if(in_array("D",$letters,true)==false){
		if($upperLetter[$letter]=="D"){
			$upperLetter[$letter]="C";
		}
		if($bottomLetter[$letter]=="D"){
			$bottomLetter[$letter]="F";
		}
  }

    //$upperLetter[$letter]=$temp;//hina
    echo "
    if(obj.title.match(/".$letter."$/)) {";

?>
     
    <?php
        echo "var yaxisUpper = 100;\n
              var yaxisLower = 0;\n
              var yaxis_val = yaxisTo-yaxisnew;\n";
       
        if($upperLetter[$letter]!=null){
        	

         echo "yaxisUpper = parseInt(marker".$upperLetter[$letter].".yaxis.from)\n"; 
        }
        if($bottomLetter[$letter]!=null){
        	
          echo "yaxisLower = parseInt(marker".$bottomLetter[$letter].".yaxis.from)\n";
        }
        echo"
        if(yaxis_val>(yaxisUpper-2.9)){\n
          yaxis_val=yaxisUpper-1;\n
        }\n
        if(yaxis_val<(yaxisLower+2.9)){\n
          yaxis_val=yaxisLower+1;\n
        }\n
        marker".$letter.".yaxis.from = yaxis_val;\n";

        echo "var newTop = posY;\n";
   
        echo "DHTMLAPI.moveTo('pointerGrade".$letter."',pointersLeft,newTop);\n";
       
         if($letter=='A'){
        echo "
          marker".$letter.".yaxis.to =marker".$bottomLetter[$letter].".yaxis.from;\n
          
        ";
         }else{
         	echo "
         	 marker".$letter.".yaxis.to =marker".$bottomLetter[$letter].".yaxis.from;\n
          
        
         marker".$upperLetter[$letter].".yaxis.to =marker".$letter.".yaxis.from;\n
          ";
         }
        echo "
            marker".$letter."_e.yaxis.to = marker".$letter.".yaxis.from;\n
            marker".$letter."_e.yaxis.from = marker".$letter.".yaxis.from;\n
          
           /*  alert('".$letter."== to ='+marker".$letter.".yaxis.to+'--from='+marker".$letter.".yaxis.from);\n
            
            alert('".$upperLetter[$letter]."upper== to ='+marker".$upperLetter[$letter].".yaxis.to+'--from='+marker".$upperLetter[$letter].".yaxis.from);\n
alert('".$bottomLetter[$letter]."lower== to ='+marker".$bottomLetter[$letter].".yaxis.to+'--from='+marker".$bottomLetter[$letter].".yaxis.from);\n
    alert('".$letter."== to ='+marker".$letter."_e.yaxis.to+'--from='+marker".$letter."_e.yaxis.from);\n
            
            alert('".$upperLetter[$letter]."upper== to ='+marker".$upperLetter[$letter]."_e.yaxis.to+'--from='+marker".$upperLetter[$letter]."_e.yaxis.from);\n
alert('".$bottomLetter[$letter]."lower== to ='+marker".$bottomLetter[$letter]."_e.yaxis.to+'--from='+marker".$bottomLetter[$letter]."_e.yaxis.from);\n
    */
            ";

 
    echo "document.getElementById('Grade_".$letter."_value').innerHTML='('+(yaxis_val.toFixed(0))+')';\n";
   
     
      echo "document.getElementById('textbox_m_".$bottomLetter[$letter]."').value=''+((yaxis_val-1).toFixed(0))+'';\n";
      echo "document.getElementById('textbox_".$letter."').value=''+((yaxis_val).toFixed(0))+'';\n";
   
    echo "}\n";
    $max = $boundary ;
   
    $marker_count++;

}
?>

    plot.draw();
}
/* Function to update the graph when an area is chosen */
function updateMarkerImgs(yaxisTo_new,yaxisFrom_new) {


    yaxisTo = yaxisTo_new; yaxisFrom = yaxisFrom_new;
    var totalTicks = yaxisTo - yaxisFrom;
    var tickRatio = plotHeight/totalTicks;

<?php
$marker_count=0;
foreach($letters as $boundary=>$letter) {
	 if($letter=="Average") 
  $max=$boundary;
  $letter=str_replace("+","1",$letter);
    echo "
    if(yaxisTo>=marker".$letter.".yaxis.from && yaxisFrom<=marker".$letter.".yaxis.from) {
        var ticksToTop = yaxisTo - marker".$letter.".yaxis.from;
        var newY = ticksToTop*tickRatio;
        DHTMLAPI.moveTo('pointerGrade".$letter."',pointersLeft,newY+plotTop);
        DHTMLAPI.show('pointerGrade".$letter."');
    } else {
        DHTMLAPI.hide('pointerGrade".$letter."');
    }
    ";
    $max = $boundary ;
    $marker_count++;

}
?>


}


function loadXMLDoc(uri_Local,query,div_name,method)
{
var xmlhttp;
if (window.XMLHttpRequest)
  {// code for IE7+, Firefox, Chrome, Opera, Safari
  xmlhttp=new XMLHttpRequest();
  }
else
  {// code for IE6, IE5
  xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
xmlhttp.onreadystatechange=function()
  {
  if (xmlhttp.readyState==4 && xmlhttp.status==200)
    {
        if(xmlhttp.responseText.match(/error/i)){
           document.getElementById(div_name).innerHTML="ERROR!!! can't update your grades.";
        }
        else{
          document.getElementById(div_name).innerHTML=xmlhttp.responseText;
        }
    }
  else{
    if(document.getElementById('update-status')!=null){
      document.getElementById('update-status').innerHTML="<img src='<?php echo $CFG->pixpath.'/i/ajaxloader.gif' ?>' />";
    }
  }
  }
if(method.toLowerCase()=='get'){
  uri_Local = uri_Local+"?"+query;
}
xmlhttp.open(method,uri_Local,true);
xmlhttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
xmlhttp.send(query);
}
var Post = new Object();
Post.Send = function(form)
{
	
        var query = Post.buildQuery(form);
        loadXMLDoc(form.action,query,"update-status",form.method);
}

Post.OnResponse = function(xml)
{
        var results = document.createElement('div');
        document.getElementsByTagName('body')[0].appendChild(results)
        results.innerHTML = xml.firstChild.nodeValue;
}
Post.buildQuery = function(form)
{
        var query = "";
        for(var i=0; i<form.elements.length; i++)
        {
                var key = form.elements[i].name;
                var value = Post.getElementValue(form.elements[i]);
                if(key && value)
                {
                        query += key +"="+ value +"&";
                }
        }
        return query;
}
Post.getElementValue = function(formElement)
{
        if(formElement.length != null) var type = formElement[0].type;
        if((typeof(type) == 'undefined') || (type == 0)) var type = formElement.type;

        switch(type)
        {
                case 'undefined': return;

                case 'radio':
                        for(var x=0; x < formElement.length; x++)
                                if(formElement[x].checked == true)
                        return formElement[x].value;

                case 'select-multiple':
                        var myArray = new Array();
                        for(var x=0; x < formElement.length; x++)
                                if(formElement[x].selected == true)
                                        myArray[myArray.length] = formElement[x].value;
                        return myArray;

                case 'checkbox': return formElement.checked;

                default: return formElement.value;
        }
}

function toggle_grade_editor(){
  if(document.getElementById('letter_grades').style.display==""){
    document.getElementById('letter_grades').style.display="none";
  }
  else{
    document.getElementById('letter_grades').style.display="";
  }
}

</script>

<script src="eventsManager.js"></script>
<script src="DHTML3API.js"></script>
<script src="dragManager.js"></script>



<!--div id="pointerGradeA" style="cursor:n-resize;position:absolute;left:643px;top:254px;" class="draggable" title="A"><img src="btn.png" width="16" height="16" border="0" alt="A"/>A</div>

    <div id="pointerGradeB" style="cursor:n-resize;position:absolute;left:643px;top:294px;" class="draggable" title="B"><img src="btn.png" width="16" height="16" border="0" alt="B"/>B</div>
    <div id="pointerGradeC" style="cursor:n-resize;position:absolute;left:643px;top:334px;" class="draggable" title="C"><img src="btn.png" width="16" height="16" border="0" alt="C"/>C</div>
    <div id="pointerGradeD" style="cursor:n-resize;position:absolute;left:643px;top:374px;" class="draggable" title="D"><img src="btn.png" width="16" height="16" border="0" alt="D"/>D</div-->


<div id='container' style='position: ; width: 100%; top: 2px;'>
	<div id='right_comp' style='float: right; position: relative;'>


		<form id="editing" method="POST" action="index.php"
			onsubmit="Post.Send(this); ">
			<input type='hidden' id='contextid' name='contextid'
				value='<?php echo $context->id?>'> <input type='hidden' id='cid1'
				name='cid1' value='<?php echo $courseid_1?>'>
			<table class='edit_grade_table'>
				<th>Grades</th>
				<th>Min</th>
				<th>Max</th>

				<?php
				$max = 100;
				foreach ($letters as $boundary=>$letter) {
					$letter = trim($letter);
					if($letter!="Average"){
						if($letter==''){
							continue;
						}
						$ori_letter = $letter;
						$letter=str_replace("+","1",$letter);
						echo '<tr>';
						echo '<td>';
						echo '<label for="label_'.$letter.'">'.$ori_letter.'</label>';
						echo '</td>';
						echo '<td>';

						$str_textbox =  '<input type="textbox" id="textbox_'.$letter.'" name="textbox_'.$letter.'" value="'.format_float($boundary,0).'" size=5 maxlength=5  />';
						echo $str_textbox;
							
							
						echo '</td>';
						echo '<td>';
						echo '<input type="textbox" id="textbox_m_'.$letter.'" name="textbox_m_'.$letter.'" onblur="changeGrades(\''.$letter.'\')" value="'.format_float($max,0).'" size=5 maxlength=5  ';

						if($upperLetter[$letter]==null){
							echo '  ';
						}
						echo '/>';


						echo '</td>';

						echo '</tr>';
							
						$max = $boundary - 1;
					}

				}
				echo '</table>';
				echo '<div style="text-align:center;padding:1px;margin-top:0px;">';
				echo '<div id="update-status" style="overflow:auto;width:300px;"></div>';
				echo '<input  name="update" id="update" type="submit" value="Update Grades" />';
				echo '</div>';
				echo '</form>';
				?>
				</div>
				</div>

				<div id='left_comp' style='float: left;'>

					<div id="placeholder" style="width: 690px; height: 320px;"></div>

					<!--div id="overview" style="width:200px;height:76px;margin-top:20px;float:right;"></div-->
					<?php
					$max=100;
					$marker_count=0;
					foreach($letters as $boundary=>$letter) {

						$ori_letter = $letter;
						$letter=str_replace("+","1",$letter);
						//$upperLetter[$letter]=$temp;
						if($letter=="Average"){
							$ori_letter="<b>Average</b>";
							$max=$boundary;
						}
						$top = (312- (ceil($boundary)*3.0));
						if($letter=="Average"){
							$str_div = "
     <div  id='pointerGrade".$letter."' style='position:absolute;left:690px;top:".$top."px;' ";
						}else{
							$str_div = "
     <div  id='pointerGrade".$letter."' style='cursor:n-resize;position:absolute;left:690px;top:".$top."px;' ";


						}

						if($bottomLetter[$letter]!=null){
							$str_div .= " class='draggable' ";
						}
						if($letter=="Average"){
							$str_div .= " title='".$letter."'>  &nbsp; ".$ori_letter;
						}
						else{
							$str_div .= " title='".$letter."'>
<img src='btn.png' width='16' height='16' border='0' alt='".$ori_letter."'/>".$ori_letter;
						}
							
							

						echo $str_div;
						if($letter=="Average"){
							echo "<div id='Grade_".$letter."_value' style='float:right;'>(".format_float($boundary,2).")</div></div>";
						}else{
							echo "<div id='Grade_".$letter."_value' style='float:right;'>(".format_float($boundary,0).")</div></div>";

						}
						$max = $boundary - 1;


						$marker_count++;

					}

					?>



					<?php
					function compare($x, $y)
					{
						if ( $x->finalgrade == $y->finalgrade )
						return 0;
						return ( $x->finalgrade < $y->finalgrade )? 1: -1;
					}
					uasort($grades_result, 'compare');

					$user_count=0;

					foreach($grades_result as $row)
					{

						$row->finalgrade = trim($row->finalgrade);
						$row->firstname = trim($row->firstname);
						$row->lastname = trim($row->lastname);
						$row->idnumber = trim($row->idnumber);
						echo "<input id='d".$user_count."' type='hidden' value='".$row->finalgrade ."'>";

						echo "<input id='name".$user_count."' type='hidden' value='".$row->firstname ."'>";

						echo   "<input id='lname".$user_count."' type='hidden' value='".$row->lastname ."'>";
						echo   "<input id='idnum".$user_count."' type='hidden' value='".$row->idnumber ."'>";
						$user_count++;
					}

					echo "<input id='no_of_scores' type=hidden value=".$user_count.">";
					?>

					<script id="source" language="javascript" type="text/javascript">
var stu_records = document.getElementById("no_of_scores").value;

var options = {
    yaxis: { min: 0, max: 100 },
    xaxis: { ticks: 0, min: 0, max: stu_records},
    lines: { show: false},
    points: { show: true, fill: 2.9 },
    selection: { mode: "xy" },
    grid: {
        hoverable: true, clickable: true,
        markings: markers
    }
};

var plot = null;
var sdata = new Array();
var score_count = stu_records;

for(i=1;i<=score_count;i++)
{

   var  fnm="name"+(i-1);

   var lnm="lname"+(i-1);
   var idnum="idnum"+(i-1);

   var p="d"+(i-1);
  sdata[i] = new Array();
  sdata [i] [0]=i-1;

  sdata [i] [1]=parseFloat((document.getElementById(p)).value);

  sdata [i] [2]=''+(document.getElementById(fnm)).value;

  sdata [i] [3]=''+(document.getElementById(lnm)).value;
  sdata [i] [4]=''+(document.getElementById(idnum)).value;


}


plot = $.plot($("#placeholder"), [sdata], options);


$(function () {

    function showTooltip(x, y, reg,name,marks,regno) {
        $('<div id="tooltip">' + reg + ' '+name+'<br/>'+regno+'<br/>'+marks+'</div>').css( {
            position: 'absolute',
            display: 'none',
            top: y + 10,
            left: x + 10,
            border: '1px solid #faa',
            padding: '2px',
            'background-color': '#fee',
            opacity: 0.80
        }).appendTo("body").fadeIn(200);
    }

     var previousPoint = null;

     $("#placeholder").bind("plothover", function (event, pos, item) {

        if (item) {
                if (previousPoint != item.datapoint) {
                    previousPoint = item.datapoint;
                    $("#tooltip").remove();
                    var x = item.datapoint[0].toFixed(0),
                        y = item.datapoint[1].toFixed(2);
var x_int = parseInt(x)+1;
var y_int = parseInt(y)+1;
                    showTooltip(item.pageX, item.pageY,sdata[x_int][2], sdata[x_int][3], sdata[x_int][1],sdata [x_int] [4]);
                }
            }
            else {
                $("#tooltip").remove();
                previousPoint = null;
            }
    });

    $("#placeholder").bind("plotclick", function (event, pos, item) {
        if (item) {
                 } else {

        plot.setSelection({ xaxis: { from: 0, to: stu_records }, yaxis: { from: 0, to: 100 } });
        }
    });

    $("#placeholder").bind("plotselected", function (event, ranges) {
        // do the zooming
        plot = $.plot($("#placeholder"), [sdata], $.extend(true, {}, options, {
            xaxis: { min: ranges.xaxis.from, max: ranges.xaxis.to },
            yaxis: { min: ranges.yaxis.from, max: ranges.yaxis.to }
        }));
     
        updateMarkerImgs(ranges.yaxis.to, ranges.yaxis.from);
    });

  

});
</script>

					<div>
						<h3>Instructions</h3>
						<ul>
							<li>This graph shows the starting boundaries.</li>
							<li>Select an area within the graph to zoom in.</li>
							<li>Click on any empty space within graph to zoom out.</li>
							<li>Drag the adjustment images on the right side of the graph to
								change grade marker lines.</li>
						</ul>
					</div>

					<?php echo $OUTPUT->footer(); ?>