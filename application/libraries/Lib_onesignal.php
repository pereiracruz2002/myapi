<?php 
class Lib_onesignal
{
    private $onesignal_id = '778b8cdc-ae20-4301-98db-79cd9466b087';
    private $onesignal_key = 'MzAwNzI0YzItMDhhMC00ZDNlLTg2NzYtODZlYjg4NTQwZjYw';

    public function __construct() 
    {
    }

    public function send($users, $msg, $data = array()) 
    {
        $fields = array(
                      'app_id' => $this->onesignal_id,
                      'include_player_ids' => $users,
                      'data' => $data,
                      'contents' => array(
                          "en" => $msg
                      )
                  );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Basic '.$this->onesignal_key));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response);
    } 
}
