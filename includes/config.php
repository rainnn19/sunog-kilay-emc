<?php
if(session_status()===PHP_SESSION_NONE) session_start();
$conn=new mysqli('localhost','root','','sunog_kilay_emc');
if($conn->connect_error) die('Database connection failed. Check includes/config.php.');
$conn->set_charset('utf8mb4');
define('BASE_URL','/sunog-kilay-emc');
define('UPLOAD_DIR',dirname(__DIR__).'/uploads');
define('MAX_UPLOAD_BYTES',10*1024*1024);
if(!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR,0755,true);
