<?php

return [
    // 企业ID
    'wecom_cid' => 'wwef3723c8dfa0cc9d',
    // 应用ID
    'wecom_aid' => '1000002',
    // 应用密钥
    'wecom_secret' => 'S0mrF93VEmCNiz9wVsbcQq4mJZcie4r7cMvskjeBoN0',
    // 城市列表
    'cityList' => [
        [
            'name' => 'wuhan',
            'url' => 'http://www.nmc.cn/rest/weather?stationid=57494&_=1627110263634',
        ],
        [
            'name' => 'xianning',
            'url' => 'http://www.nmc.cn/rest/weather?stationid=57590&_=1627110263634'
        ]
    ],
    // 消息跳转链接
    'redirect_url' => 'http://m.nmc.cn'
];

