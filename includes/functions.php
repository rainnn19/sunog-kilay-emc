<?php
require_once __DIR__.'/config.php';
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function go($p){header('Location: '.BASE_URL.'/'.ltrim($p,'/'));exit;}
function logged(){if(empty($_SESSION['user_id']))go('login.php');}
function role($r){logged();if(!in_array($_SESSION['role'],(array)$r,true)){http_response_code(403);exit('Access denied');}}
function token(){if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return $_SESSION['csrf'];}
function check_token(){if(!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')){http_response_code(400);exit('Invalid form token');}}
function schedule_label($days,$times,$legacy_date='',$legacy_time=''){
 $day_list=array_filter(array_map('trim',explode(',',(string)$days)));
 $ranges=json_decode((string)$times,true);
 $range_list=[];
 if(is_array($ranges))foreach($ranges as $range)if(is_array($range)&&!empty($range['start'])&&!empty($range['end']))$range_list[]=date('g:i A',strtotime($range['start'])).' - '.date('g:i A',strtotime($range['end']));
 if($day_list&&$range_list)return implode(', ',$day_list).' · '.implode(', ',$range_list);
 if($legacy_date||$legacy_time)return trim($legacy_date.' '.$legacy_time);
 return 'Schedule not set';
}
function upload_one($f,$folder){
 if(!$f||$f['error']===UPLOAD_ERR_NO_FILE)return null;
 if($f['error']!==UPLOAD_ERR_OK||$f['size']<=0||$f['size']>MAX_UPLOAD_BYTES)throw new RuntimeException('Upload failed or file exceeds 10 MB.');
 $types=['pdf'=>['application/pdf'],'doc'=>['application/msword'],'docx'=>['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],'ppt'=>['application/vnd.ms-powerpoint'],'pptx'=>['application/vnd.openxmlformats-officedocument.presentationml.presentation'],'xls'=>['application/vnd.ms-excel'],'xlsx'=>['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],'png'=>['image/png'],'jpg'=>['image/jpeg'],'jpeg'=>['image/jpeg'],'gif'=>['image/gif'],'txt'=>['text/plain'],'zip'=>['application/zip','application/x-zip-compressed']];
 $name=basename($f['name']);$ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));if(!isset($types[$ext]))throw new RuntimeException('Unsupported file type.');
 $fi=new finfo(FILEINFO_MIME_TYPE);$mime=$fi->file($f['tmp_name']);if(!in_array($mime,$types[$ext],true))throw new RuntimeException('File extension and content do not match.');
 $dir=UPLOAD_DIR.'/'.$folder;if(!is_dir($dir))mkdir($dir,0755,true);$stored=$folder.'/'.bin2hex(random_bytes(16)).'.'.$ext;
 if(!move_uploaded_file($f['tmp_name'],UPLOAD_DIR.'/'.$stored))throw new RuntimeException('Could not save uploaded file.');
 return ['original_name'=>$name,'stored_name'=>$stored,'mime_type'=>$mime,'file_size'=>(int)$f['size']];
}
