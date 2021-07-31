<?php

use GuzzleHttp\Client;

require 'vendor/autoload.php';
require 'myclass/WecomSendClass.php';
require 'myclass/RedisUtil.php';

// 设置脚本时区
date_default_timezone_set("PRC");
// 天气推送，从中央气象局调用接口推送天气消息
$appConfig = include 'app.conf.php';
$redisConfig = include 'redis.conf.php';
$retryCount = 3;

$timeBegin = [420, 680, 1020];
$timeEnd = [480, 710, 1080];
// 获取当前小时
$nowTime = date('H');
$nowMinute = $nowTime * 60;

// 时间比较函数
function timeCompare()
{
    // 发送时间数组，包含早、中、晚三个时间段，在三个时间段中如果预报开始时间有雨则进行消息推送
    $sendTimeArr = array_combine($GLOBALS['timeBegin'], $GLOBALS['timeEnd']);

    foreach ($sendTimeArr as $beginTime => $endTime) {
        if ($GLOBALS['nowMinute'] >= $beginTime && $GLOBALS['nowMinute'] <= $endTime) {
            return true;
        }
    }
    return false;
}

// 部署云函数时注释此行
getWeatherInfo(null, null);

function getWeatherInfo($event, $context)
{
    if ($GLOBALS['retryCount'] <= 0) {
        die('重试次数已用完');
    }
    try {
        timeCompare() || die('时间还没到，不予推送！');
        $redis = RedisUtil::getInstance($GLOBALS['redisConfig']);
        $value = $redis->get('weather_send');
        if ($value) {
            die(sprintf('%s最近3小时内已经推送过消息了！', date('Y m d h:i:s')));
        }

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

天气：{$info}
    温度：{$weather['temperature']}
    温差：{$weather['temperatureDiff']}
    降雨量：{$weather['rain']}mm

刮风
    风向：{$wind['direct']}
    风力：{$wind['power']}
    风速：{$wind['speed']}
EOF;
        if (mb_stripos($info, '雨')) {
            $redis->set('weather_send', 1);
            return WecomSendClass::sendMsg($text, $config['wecom_cid'], $config['wecom_aid'], $config['wecom_secret']);
        }
        if (!in_array($GLOBALS['nowMinute'], $GLOBALS['timeEnd'])) {
            die(sprintf('%s还没到该时段结束时间！', date('Y m d h:i:s')));
        }
        $redis->set('weather_send', 1);
        return WecomSendClass::sendMsg($text, $config['wecom_cid'], $config['wecom_aid'], $config['wecom_secret']);
    } catch (Exception $exception) {
        echo $exception->getMessage() . PHP_EOL;
        $GLOBALS['retryCount']--;
        getWeatherInfo($event, $context);
    }
}
