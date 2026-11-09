<?php
require_once __DIR__.'/../includes/functions.php';
role('admin');

$err='';
$professors=$conn->query("SELECT p.id,p.name,p.last_name,p.first_name,p.middle_name,p.name_extension,p.email,
    GROUP_CONCAT(
        DISTINCT CONCAT(
            COALESCE(NULLIF(c.subject_code,''),c.title),
            ' - ',
            COALESCE(NULLIF(c.subject_description,''),c.description,c.title)
        )
        ORDER BY c.subject_code,c.title SEPARATOR ' | '
    ) subjects
    FROM professors p
    LEFT JOIN courses c ON c.professor_id=p.id
    GROUP BY p.id,p.name,p.last_name,p.first_name,p.middle_name,p.name_extension,p.email
    ORDER BY p.last_name,p.first_name,p.name");
if(!$professors)$err='Could not load the professors list. Check the professor and course table schema, then reload this page.';

$page_title='Professors';
include __DIR__.'/../includes/header.php';
?>
<h1>Professors</h1>
<?php if($err):?><div class="alert error"><?=e($err)?></div><?php endif;?>
<div class="card">
    <h2>Professors and subjects</h2>
    <div class="tablewrap"><table>
        <tr><th>Professor</th><th>Email</th><th>Subject</th></tr>
        <?php if($professors): while($professor=$professors->fetch_assoc()):?>
            <?php $name=$professor['last_name']?trim($professor['last_name'].', '.$professor['first_name'].($professor['middle_name']?' '.$professor['middle_name']:'').($professor['name_extension']?' '.$professor['name_extension']:'')):$professor['name'];?>
            <tr>
                <td><?=e($name)?></td>
                <td><?=e($professor['email']??'')?></td>
                <td><?=e($professor['subjects']??'No subject assigned')?></td>
            </tr>
        <?php endwhile; endif;?>
    </table></div>
</div>
<?php include __DIR__.'/../includes/footer.php';?>
