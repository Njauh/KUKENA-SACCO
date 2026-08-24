<?php
require 'db.php';
$s=(int)($_GET['schedule_id']??0);$date=$_GET['travel_date']??'';
if(!$s||!$date){echo json_encode(['success'=>false,'message'=>'Schedule and travel date are required.']);exit;}
$q=$conn->prepare("SELECT v.capacity FROM schedule s JOIN vehicle v ON v.vehicle_id=s.vehicle_id WHERE s.schedule_id=? LIMIT 1");$q->bind_param('i',$s);$q->execute();$r=$q->get_result();if(!$r->num_rows){echo json_encode(['success'=>false,'message'=>'Schedule not found.']);exit;}$cap=(int)$r->fetch_assoc()['capacity'];
$q=$conn->prepare("SELECT seat_number FROM booking WHERE schedule_id=? AND travel_date=?");$q->bind_param('is',$s,$date);$q->execute();$r=$q->get_result();$booked=[];while($x=$r->fetch_assoc())$booked[]=(int)$x['seat_number'];
echo json_encode(['success'=>true,'capacity'=>$cap,'booked_seats'=>$booked,'remaining'=>$cap-count($booked)]);
?>