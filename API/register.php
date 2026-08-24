<?php
require 'db.php';
$data=json_decode(file_get_contents('php://input'),true);
$name=trim($data['name']??'');$nid=trim($data['national_id']??'');$phone=trim($data['phone']??'');$email=trim($data['email']??'');$password=$data['password']??'';
if(!$name||!$nid||!$phone||!$email||!$password){echo json_encode(['success'=>false,'message'=>'All registration fields are required.']);exit;}
$check=$conn->prepare("SELECT customer_id FROM customer WHERE email=? OR phone=? OR national_id=? LIMIT 1");$check->bind_param('sss',$email,$phone,$nid);$check->execute();if($check->get_result()->num_rows){echo json_encode(['success'=>false,'message'=>'Email, phone or National ID is already registered.']);exit;}
$staff=$conn->query("SELECT staff_id FROM staff WHERE status='Active' ORDER BY staff_id LIMIT 1")->fetch_assoc();
if(!$staff){echo json_encode(['success'=>false,'message'=>'No active staff account exists. Add an active staff record first.']);exit;}
$hash=password_hash($password,PASSWORD_DEFAULT);$status='Active';
$stmt=$conn->prepare("INSERT INTO customer(national_id,full_name,phone,email,password_hash,status,staff_id) VALUES(?,?,?,?,?,?,?)");$stmt->bind_param('ssssssi',$nid,$name,$phone,$email,$hash,$status,$staff['staff_id']);
if($stmt->execute())echo json_encode(['success'=>true,'message'=>'Account created successfully.']);else echo json_encode(['success'=>false,'message'=>'Registration failed: '.$stmt->error]);
?>