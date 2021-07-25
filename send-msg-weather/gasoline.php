<?php

use QL\QueryList;
use SendApp\WecomSendClass;

require __DIR__ . '/vendor/autoload.php';

// 油价推送
$config = include 'app.conf.php';

getGasolineInfo();

function getGasolineInfo()
{
    global $config;
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
    $qiyouArr = explode("\n", $qiyou);
    $priceArr = explode("\n", $price);
    $_92 = $priceArr[0];
    $_95 = $priceArr[1];

    $text['url'] = 'http://www.qiyoujiage.com/hubei/xianning.shtml';
    $text['title'] = <<<EOF
咸宁今日油价 单位:元/升
92#汽油：{$_92} | 95#汽油：{$_95}
EOF;;
    $text['description'] = '';

    foreach ($qiyouArr as $key => $item) {
        $text['description'] .= sprintf("%s:%s\n", $item, $priceArr[$key]);
    }
    if ($tip) {
        $text['description'] .= "\n提示：{$tip}";
    }
    WecomSendClass::sendMsg($text, $config['wecom_cid'], $config['wecom_aid'], $config['wecom_secret']);

}
