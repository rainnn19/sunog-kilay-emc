<?php
require_once __DIR__.'/../includes/functions.php';
role('teacher');
$err='';
$days=['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
foreach(['schedule_days'=>'VARCHAR(40)','schedule_times'=>'VARCHAR(255)'] as $field=>$definition){
    $column=$conn->query("SHOW COLUMNS FROM courses LIKE '".$conn->real_escape_string($field)."'");
    if(!$column||!$column->num_rows){
        if(!$conn->query("ALTER TABLE courses ADD COLUMN `$field` $definition")){
            $err='Could not upgrade the courses table. Add the schedule fields from database.sql, then reload this page.';
            break;
        }
    }
}
if(!$err&&$_SERVER['REQUEST_METHOD']==='POST'){
    check_token();
    $t=trim($_POST['title']??'');
    $d=trim($_POST['description']??'');
    $selected_days=array_values(array_intersect($days,(array)($_POST['schedule_days']??[])));
    $starts=(array)($_POST['time_start']??[]);
    $ends=(array)($_POST['time_end']??[]);
    $ranges=[];
    for($i=0;$i<min(count($starts),count($ends),2);$i++)if($starts[$i]!==''||$ends[$i]!=='')$ranges[]=['start'=>$starts[$i],'end'=>$ends[$i]];
    $invalid_range=array_filter($ranges,function($range){return !$range['start']||!$range['end']||$range['start']>=$range['end'];});
    $id=$_SESSION['user_id'];
    if(!$t)$err='Title is required.';
    elseif(count($selected_days)<1||count($selected_days)>2)$err='Select one or two days.';
    elseif(count($ranges)<1||count($ranges)>2||$invalid_range)$err='Enter one or two valid time ranges.';
    else{
        $schedule_days=implode(', ',$selected_days);
        $schedule_times=json_encode($ranges);
        $s=$conn->prepare('INSERT INTO courses(title,description,schedule_days,schedule_times,teacher_id) VALUES(?,?,?,?,?)');
        $s->bind_param('ssssi',$t,$d,$schedule_days,$schedule_times,$id);
        if($s->execute())go('teacher/courses.php');
        $err='Could not create course.';
    }
}
$page_title='Create Course';
include __DIR__.'/../includes/header.php';
?>
<h1>Create a course</h1>
<div class="card">
    <?php if($err):?><div class="alert error"><?=e($err)?></div><?php endif;?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?=e(token())?>">
        <label>Course title</label>
        <input name="title" value="<?=e($_POST['title']??'')?>" required>
        <label>Description</label>
        <textarea name="description"><?=e($_POST['description']??'')?></textarea>
        <label>Class days <span class="muted">(choose up to 2)</span></label>
        <div class="schedule-cards">
            <?php foreach($days as $day):?>
                <?php $day_selected=in_array($day,(array)($_POST['schedule_days']??[]),true);?>
                <button type="button" class="schedule-card<?=$day_selected?' is-selected':''?>" data-day="<?=e($day)?>" aria-pressed="<?=$day_selected?'true':'false'?>">
                    <strong><?=e($day)?></strong>
                </button>
            <?php endforeach;?>
        </div>
        <div id="selected-days">
            <?php foreach(array_values(array_intersect($days,(array)($_POST['schedule_days']??[]))) as $day):?>
                <input type="hidden" name="schedule_days[]" value="<?=e($day)?>">
            <?php endforeach;?>
        </div>
        <label>Time range 1</label>
        <div class="time-card"><input type="time" name="time_start[]" value="<?=e($_POST['time_start'][0]??'')?>" required><span>to</span><input type="time" name="time_end[]" value="<?=e($_POST['time_end'][0]??'')?>" required></div>
        <label>Time range 2 <span class="muted">(optional)</span></label>
        <div class="time-card"><input type="time" name="time_start[]" value="<?=e($_POST['time_start'][1]??'')?>"><span>to</span><input type="time" name="time_end[]" value="<?=e($_POST['time_end'][1]??'')?>"></div>
        <button class="btn">Create course</button>
    </form>
</div>
<script>
document.querySelectorAll('.schedule-card').forEach(function(card){
    card.addEventListener('click',function(){
        var selected=document.querySelectorAll('.schedule-card.is-selected');
        if(!card.classList.contains('is-selected')&&selected.length>=2){alert('Select one or two days only.');return;}
        card.classList.toggle('is-selected');
        card.setAttribute('aria-pressed',card.classList.contains('is-selected')?'true':'false');
        var selectedDays=document.getElementById('selected-days');
        selectedDays.innerHTML='';
        document.querySelectorAll('.schedule-card.is-selected').forEach(function(selectedCard){
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
