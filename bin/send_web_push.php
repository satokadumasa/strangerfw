<?php
// define( 'API_ACCESS_KEY', 'AAAAMuyypx0:APA91bGcOP2GNgdnM596ot6fEtFvQncrYfYlKTJQgjCRjev4rc0gdPKCQpdTIxKYxLKBwab867MxLJmWyLh9ynRP417iVDPCyoyAnYMVfIAhB7IIbT14rrULLMwBx7flx56zRzLCqMQg');
define( 'API_ACCESS_KEY', 'AIzaSyD-eOVF5bcHJinjjVwz2M61v3qfgg_qMUM');
$msg = [
           'message'       => 'Wakeup Wakeup!!',
           'title'         => 'Wakeup call !',
        ];
$fields = array(
          'registration_id'  => 218719495965,
          'data'              => $msg
         );
$headers = [
            'Authorization: key=' . API_ACCESS_KEY,
            'Content-Type: application/json'
          ];

$ch = curl_init();
curl_setopt($ch,CURLOPT_URL, '//gcm-http.googleapis.com/gcm/send');
curl_setopt($ch,CURLOPT_POST, true);
curl_setopt($ch,CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode($fields));
$result = curl_exec($ch);
curl_close( $ch );
echo "RESULT:".$result."\n";
