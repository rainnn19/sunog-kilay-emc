<?php
require_once __DIR__.'/../includes/functions.php';
role('admin');

$msg='';
$err='';
$editing=null;
$edit_id=(int)($_GET['edit']??0);

foreach(['subject_code'=>'VARCHAR(40)','subject_description'=>'TEXT','units'=>'DECIMAL(4,1)','schedule_date'=>'DATE','schedule_time'=>'TIME','schedule_days'=>'VARCHAR(40)','schedule_times'=>'VARCHAR(255)','professor_id'=>'INT NULL'] as $field=>$definition){
    $column=$conn->query("SHOW COLUMNS FROM courses LIKE '".$conn->real_escape_string($field)."'");
    if(!$column||!$column->num_rows){
        if(!$conn->query("ALTER TABLE courses ADD COLUMN `$field` $definition")){
            $err='Could not upgrade the courses table. Add the course fields from database.sql, then reload this page.';
            break;
        }
    }
}
if(!$err){
    foreach(['last_name'=>'VARCHAR(80)','first_name'=>'VARCHAR(80)','middle_name'=>'VARCHAR(80)','name_extension'=>'VARCHAR(20)'] as $field=>$definition){
        $column=$conn->query("SHOW COLUMNS FROM professors LIKE '".$conn->real_escape_string($field)."'");
        if(!$column||!$column->num_rows){
            if(!$conn->query("ALTER TABLE professors ADD COLUMN `$field` $definition")){
                $err='Could not upgrade the professors table. Add the professor fields from database.sql, then reload this page.';
                break;
            }
        }
    }
}

if(!$err&&$edit_id){
    $s=$conn->prepare('SELECT id,title,description,subject_code,subject_description,units,schedule_date,schedule_time,schedule_days,schedule_times,professor_id FROM courses WHERE id=?');
    $s->bind_param('i',$edit_id);
    $s->execute();
    $editing=$s->get_result()->fetch_assoc();
    if(!$editing)$err='Course not found.';
}

if(!$err&&$_SERVER['REQUEST_METHOD']==='POST'){
    check_token();
    $course_id=(int)($_POST['course_id']??0);
    $subject_code=trim($_POST['subject_code']??'');
    $subject_description=trim($_POST['subject_description']??'');
    $units=$_POST['units']??'';
    $schedule_date=$_POST['schedule_date']??'';
    $schedule_time=$_POST['schedule_time']??'';
    $schedule_days=array_values(array_intersect(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'],(array)($_POST['schedule_days']??[])));
    $starts=(array)($_POST['time_start']??[]);
    $ends=(array)($_POST['time_end']??[]);
    $schedule_ranges=[];
    for($i=0;$i<min(count($starts),count($ends),2);$i++)if($starts[$i]!==''||$ends[$i]!=='')$schedule_ranges[]=['start'=>$starts[$i],'end'=>$ends[$i]];
    $schedule_days_value=implode(', ',$schedule_days);
    $schedule_times_value=json_encode($schedule_ranges);
    $professor_id=(int)($_POST['professor_id']??0);
    $professor=$conn->prepare('SELECT id FROM professors WHERE id=?');
    $professor->bind_param('i',$professor_id);
    $professor->execute();

    if(!$subject_code)$err='Subject code is required.';
    elseif($units===''||!is_numeric($units)||$units<0)$err='Units must be a valid number.';
    elseif(count($schedule_days)<1||count($schedule_days)>2)$err='Select one or two days.';
    elseif(count($schedule_ranges)<1||count($schedule_ranges)>2)$err='Enter one or two time ranges.';
    elseif(count(array_filter($schedule_ranges,function($range){return !$range['start']||!$range['end']||$range['start']>=$range['end'];})))$err='Each time range must have an end time after its start time.';
    elseif(!$professor->get_result()->fetch_assoc())$err='Select a professor.';
    else{
        if($course_id){
            $s=$conn->prepare('UPDATE courses SET title=?,description=?,subject_code=?,subject_description=?,units=?,schedule_days=?,schedule_times=?,professor_id=?,teacher_id=NULL WHERE id=?');
            $s->bind_param('sssssssii',$subject_code,$subject_description,$subject_code,$subject_description,$units,$schedule_days_value,$schedule_times_value,$professor_id,$course_id);
        }else{
            $s=$conn->prepare('INSERT INTO courses(title,description,subject_code,subject_description,units,schedule_days,schedule_times,professor_id) VALUES(?,?,?,?,?,?,?,?)');
            $s->bind_param('sssssssi',$subject_code,$subject_description,$subject_code,$subject_description,$units,$schedule_days_value,$schedule_times_value,$professor_id);
        }
        $ok=$s->execute();
        if($ok)go('admin/courses.php');
        $err=$course_id?'Could not update course.':'Could not create course.';
    }
    $editing=['id'=>$course_id,'subject_code'=>$subject_code,'subject_description'=>$subject_description,'units'=>$units,'schedule_days'=>$schedule_days_value,'schedule_times'=>$schedule_times_value,'professor_id'=>$professor_id];
}

$professors=$err?false:$conn->query('SELECT id,name,last_name,first_name,middle_name,name_extension,email FROM professors ORDER BY last_name,first_name');
$courses=$err?false:$conn->query("SELECT c.id,c.subject_code,c.subject_description,c.units,c.schedule_date,c.schedule_time,c.schedule_days,c.schedule_times,COALESCE(NULLIF(CONCAT_WS(' ',NULLIF(p.last_name,''),NULLIF(p.first_name,''),NULLIF(p.middle_name,''),NULLIF(p.name_extension,'')),''),p.name,u.name) professor FROM courses c LEFT JOIN professors p ON p.id=c.professor_id LEFT JOIN users u ON u.id=c.teacher_id ORDER BY c.subject_code,c.title");
if(!$err&&(!$professors||!$courses))$err='Could not load courses. Check the course and professor table schema, then reload this page.';
$editing_ranges=is_array($editing)?json_decode((string)($editing['schedule_times']??''),true):[];
$editing_ranges=is_array($editing_ranges)?$editing_ranges:[];
$editing_days=is_array($editing)?array_map('trim',explode(',',(string)($editing['schedule_days']??''))):[];
$page_title='Manage Courses';
include __DIR__.'/../includes/header.php';
?>
<h1>Manage Courses</h1>
<?php if($msg):?><div class="alert success"><?=e($msg)?></div><?php endif;?>
<?php if($err):?><div class="alert error"><?=e($err)?></div><?php endif;?>
<div class="card">
    <h2><?=($editing?'Edit course':'Add course')?></h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?=e(token())?>">
        <?php if($editing):?><input type="hidden" name="course_id" value="<?=$editing['id']?>"><?php endif;?>
        <label>Subject code</label>
        <input name="subject_code" value="<?=e($editing['subject_code']??$editing['title']??'')?>" required>
        <label>Subject description</label>
        <textarea name="subject_description" required><?=e($editing['subject_description']??$editing['description']??'')?></textarea>
        <label>Units</label>
        <input type="number" name="units" min="0" step="0.5" value="<?=e($editing['units']??'')?>" required>
        <label>Days (select up to 2)</label>
        <div class="schedule-cards" id="schedule-days">
            <?php foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day):?>
                <?php $day_selected=in_array($day,$editing_days,true);?>
                <button type="button" class="schedule-card<?=$day_selected?' is-selected':''?>" data-day="<?=e($day)?>" aria-pressed="<?=$day_selected?'true':'false'?>">
                    <strong><?=e($day)?></strong>
                </button>
            <?php endforeach;?>
        </div>
        <div id="selected-days">
            <?php foreach($editing_days as $day):?>
                <?php if(in_array($day,['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'],true)):?>
                    <input type="hidden" name="schedule_days[]" value="<?=e($day)?>">
                <?php endif;?>
            <?php endforeach;?>
        </div>
        <label>Time range 1</label>
        <div><input type="time" name="time_start[]" value="<?=e($editing_ranges[0]['start']??'')?>" required> to <input type="time" name="time_end[]" value="<?=e($editing_ranges[0]['end']??'')?>" required></div>
        <label>Time range 2 (optional)</label>
        <div><input type="time" name="time_start[]" value="<?=e($editing_ranges[1]['start']??'')?>"> to <input type="time" name="time_end[]" value="<?=e($editing_ranges[1]['end']??'')?>"></div>
        <label>Professor</label>
        <select name="professor_id" required>
            <option value="">Select professor</option>
            <?php if($professors): while($professor=$professors->fetch_assoc()):?>
                <option value="<?=$professor['id']?>" <?=((int)($editing['professor_id']??0)===(int)$professor['id']?'selected':'')?>><?=e(($professor['last_name']?$professor['last_name'].', '.$professor['first_name'].' '.$professor['middle_name'].' '.$professor['name_extension']:$professor['name']).($professor['email']?' · '.$professor['email']:''))?></option>
            <?php endwhile; endif;?>
        </select>
        <button class="btn"><?=($editing?'Save changes':'Add course')?></button>
        <?php if($editing):?><a class="btn soft" href="<?=BASE_URL?>/admin/courses.php">Cancel</a><?php endif;?>
    </form>
</div>
<div class="card">
    <h2>Courses</h2>
    <div class="tablewrap"><table>
        <tr><th>Subject code</th><th>Description</th><th>Units</th><th>Date and time</th><th>Professor</th><th>Action</th></tr>
        <?php if($courses): while($course=$courses->fetch_assoc()):?>
            <tr><td><?=e($course['subject_code'])?></td><td><?=e($course['subject_description'])?></td><td><?=e($course['units'])?></td><td><?=e(schedule_label($course['schedule_days'],$course['schedule_times'],$course['schedule_date'],$course['schedule_time']))?></td><td><?=e($course['professor']??'')?></td><td><a href="?edit=<?=$course['id']?>">Edit</a></td></tr>
        <?php endwhile; endif;?>
    </table></div>
</div>
<script>
document.querySelectorAll('#schedule-days .schedule-card').forEach(function(card){
    card.addEventListener('click',function(){
        var selected=document.querySelectorAll('#schedule-days .schedule-card.is-selected');
        if(!card.classList.contains('is-selected')&&selected.length>=2){alert('Select one or two days only.');return;}
        card.classList.toggle('is-selected');
        card.setAttribute('aria-pressed',card.classList.contains('is-selected')?'true':'false');
        var selectedDays=document.getElementById('selected-days');
        selectedDays.innerHTML='';
        document.querySelectorAll('#schedule-days .schedule-card.is-selected').forEach(function(selectedCard){
            var input=document.createElement('input');
            input.type='hidden';
            input.name='schedule_days[]';
            input.value=selectedCard.getAttribute('data-day');
            selectedDays.appendChild(input);
        });
    });
});
</script>
<?php include __DIR__.'/../includes/footer.php';?>
