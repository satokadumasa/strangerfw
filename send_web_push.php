<?php
// define( 'API_ACCESS_KEY', 'AAAAMuyypx0:APA91bGcOP2GNgdnM596ot6fEtFvQncrYfYlKTJQgjCRjev4rc0gdPKCQpdTIxKYxLKBwab867MxLJmWyLh9ynRP417iVDPCyoyAnYMVfIAhB7IIbT14rrULLMwBx7flx56zRzLCqMQg');
define( 'API_ACCESS_KEY', 'AIzaSyD-eOVF5bcHJinjjVwz2M61v3qfgg_qMUM');
echo "CH-01\n";
$msg = array(
               'message'       => 'Wakeup Wakeup!!',
               'title'         => 'Wakeup call !',
            );
$fields = array(
          'registration_id'  => 218719495965,
          'data'              => $msg
         );
$headers = array
           (
            'Authorization: key=' . API_ACCESS_KEY,
            'Content-Type: application/json'
            );

$ch = curl_init();
echo "CH-02\n";
curl_setopt($ch,CURLOPT_URL, '//gcm-http.googleapis.com/gcm/send');
curl_setopt($ch,CURLOPT_POST, true);
curl_setopt($ch,CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode($fields));
echo "CH-03\n";
$result = curl_exec($ch);
echo "CH-04\n";
curl_close( $ch );
echo "CH-05\n";
echo "RESULT:".$result."\n";
