<?php


namespace App\Http\Traits;



use App\Jobs\NotificationJob;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;

trait NotificationTrait
{
    private function initializeAccessToken()
    {
        try {
            $jsonKeyPath = base_path(config('services.firebase.service_account_json'));
            $jsonKey = json_decode(file_get_contents($jsonKeyPath), true);
            $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

            $credentials = new ServiceAccountCredentials($scopes, $jsonKey);
            $credentials->fetchAuthToken();

            return  $credentials->getLastReceivedToken();
        } catch (\Exception $e) {
            // Handle exceptions appropriately here
            return $e->getMessage();
        }

    }
    public function sendFCMNotification($array_to, $title, $message,$type, $data = null, $message_type = null,$extraTokens = [],$topics = [],$datetime = null)
    {
        $job = new NotificationJob($array_to, $title, $message, $type, $data, $message_type, $extraTokens, $topics);

        if ($datetime) {
            $delay = \Carbon\Carbon::parse($datetime, 'Asia/Riyadh')->utc();
            $job->delay($delay); // ✅ Jobs DO have delay() via the Queueable trait
        }

        dispatch($job)->onQueue('notifications'); // ✅ onQueue() on PendingDispatch

//        $projectId = env('FIREBASE_PROJECT_ID');
//        $accessToken = $this->initializeAccessToken();
//        $tokens = UserToken::whereIn("user_id", $array_to)->where('type', $type)->
//        pluck('token')->toArray();
//        foreach ($tokens as $token) {
//            try{
//                $notificationData = [
//                    "message" => [
//                        "token" => $token,
//                        "notification" => [
//                            "title" => $title,
//                            "body" => $message,
//                        ],
//                        "data" => [
//                            "title" => $title,
//                            "body" => $message,
//                            "data_" => json_encode($data),
//                            "message_type_" => $message_type,
//                        ],
//                    ],
//                ];
//                $headers = [
//                    'Authorization: Bearer ' . $accessToken['access_token'],
//                    'Content-Type: application/json',
//                ];
//                $ch = curl_init();
//                curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/".$projectId."/messages:send");
//                curl_setopt($ch, CURLOPT_POST, true);
//                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notificationData));
//                $response = curl_exec($ch);
//                echo $response;
//
//            }catch (\Exception $e){
//
//            }
//        }
    }
    public function subscribeToTopic(array $tokens, string $topic, string $type = 'add')
    {
        $accessToken = $this->initializeAccessToken();

        $url = $type === 'add'
            ? 'https://iid.googleapis.com/iid/v1:batchAdd'
            : 'https://iid.googleapis.com/iid/v1:batchRemove';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken['access_token'],
            'Content-Type: application/json',
            'access_token_auth: true',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'to'                  => '/topics/' . $topic,
            'registration_tokens' => $tokens,
        ]));

        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }
}
