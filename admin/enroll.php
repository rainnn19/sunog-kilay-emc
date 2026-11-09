<?php
require_once __DIR__.'/../includes/functions.php';
role('admin');

$msg='';
$err='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    check_token();
    $course_id=(int)($_POST['course_id']??0);
    $student_id=(int)($_POST['student_id']??0);

    if(!$course_id)$err='Select a course first.';
    elseif(!$student_id)$err='Select a student.';
    else{
        $course=$conn->prepare("SELECT id,COALESCE(NULLIF(subject_code,''),title) subject_code FROM courses WHERE id=?");
        $course->bind_param('i',$course_id);
        $course->execute();
        $course_data=$course->get_result()->fetch_assoc();

        if(!$course_data)$err='Selected course was not found.';
        else{
            $eligible=$conn->prepare("SELECT u.id
                FROM users u
                WHERE u.id=? AND u.role='student' AND u.is_active=1
                AND NOT EXISTS (
                    SELECT 1
                    FROM enrollments e
                    JOIN courses enrolled_course ON enrolled_course.id=e.course_id
                    WHERE e.student_id=u.id
                    AND COALESCE(NULLIF(enrolled_course.subject_code,''),enrolled_course.title)=?
                )");
            $eligible->bind_param('is',$student_id,$course_data['subject_code']);
            $eligible->execute();

            if(!$eligible->get_result()->fetch_assoc())$err='This student has already taken that subject.';
            else{
                $save=$conn->prepare('INSERT INTO enrollments(course_id,student_id) VALUES(?,?)');
                $save->bind_param('ii',$course_id,$student_id);
                $msg=$save->execute()?'Enrollment saved.':'Could not enroll student.';
            }
        }
    }
}

$courses=$conn->query("SELECT id,COALESCE(NULLIF(subject_code,''),title) subject_code,COALESCE(NULLIF(subject_description,''),description,title) subject_description
    FROM courses ORDER BY subject_code,subject_description");
$students=$conn->query("SELECT u.id,u.name,u.last_name,u.first_name,u.middle_name,u.name_extension,u.email,
    GROUP_CONCAT(DISTINCT COALESCE(NULLIF(c.subject_code,''),c.title) SEPARATOR '|') enrolled_subjects
    FROM users u
    LEFT JOIN enrollments e ON e.student_id=u.id
    LEFT JOIN courses c ON c.id=e.course_id
    WHERE u.role='student' AND u.is_active=1
    GROUP BY u.id,u.name,u.last_name,u.first_name,u.middle_name,u.name_extension,u.email
    ORDER BY u.last_name,u.first_name,u.name");
$page_title='Enroll Students';
include __DIR__.'/../includes/header.php';
?>
<h1>Enroll Students</h1>
<div class="card">
    <?php if($msg):?><div class="alert info"><?=e($msg)?></div><?php endif;?>
    <?php if($err):?><div class="alert error"><?=e($err)?></div><?php endif;?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?=e(token())?>">
        <label>Course</label>
        <select name="course_id" id="course-select" required>
            <option value="">Select course</option>
            <?php while($c=$courses->fetch_assoc()):?>
                <option value="<?=$c['id']?>" data-subject-code="<?=e($c['subject_code'])?>">
                    <?=e($c['subject_code'].' - '.$c['subject_description'])?>
                </option>
            <?php endwhile;?>
        </select>
        <label>Student</label>
        <select name="student_id" id="student-select" required disabled>
            <option value="">Select a course first</option>
            <?php while($s=$students->fetch_assoc()):?>
                <?php $student_name=$s['last_name']?trim($s['last_name'].', '.$s['first_name'].($s['middle_name']?' '.$s['middle_name']:'').($s['name_extension']?' '.$s['name_extension']:'')):$s['name'];?>
                <option value="<?=$s['id']?>" data-enrolled-subjects="<?=e($s['enrolled_subjects']??'')?>">
                    <?=e($student_name.' · '.$s['email'])?>
                </option>
            <?php endwhile;?>
        </select>
        <button class="btn">Enroll student</button>
    </form>
</div>
<script>
var courseSelect=document.getElementById('course-select');
var studentSelect=document.getElementById('student-select');
courseSelect.addEventListener('change',function(){
    var selectedCourse=courseSelect.options[courseSelect.selectedIndex];
    var subjectCode=selectedCourse.getAttribute('data-subject-code')||'';
    studentSelect.disabled=!subjectCode;
    studentSelect.value='';
    Array.prototype.forEach.call(studentSelect.options,function(option,index){
        if(index===0)return;
        var enrolled=(option.getAttribute('data-enrolled-subjects')||'').split('|');
        option.hidden=!subjectCode||enrolled.indexOf(subjectCode)!==-1;
    });
    studentSelect.options[0].textContent=subjectCode?'Select student':'Select a course first';
});
</script>
<?php include __DIR__.'/../includes/footer.php';?>
