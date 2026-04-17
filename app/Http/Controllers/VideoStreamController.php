<?php
// Decrypted by https://bolt.pawan.krd - Educational Use Only
//
// DISCLAIMER: This file was decrypted for educational and research purposes only.
// Use of this file is at your own risk. We make no warranties, express or implied,
// regarding the accuracy, reliability, or legality of the content within. We are not
// responsible for any damages, direct or indirect, resulting from the use of this file.
// You are solely responsible for ensuring compliance with all applicable laws and
// regulations in your jurisdiction.
//
// By using this file, you acknowledge and agree to these terms.



namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tobuli\Entities\Device;

class VideoStreamController extends Controller
{
    public function show($device_id, $channel = 0)
    {
        $item = Device::find($device_id);

        if (!$item) {
            abort(404);
        }

        return view('front::Video.index', [
            'item' => $item,
            'channel' => $channel
        ]);
    }

    public function getStreamConfig($device_id)
    {
        $item = Device::find($device_id);

        if (!$item) {
            return response()->json(['error' => 'Device not found'], 404);
        }

        $cameraFields = [
            's_e_o_c_a_m_01',
            's_e_o_c_a_m_02',
            's_e_o_c_a_m_03',
            's_e_o_c_a_m_04'
        ];

        $config = [
            'streamUrls' => [],
            'streamTypes' => [],
            'playerConfig' => [
                'hls' => [
                    'enableWorker' => true,
                    'lowLatencyMode' => true,
                    'backBufferLength' => 90,
                    'maxBufferSize' => 0,
                    'maxBufferLength' => 30,
                    'liveSyncDurationCount' => 3,
                    'liveMaxLatencyDurationCount' => 5
                ],
                'flv' => [
                    'isLive' => true,
                    'enableStashBuffer' => true,
                    'stashInitialSize' => 1024 * 64,
                    'lazyLoad' => false,
                    'enableWorker' => true,
                    'autoCleanupSourceBuffer' => true,
                    'autoCleanupMaxBackwardDuration' => 30,
                    'autoCleanupMinBackwardDuration' => 15,
                    'seekType' => 'range',
                    'cors' => true,
                    'withCredentials' => false,
                    'hasAudio' => false,
                    'reuseRedirectedURL' => true
                ]
            ]
        ];

        foreach ($cameraFields as $field) {
            $url = $item->getCustomValue($field);
            if ($url) {
                $processedUrl = $this->processVideoUrl($url);
                $config['streamUrls'][] = $processedUrl;
                $config['streamTypes'][] = $this->detectStreamType($processedUrl);
            } else {
                $config['streamUrls'][] = null;
                $config['streamTypes'][] = null;
            }
        }

        return response()->json($config);
    }

    protected function processVideoUrl($url)
    {
        if (!$url) {
            return '';
        }

        try {
            $url = str_replace('&amp;', '&', $url);

            if (strpos($url, 'videoUrl=') !== false) {
                $parsedUrl = parse_url($url);
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $queryParams);
                    if (isset($queryParams['videoUrl'])) {
                        return urldecode($queryParams['videoUrl']);
                    }
                }
            }

            return trim($url);
        } catch (\Exception $e) {
            \Log::error('Error procesando URL de video: ' . $e->getMessage());
            return '';
        }
    }

    protected function detectStreamType($url)
    {
        if (!$url) return null;

        $lowerUrl = strtolower($url);

        if (strpos($lowerUrl, '.m3u8') !== false) return 'hls';
        if (strpos($lowerUrl, '.flv') !== false) return 'flv';
        if (
            strpos($lowerUrl, 'rtmp') !== false ||
            strpos($lowerUrl, '/live/') !== false ||
            strpos($lowerUrl, '/stream/') !== false
        ) return 'hls';

        return 'hls';
    }
}
