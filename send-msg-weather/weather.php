<?php

use GuzzleHttp\Client;

require 'vendor/autoload.php';
require 'myclass/WecomSendClass.php';


// 天气推送，从中央气象局调用接口推送天气消息
$appConfig = include 'app.conf.php';

getWeatherInfo(null, null);

function getWeatherInfo($event, $context)
{
    $config = $GLOBALS['appConfig'];
    $client = new Client(['base_uri' => 'http://www.nmc.cn']);
    $response = $client->get('rest/weather?stationid=57590&_=1627110263634');
    $body = $response->getBody();
    $stringBody = (string)$body;

    // 空气质量
    $air = json_decode($stringBody, true)['data']['air'];
    // 实时天气
    $weather = json_decode($stringBody, true)['data']['real']['weather'];
    $publishTime = json_decode($stringBody, true)['data']['real']['publish_time'];
    // 实时风向
    $wind = json_decode($stringBody, true)['data']['real']['wind'];
    $text['url'] = 'http://m.nmc.cn/publish/forecast//AHB/xianning.html';
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
