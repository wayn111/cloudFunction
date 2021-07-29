<?php

use GuzzleHttp\Client;

require 'vendor/autoload.php';
require 'myclass/WecomSendClass.php';


// 天气推送，从中央气象局调用接口推送天气消息
$appConfig = include 'app.conf.php';

// 时间比较函数
function timeCompare()
{
    // 发送时间数组，包含早、中、晚三个时间段，在三个时间段中如果预报开始时间有雨则进行消息推送
    $sendTimeArr = [
        // 420预报开始时间7点，480预报结束时间8点
        [420, 480],
        [605, 620],
        [1020, 1080],
    ];

    // 获取当前小时
    $nowTime = date('H');
    $nowMinute = $nowTime * 60;
    foreach ($sendTimeArr as $sendTime) {
        if ($nowMinute >= $sendTime[0] && $nowMinute <= $sendTime[1]) {
            return true;
        }
    }
    return false;
}

getWeatherInfo(null, null);

function getWeatherInfo($event, $context)
{
    $config = $GLOBALS['appConfig'];
    $client = new Client(['timeout' => 5]);
    $response = $client->get($config['url']);
    $body = $response->getBody();
    $stringBody = (string)$body;

    // 空气质量
    $air = json_decode($stringBody, true)['data']['air'];
    // 实时天气
    $weather = json_decode($stringBody, true)['data']['real']['weather'];
    $publishTime = json_decode($stringBody, true)['data']['real']['publish_time'];
    // 实时风向
    $wind = json_decode($stringBody, true)['data']['real']['wind'];
    $text['url'] = $config['redirect_url'];
    $info = $weather['info'];
    $text['title'] = <<<EOF
咸宁未来两小时天气：{$info}
温度：{$weather['temperature']} | 空气质量: {$air['text']}
EOF;
    $text['description'] = <<<EOF
发布时间：{$publishTime}

天气：{$weather['info']}
    温度：{$weather['temperature']}
    温差：{$weather['temperatureDiff']}
    降雨量：{$weather['rain']}mm

刮风
    风向：{$wind['direct']}
    风力：{$wind['power']}
    风速：{$wind['speed']}
EOF;
    return WecomSendClass::sendMsg($text, $config['wecom_cid'], $config['wecom_aid'], $config['wecom_secret']);
}
