<?php
include('connect.php');


if(isset($_POST['view'])){

// $con = mysqli_connect("localhost", "root", "", "notif");

if($_POST["view"] != '')
{
    $update_query = "UPDATE afront_contact SET status = 'Y' WHERE status='N'";
    mysqli_query($con, $update_query);
}
$query = "SELECT * FROM afront_contact ORDER BY id DESC LIMIT 5";
$result = mysqli_query($con, $query);
$output = '';
if(mysqli_num_rows($result) > 0)
{
 while($row = mysqli_fetch_array($result))
 {
   $output .= '
   <li>
   <a href="#">
   <strong>'.$row["f_name"] . "  " . $row["l_name"] .'</strong><br />
   <small><em>'.$row["phone"]. " เวลาติดต่อ " .  $row["time_contact"].'</em></small>
   </a>
   </li>
   ';

 }
}
else{
     $output .= '
     <li><a href="#" class="text-bold text-italic">No Noti Found</a></li>';
}


$status_query = "SELECT * FROM afront_contact WHERE status='N'";
$result_query = mysqli_query($con, $status_query);
$count = mysqli_num_rows($result_query);
$data = array(
    'notification' => $output,
    'unseen_notification'  => $count
);

echo json_encode($data);

}

?>