<?php
require_once __DIR__.'/../includes/functions.php';
role('admin');

$msg='';
$err='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    check_token();
    $last_name=trim($_POST['last_name']??'');
    $first_name=trim($_POST['first_name']??'');
    $middle_name=trim($_POST['middle_name']??'');
    $name_extension=trim($_POST['name_extension']??'');
    $email=trim($_POST['email']??'');
    $name=trim($last_name.', '.$first_name.($middle_name?' '.$middle_name:'').($name_extension?' '.$name_extension:''));
    if(!$last_name||!$first_name)$err='Last name and first name are required.';
    elseif($email&&!filter_var($email,FILTER_VALIDATE_EMAIL))$err='Enter a valid email address.';
    else{
        $s=$conn->prepare('INSERT INTO professors(name,last_name,first_name,middle_name,name_extension,email) VALUES(?,?,?,?,?,?)');
        if(!$s){
            $err='The professors table is missing the new name fields. Run the professor upgrade SQL in database.sql, then reload this page.';
        }else{
            $s->bind_param('ssssss',$name,$last_name,$first_name,$middle_name,$name_extension,$email);
            if($s->execute())$msg='Professor added successfully.';
            else $err='Could not add professor.';
        }
    }
}

$professors=$conn->query('SELECT id,name,last_name,first_name,middle_name,name_extension,email FROM professors ORDER BY last_name,first_name');
if(!$professors)$err='The professors table is missing the new name fields. Run the professor upgrade SQL in database.sql, then reload this page.';
$page_title='Add Professor';
include __DIR__.'/../includes/header.php';
?>
<h1>Add Professor</h1>
<?php if($msg):?><div class="alert success"><?=e($msg)?></div><?php endif;?>
<?php if($err):?><div class="alert error"><?=e($err)?></div><?php endif;?>
<div class="card">
    <h2>Professor details</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?=e(token())?>">
        <label>Last name</label>
        <input name="last_name" value="<?=e($_POST['last_name']??'')?>" required>
        <label>First name</label>
        <input name="first_name" value="<?=e($_POST['first_name']??'')?>" required>
        <label>Middle name</label>
        <input name="middle_name" value="<?=e($_POST['middle_name']??'')?>">
        <label>Name extension</label>
        <input name="name_extension" placeholder="Jr., Sr., III" value="<?=e($_POST['name_extension']??'')?>">
        <label>Email</label>
        <input type="email" name="email" value="<?=e($_POST['email']??'')?>">
        <button class="btn">Add professor</button>
    </form>
</div>
<div class="card">
    <h2>Professors</h2>
    <div class="tablewrap"><table>
        <tr><th>Last name</th><th>First name</th><th>Middle name</th><th>Extension</th><th>Email</th></tr>
        <?php if($professors): while($professor=$professors->fetch_assoc()):?>
            <tr><td><?=e($professor['last_name']?:$professor['name'])?></td><td><?=e($professor['first_name']??'')?></td><td><?=e($professor['middle_name']??'')?></td><td><?=e($professor['name_extension']??'')?></td><td><?=e($professor['email']??'')?></td></tr>
        <?php endwhile; endif;?>
    </table></div>
</div>
<?php include __DIR__.'/../includes/footer.php';?>
