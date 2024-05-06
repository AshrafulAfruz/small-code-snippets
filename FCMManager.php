<?php
//Google APIs Client Library for PHP
//google/apiclient:^2.15.0

namespace App;
use App\DBConnection;

define('FCM_SEND_URL', 'https://fcm.googleapis.com/v1/projects/');
define('FCM_SEND_ENDPOINT', '/messages:send');

class FCMManager {
    private $firebaseCredentials;

    public function __construct() {
        $this->loadFirebaseCredentials();
    }

    public function getNotifiableUsers($user_ids)
    {
        $token_array = [];
        foreach ($user_ids as $user_id) {
            $sql = "select token from user_device_tokens 
            where user_id=:uid and status=1";
            $stmt = DBConnection::myQuery($sql);
            $stmt->bindValue(':uid', $user_id);
            $stmt->execute();
            $token = $stmt->fetch(\PDO::FETCH_ASSOC)['token'];
            if ($token != '') {
                $token_array[] = $token;
            }
        }
        return $token_array;
    }


    public function sendFCMMessages($messagePayload, $user_ids) {
        try {
            $token_ids = $this->getNotifiableUsers($user_ids);

            $projectId = $this->getProjectId();
            $accessToken = $this->getAccessToken();

            $mh = curl_multi_init();
            $curls = [];

            foreach ($token_ids as $token) {
                $url = FCM_SEND_URL . $projectId . FCM_SEND_ENDPOINT;
                $messageData = [
                    'message' => [
                        'notification' => $messagePayload,
                        'token' => $token,
                    ],
                ];
                $ch = $this->initCurlHandle($url, $accessToken, $messageData);
                curl_multi_add_handle($mh, $ch);
                $curls[] = ['token' => $token, 'handle' => $ch];
            }

            $running = null;
            do {
                curl_multi_exec($mh, $running);
            } while ($running);

            foreach ($curls as $curlData) {
                $response = curl_multi_getcontent($curlData['handle']);
                $responseCode = curl_getinfo($curlData['handle'], CURLINFO_HTTP_CODE);
                curl_multi_remove_handle($mh, $curlData['handle']);
                curl_close($curlData['handle']);

                if ($responseCode === 200) {
                    //echo 'Message to ' . $curlData['token'] . ' sent successfully.' . PHP_EOL;
                } else {
                    //echo 'Error sending message to ' . $curlData['token'] . ': ' . $response . PHP_EOL;
                }
            }

            curl_multi_close($mh);
        } catch (Exception $e) {
            return false;
            //die('Error sending FCM messages: ' . $e->getMessage());
        }

        return true;
    }

    private function initCurlHandle($url, $accessToken, $messageData) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($messageData));
        return $ch;
    }

    private function getAccessToken() {
        $client = new \Google\Client();
        $client->setAuthConfig($this->firebaseCredentials);
        $client->addScope(\Google_Service_FirebaseCloudMessaging::CLOUD_PLATFORM);
        $client->fetchAccessTokenWithAssertion();
        return $client->getAccessToken()['access_token'];
    }

    private function getProjectId() {
        return json_decode(file_get_contents($this->firebaseCredentials), true)['project_id'];
    }

    private function loadFirebaseCredentials() { 
        $this->firebaseCredentials = "include_your_private_json_key_path_here'; //service-account-file json
    }
}
