<?php
require 'db.php';
$data=json_decode(file_get_contents('php://input'),true);$id=trim($data['identifier']??'');$password=$data['password']??'';
$stmt=$conn->prepare("SELECT customer_id,full_name,national_id,phone,email,password_hash,status FROM customer WHERE email=? OR phone=? LIMIT 1");$stmt->bind_param('ss',$id,$id);$stmt->execute();$r=$stmt->get_result();
if(!$r->num_rows){echo json_encode(['success'=>false,'message'=>'Invalid email/phone or password.']);exit;}
$u=$r->fetch_assoc();if($u['status']!=='Active'||!password_verify($password,$u['password_hash'])){echo json_encode(['success'=>false,'message'=>'Invalid email/phone or password.']);exit;}
unset($u['password_hash']);echo json_encode(['success'=>true,'user'=>$u]);
?>