<?php
require_once __DIR__.'/../includes/functions.php';
role('admin');

$msg='';
$err='';

foreach(['last_name'=>'VARCHAR(80)','first_name'=>'VARCHAR(80)','middle_name'=>'VARCHAR(80)','name_extension'=>'VARCHAR(20)'] as $field=>$definition){
    $column=$conn->query("SHOW COLUMNS FROM users LIKE '".$conn->real_escape_string($field)."'");
    if(!$column||!$column->num_rows){
        if(!$conn->query("ALTER TABLE users ADD COLUMN `$field` $definition")){
            $err='Could not upgrade the users table. Add the name fields from database.sql, then reload this page.';
            break;
        }
    }
}

if(!$err&&$_SERVER['REQUEST_METHOD']==='POST'){
    check_token();
    $last_name=trim($_POST['last_name']??'');
    $first_name=trim($_POST['first_name']??'');
    $middle_name=trim($_POST['middle_name']??'');
    $name_extension=trim($_POST['name_extension']??'');
    $em=trim($_POST['email']??'');
    $pw=$_POST['password']??'';
    $ro=$_POST['role']??'student';
    $n=trim($last_name.', '.$first_name.($middle_name?' '.$middle_name:'').($name_extension?' '.$name_extension:''));
    if(!$last_name||!$first_name||!filter_var($em,FILTER_VALIDATE_EMAIL)||strlen($pw)<8||!in_array($ro,['admin','teacher','student'],true)){
        $err='Check details. Last name and first name are required, and password must be at least 8 characters.';
    }else{
        $hash=password_hash($pw,PASSWORD_DEFAULT);
        $s=$conn->prepare('INSERT INTO users(name,last_name,first_name,middle_name,name_extension,email,password,role) VALUES(?,?,?,?,?,?,?,?)');
        if(!$s){
            $err='Could not prepare the account query. Check the users table schema and reload this page.';
        }else{
            $s->bind_param('ssssssss',$n,$last_name,$first_name,$middle_name,$name_extension,$em,$hash,$ro);
            $msg=$s->execute()?'Account created.':'Could not create account; email may already exist.';
        }
    }
}

if(isset($_GET['toggle'])){
    $id=(int)$_GET['toggle'];
    if($id!=$_SESSION['user_id']){
        $s=$conn->prepare('UPDATE users SET is_active=1-is_active WHERE id=?');
        $s->bind_param('i',$id);
        $s->execute();
    }
    go('admin/users.php');
}

$users=$err?false:$conn->query('SELECT id,name,last_name,first_name,middle_name,name_extension,email,role,is_active FROM users ORDER BY last_name,first_name,name');
if(!$users&&!$err)$err='Could not load user accounts. Check the users table schema and reload this page.';
$page_title='Manage Users';
include __DIR__.'/../includes/header.php';
?>
<h1>Manage Users</h1>
<?php if($msg):?><div class="alert info"><?=e($msg)?></div><?php endif;?>
<?php if($err):?><div class="alert error"><?=e($err)?></div><?php endif;?>
<div class="card">
    <h2>Create account</h2>
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
        <input type="email" name="email" value="<?=e($_POST['email']??'')?>" required>
        <label>Temporary password (8+ characters)</label>
        <input type="password" name="password" minlength="8" required>
        <label>Role</label>
        <select name="role"><option>student</option><option>teacher</option><option>admin</option></select>
        <button class="btn">Create account</button>
    </form>
</div>
<div class="card">
    <h2>Accounts</h2>
    <div class="tablewrap"><table>
        <tr><th>Last name</th><th>First name</th><th>Middle name</th><th>Extension</th><th>Email</th><th>Role</th><th>Status</th><th>Action</th></tr>
        <?php while($u=$users->fetch_assoc()):?>
            <tr>
                <td><?=e($u['last_name']?:$u['name'])?></td>
                <td><?=e($u['first_name']??'')?></td>
                <td><?=e($u['middle_name']??'')?></td>
                <td><?=e($u['name_extension']??'')?></td>
                <td><?=e($u['email'])?></td>
                <td><?=e($u['role'])?></td>
                <td><?=$u['is_active']?'Active':'Inactive'?></td>
                <td><?php if($u['id']!=$_SESSION['user_id']):?><a href="?toggle=<?=$u['id']?>" onclick="return confirm('Change status?')">Toggle</a><?php endif;?></td>
            </tr>
        <?php endwhile;?>
    </table></div>
</div>
<?php include __DIR__.'/../includes/footer.php';?>
