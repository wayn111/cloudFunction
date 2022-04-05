<?php

use GuzzleHttp\Client;

/**
 * 企业微信应用消息推送class
 */
class WecomSendClass
{
    static function sendMsg($text, $wecom_cid, $wecom_aid, $wecom_secret, $wecom_touid = '@all', $errorRetryCount = 3)
    {
        if ($errorRetryCount <= 0) {
            echo "发送失败，消息重试发送次数用完！";
            return false;
        }
        $client = new Client(['base_uri' => 'https://qyapi.weixin.qq.com']);
        $response = $client->get('cgi-bin/gettoken?corpid=' . urlencode($wecom_cid) . "&corpsecret=" . urlencode($wecom_secret));
        $body = $response->getBody();
        $info = @json_decode((string)$body, true);
        if ($info && isset($info['access_token']) && strlen($info['access_token']) > 0) {
            $access_token = $info['access_token'];

            $data = new stdClass();
            $data->touser = $wecom_touid;
            $data->agentid = $wecom_aid;
            $data->msgtype = "textcard";
            $data->textcard = $text;
            $data->duplicate_check_interval = 600;
            $response = $client->post('cgi-bin/message/send?debug=1&access_token=' . urlencode($access_token)
                , [
                    'json' => json_decode(json_encode($data), true),
                ]);
            $body = $response->getBody();
            $stringBody = (string)$body;
            echo $stringBody . PHP_EOL;
            if (json_decode($stringBody, true)['errcode'] != '0') {
                $errorRetryCount--;
                self::sendMsg($text, $wecom_cid, $wecom_aid, $wecom_secret, errorRetryCount: $errorRetryCount);
            }
            return true;
        }
        return false;
    }
}
