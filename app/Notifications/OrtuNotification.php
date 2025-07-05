<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Google\Client;
use Google\Service\FirebaseCloudMessaging;
use Illuminate\Support\Collection; // <<< TAMBAHKAN INI UNTUK collect()

class OrtuNotification extends BaseNotification
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected array $data;

    public function __construct(string $title, string $body, array $data = [])
    {
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
    }

    public function via(object $notifiable): array
    {
        return ['fcm_custom'];
    }

    public function toFcmCustom(object $notifiable): void
    {
        Log::debug('OrtuNotification: toFcmCustom dimulai untuk ID ortu: ' . ($notifiable->id_ortu ?? 'N/A'));

        if (empty($notifiable->fcm_token)) {
            Log::warning('OrtuNotification: FCM token tidak ditemukan untuk user ID: ' . ($notifiable->id_ortu ?? 'N/A'));
            return;
        }

        $fcmToken = $notifiable->fcm_token;
        $fcmApiUrl = 'https://fcm.googleapis.com/v1/projects/' . env('FIREBASE_PROJECT_ID') . '/messages:send';
        $serviceAccountJsonPath = base_path(env('FIREBASE_CREDENTIALS'));

        try {
            Log::debug('OrtuNotification: Mencoba otentikasi Google Client.');
            $client = new Client();
            $client->setAuthConfig($serviceAccountJsonPath);
            $client->addScope(FirebaseCloudMessaging::CLOUD_PLATFORM);

            $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];
            Log::debug('OrtuNotification: Access Token berhasil didapatkan.');

            $title = $this->title ?: 'Notifikasi Baru';
            $body = $this->body ?: 'Anda memiliki pemberitahuan baru.';

            // <<< PERBAIKAN DI SINI! Pastikan semua nilai di 'data' adalah string >>>
            $stringifiedData = collect($this->data)
                ->mapWithKeys(function ($value, $key) {
                    return [$key => (string) $value];
                })
                ->all();

            // Tambahkan title dan body ke data payload juga sebagai string
            $stringifiedData['title'] = (string) $title;
            $stringifiedData['body'] = (string) $body;
            // <<< AKHIR PERBAIKAN >>>

            $payload = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $stringifiedData, // Gunakan data yang sudah di-string-kan
                    'android' => [
                        'priority' => 'HIGH',
                        'notification' => [
                            'click_action' => 'MAIN_ACTIVITY_INTENT',
                            'color' => '#00FF00',
                        ],
                    ],
                ],
            ];

            Log::debug('OrtuNotification: Payload notifikasi siap dikirim:', ['payload' => $payload]); // Log payload lengkap

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($fcmApiUrl, $payload);

            Log::debug('OrtuNotification: Respons dari FCM API diterima. Status: ' . $response->status());

            if ($response->successful()) {
                Log::info('Notifikasi FCM V1 berhasil dikirim ke Firebase.', [
                    'token' => $fcmToken,
                    'title' => $title,
                    'firebase_response_status' => $response->status(),
                    'firebase_response_body' => $response->json()
                ]);
            } else {
                Log::error('Gagal mengirim notifikasi FCM V1 ke Firebase: ' . $response->body(), [
                    'token' => $fcmToken,
                    'firebase_response_code' => $response->status(),
                    'firebase_response_body' => $response->body()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('OrtuNotification: Error saat mengirim notifikasi FCM V1 (Guzzle): ' . $e->getMessage(), [
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
                'exception_trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
        ];
    }
}