<?php

return [
    'labels' => [
        'Goods' => '商品',
        'goods' => '商品',
    ],
    'fields' => [
        'actual_price' => '实际售价',
        'group_id' => '所属分类',
        'api_hook' => '回调事件',
        'buy_prompt' => '购买提示',
        'description' => '商品描述',
        'gd_name' => '商品名称',
        'gd_description' => '商品描述',
        'gd_keywords' => '商品关键字',
        'in_stock' => '库存',
        'ord' => '排序权重',
        'other_ipu_cnf' => '其他输入框配置',
        'picture' => '商品图片',
        'retail_price' => '零售价',
        'sales_volume' => '销量',
        'type' => '商品类型',
        'buy_limit_num' => '限制单次购买最大数量',
        'wholesale_price_cnf' => '批发价配置',
        'automatic_delivery' => '自动发货',
        'manual_processing' => '人工处理',
        'delivery_source' => '发货来源',
        'delivery_source_carmis' => '本地卡密库存',
        'delivery_source_shyapi' => 'ShyAPI 兑换码库存',
        'partner_redeem_enabled' => '允许合伙人兑换',
        'shyapi_name_prefix' => 'ShyAPI 名称前缀',
        'shyapi_quota' => 'ShyAPI 固定额度',
        'shyapi_assigned_to' => 'ShyAPI 分配渠道',
        'is_open' => '是否上架',
        'coupon_id' => '可用优惠码'
    ],
    'options' => [
    ],
    'helps' => [
        'retail_price' => '可以不填写，主要用于展示',
        'picture' => '可不上传，为默认图片',
        'delivery_source' => '自动发货商品可选择本地卡密，或直接从 ShyAPI 的兑换码库存发货',
        'in_stock' => '人工处理商品使用手动库存。本地卡密自动发货会读取卡密数量。ShyAPI 自动发货会实时读取 ShyAPI 可用兑换码数量',
        'buy_limit_num' => '防止恶意刷库存，0为不限制客户单次下单最大数量',
        'partner_redeem_enabled' => '开启后，这个商品会出现在合伙人中心的佣金兑换列表里。建议只给明确用于站内兑换的套餐开启',
        'shyapi_name_prefix' => '可选。按兑换码名称前缀筛选库存，例如 10刀、月付、闲鱼。建议至少配置名称前缀或固定额度其中一项',
        'shyapi_quota' => '可选。默认按售卖面值填写，例如 10 表示 10 USD、50 表示 50 USD；系统会自动换算为 ShyAPI 内部 quota。留空或 0 表示不按额度筛选',
        'shyapi_assigned_to' => '导出兑换码后会写入这个渠道标记，便于区分 shop、xianyu 等库存去向',
        'other_ipu_cnf' => '格式为[唯一标识(英文)=输入框名字=是否必填]，例如：填写 qq_account=QQ账号=true 表示产品详情页会新增一个 [QQ账号] 输入框，客户可在其中输入 [QQ账号]，true 为必填，false 为选填。（一行一个）',
        'wholesale_price_cnf' => '例如：填写 5=3 表示客户购买 5 件或以上时，每件价格为 3 元。一行一个',

    ]
];
