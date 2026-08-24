<?php
require 'db.php';
$o=trim($_GET['origin']??'');$d=trim($_GET['destination']??'');$date=$_GET['date']??'';
if(!$o||!$d||!$date){echo json_encode(['success'=>false,'message'=>'Origin, destination and date are required.']);exit;}
if($date<date('Y-m-d')){echo json_encode(['success'=>false,'message'=>'Travel date cannot be in the past.']);exit;}
$sql="SELECT s.schedule_id,s.departure_time,s.arrival_time,v.registration_number,v.capacity,
CASE WHEN r.origin='Kerugoya' AND r.destination='Nairobi' THEN 500
     WHEN r.origin='Kutus' AND r.destination='Nairobi' THEN 450
     WHEN r.origin='Nairobi' AND r.destination='Kerugoya' THEN 500
     WHEN r.origin='Nairobi' AND r.destination='Kutus' THEN 450
     ELSE 500 END AS fare_amount,r.origin,r.destination,
(v.capacity-COALESCE((SELECT COUNT(*) FROM booking b WHERE b.schedule_id=s.schedule_id AND b.travel_date=?),0)) AS remaining_seats
FROM schedule s JOIN vehicle v ON v.vehicle_id=s.vehicle_id
JOIN route r ON r.route_id=s.route_id
WHERE r.origin=? AND r.destination=? AND DATE(s.departure_time)=? AND s.status='Scheduled'
ORDER BY s.departure_time";
$stmt=$conn->prepare($sql);$stmt->bind_param('ssss',$date,$o,$d,$date);$stmt->execute();$res=$stmt->get_result();$rows=[];while($x=$res->fetch_assoc())$rows[]=$x;
echo json_encode(['success'=>true,'schedules'=>$rows]);
?>