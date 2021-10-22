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

// 时间比较函数
function timeCompare($nowMinute)
{
    // 发送时间数组，包含早、中、晚三个时间段，在三个时间段中如果预报开始时间有雨则进行消息推送
    $sendTimeArr = array_combine($GLOBALS['timeBegin'], $GLOBALS['timeEnd']);

    foreach ($sendTimeArr as $beginTime => $endTime) {
        if ($nowMinute >= $beginTime && $nowMinute <= $endTime) {
            return true;
        }
    }
    return false;
}

// 部署云函数时注释此行
// getWeatherInfo(null, null);

function getWeatherInfo($event, $context)
{
    if ($GLOBALS['retryCount'] <= 0) {
        die('重试次数已用完');
    }
    $redis = RedisUtil::getInstance($GLOBALS['redisConfig']);
    try {
        // 获取当前分钟数
        $nowMinute = date('H') * 60 + date('i');
        timeCompare($nowMinute) || die('当前分钟数：' . $nowMinute . '，时间还没到，不予推送！');
        $config = $GLOBALS['appConfig'];
        $client = new Client(['timeout' => 5]);
        foreach ($config['cityList'] as $city) {
            $url = $city['url'];
            $cityName = $city['name'];
            $key = 'weather_send' . '_' . $cityName;
            $value = $redis->get($key);
            if ($value) {
                print_r(sprintf('%s最近3小时内已经推送过消息了！', date('Y m d h:i:s')));
                continue;
            }

            $response = $client->get($url);
            $body = $response->getBody();
            $stringBody = (string)$body;

            $area = json_decode($stringBody, true)['data']['real']['station'];
            $province = $area['province'];
            $city = $area['city'];

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
{$province} {$city}未来两小时天气：{$info}
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
                WecomSendClass::sendMsg($text, $config['wecom_cid'], $config['wecom_aid'], $config['wecom_secret']);
                $redis->set($key, 1);
                $redis->expire($key, 60 * 60 * 3);  // 保存3小时
                continue;
            }
            if (!in_array($nowMinute, $GLOBALS['timeEnd'])) {
                print_r(sprintf('时间：%s，当前分钟数：%s，还没到该时段结束时间！', date('Y-m-d h:i:s'), $nowMinute));
                continue;
            }
            WecomSendClass::sendMsg($text, $config['wecom_cid'], $config['wecom_aid'], $config['wecom_secret']);
            $redis->set($key, 1);
            $redis->expire($key, 60 * 60 * 3);  // 保存3小时
        }
    } catch (Exception $exception) {
        echo $exception->getMessage() . PHP_EOL;
        $GLOBALS['retryCount']--;
        getWeatherInfo($event, $context);
    }
}
