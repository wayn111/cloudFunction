<?php

use QL\QueryList;

require 'vendor/autoload.php';
require 'myclass/WecomSendClass.php';

// 设置脚本时区
date_default_timezone_set("PRC");
// 油价推送
$appConfig = include 'app.conf.php';

// 部署云函数时注释此行
getGasolineInfo(null, null);

function getGasolineInfo($event, $context)
{
    $str = getenv('spiderUrl') ?: 'http://www.qiyoujiage.com/hubei/xianning.shtml_咸宁,http://www.qiyoujiage.com/hubei/wuhan.shtml_武汉';
    $spiderUrlArr = explode(",", $str);
    $config = $GLOBALS['appConfig'];
    try {
        foreach ($spiderUrlArr as $item) {
            $base_url = explode("_", $item)[0];
            $city_name = explode("_", $item)[1];

            $ql = QueryList::get($base_url)
                ->find('#youjiaCont');
            $html = $ql->html();
            // 乱码处理
            $html = QueryList::html($html)->encoding("UTF-8", "UTF-8");
            // 获取a元素对象
            $qiyou = $html->find('#youjia dt')->text();
            $price = $html->find('#youjia dd')->text();
            // 获取提示信息
            $tip = $ql->find('div:nth-child(2)')->html();
            $tipArr = explode("<br>", $tip);
            $dateTip = $tipArr[0];
            $priceTip = QueryList::html($tipArr[1])->find('span:nth-child(1)')->text();
            $tip = sprintf('%s,%s', $dateTip, $priceTip);
            $qiyouArr = explode("\n", $qiyou);
            $priceArr = explode("\n", $price);
            $_92 = $priceArr[0];
            $_95 = $priceArr[1];

            $text['url'] = $base_url;
            $text['title'] = <<<EOF
{$city_name}油价提醒 $dateTip
92汽油:$_92 95汽油:$_95
EOF;
            $text['description'] = "单位:元/升\n";

            foreach ($qiyouArr as $key => $item) {
                $text['description'] .= sprintf("%s:%s\n", $item, $priceArr[$key]);
            }
            if ($tip) {
                $text['description'] .= "\n提示：$tip";
            }
            echo json_encode($text, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
            WecomSendClass::sendMsg($text, $config['wecom_cid'], $config['wecom_aid'], $config['wecom_secret']);
        }
    } catch (Exception $e) {
        die($e->getMessage());
    }


}
