<?php

use QL\QueryList;

require 'vendor/autoload.php';
require 'myclass/WecomSendClass.php';


// 油价推送
$appConfig = include 'app.conf.php';

getGasolineInfo(null, null);

function getGasolineInfo($event, $context)
{
    $config = $GLOBALS['appConfig'];
    $ql = QueryList::get('http://www.qiyoujiage.com/hubei/xianning.shtml')
        ->find('#youjiaCont');
    $html = $ql->html();
    // 乱码处理
    $html = QueryList::html($html)->encoding("UTF-8", "UTF-8");
    // 获取a元素对象
    $qiyou = $html->find('#youjia dt')->text();
    $price = $html->find('#youjia dd')->text();
    // 获取提示信息
    $tip = $ql->find('div:nth-child(2)')->text();
    $tipArr = explode("\r\n", $tip);
    $dateTip = $tipArr[0];
    $priceTip = $tipArr[1];
    $tip = sprintf('%s,%s', $dateTip, $priceTip);
    $qiyouArr = explode("\n", $qiyou);
    $priceArr = explode("\n", $price);
    $_92 = $priceArr[0];
    $_95 = $priceArr[1];

    $text['url'] = 'http://www.qiyoujiage.com/hubei/xianning.shtml';
    $text['title'] = <<<EOF
咸宁油价提醒 92#汽油:{$_92} 95#汽油:{$_95}
{$dateTip}
EOF;
    $text['description'] = "单位:元/升\n";

    foreach ($qiyouArr as $key => $item) {
        $text['description'] .= sprintf("%s:%s\n", $item, $priceArr[$key]);
    }
    if ($tip) {
        $text['description'] .= "\n提示：{$tip}";
    }
    WecomSendClass::sendMsg($text, $config['wecom_cid'], $config['wecom_aid'], $config['wecom_secret']);

}
