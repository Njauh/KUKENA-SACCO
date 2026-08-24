<?php
header('Content-Type: application/json; charset=utf-8');
$host='localhost'; $db='kukena_sacco'; $user='root'; $pass='';
$conn=new mysqli($host,$user,$pass,$db);
if($conn->connect_error){http_response_code(500);echo json_encode(['success'=>false,'message'=>'Database connection failed: '.$conn->connect_error]);exit;}
$conn->set_charset('utf8mb4');
?>