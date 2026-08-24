<?php
require 'db.php';
$data=json_decode(file_get_contents('php://input'),true);$cid=(int)($data['customer_id']??0);$sid=(int)($data['schedule_id']??0);$date=$data['travel_date']??'';$seats=$data['seats']??[];
if(!$cid||!$sid||!$date||!is_array($seats)||!count($seats)){echo json_encode(['success'=>false,'message'=>'Invalid booking details.']);exit;}
if($date<date('Y-m-d')){echo json_encode(['success'=>false,'message'=>'Travel date cannot be in the past.']);exit;}
$seats=array_values(array_unique(array_map('intval',$seats)));$conn->begin_transaction();
try{
$q=$conn->prepare("SELECT v.capacity, CASE WHEN r.origin='Kerugoya' AND r.destination='Nairobi' THEN 500 WHEN r.origin='Kutus' AND r.destination='Nairobi' THEN 450 WHEN r.origin='Nairobi' AND r.destination='Kerugoya' THEN 500 WHEN r.origin='Nairobi' AND r.destination='Kutus' THEN 450 ELSE 500 END fare FROM schedule s JOIN vehicle v ON v.vehicle_id=s.vehicle_id JOIN route r ON r.route_id=s.route_id WHERE s.schedule_id=? FOR UPDATE");$q->bind_param('i',$sid);$q->execute();$r=$q->get_result();if(!$r->num_rows)throw new Exception('Schedule not found.');$info=$r->fetch_assoc();$cap=(int)$info['capacity'];$fare=(float)$info['fare'];
foreach($seats as $seat){if($seat<1||$seat>$cap)throw new Exception("Seat $seat is invalid.");$q=$conn->prepare("SELECT booking_id FROM booking WHERE schedule_id=? AND travel_date=? AND seat_number=? LIMIT 1");$q->bind_param('isi',$sid,$date,$seat);$q->execute();if($q->get_result()->num_rows)throw new Exception("Seat $seat has just been booked. Please choose another seat.");}
$bookingIds=[];$ins=$conn->prepare("INSERT INTO booking(customer_id,schedule_id,travel_date,seat_number,fare_amount) VALUES(?,?,?,?,?)");
foreach($seats as $seat){$ins->bind_param('iisid',$cid,$sid,$date,$seat,$fare);$ins->execute();$bookingIds[]=$ins->insert_id;}
$amount=$fare*count($seats);$method='Mpesa';$code='PENDING-'.$bookingIds[0];$status='Pending';$pay=$conn->prepare("INSERT INTO payment(booking_id,amount,payment_method,transaction_code,status) VALUES(?,?,?,?,?)");$pay->bind_param('idsss',$bookingIds[0],$amount,$method,$code,$status);$pay->execute();
$conn->commit();echo json_encode(['success'=>true,'booking_id'=>$bookingIds[0],'booking_ids'=>$bookingIds,'amount'=>$amount,'payment_status'=>'Pending']);
}catch(Exception $e){$conn->rollback();echo json_encode(['success'=>false,'message'=>$e->getMessage()]);}
?>