<?php

return [
    'labels' => [
        'Goods' => '商品',
        'goods' => '商品',
    ],
    'fields' => [
        'actual_price' => '實際售價',
        'group_id' => '所屬分類',
        'api_hook' => '回調事件',
        'buy_prompt' => '購買提示',
        'description' => '商品描述',
        'gd_name' => '商品名稱',
        'gd_description' => '商品描述',
        'gd_keywords' => '商品關鍵字',
        'in_stock' => '庫存',
        'ord' => '排序權重',
        'other_ipu_cnf' => '其他輸入框配置',
        'picture' => '商品圖片',
        'retail_price' => '零售價',
        'sales_volume' => '銷量',
        'type' => '商品類型',
        'buy_limit_num' => '限製單次購買最大數量',
        'wholesale_price_cnf' => '批發價配置',
        'automatic_delivery' => '自動發貨',
        'manual_processing' => '人工處理',
        'delivery_source' => '發貨來源',
        'delivery_source_carmis' => '本地卡密庫存',
        'delivery_source_shyapi' => 'ShyAPI 兌換碼庫存',
        'partner_redeem_enabled' => '允許合夥人兌換',
        'shyapi_name_prefix' => 'ShyAPI 名稱前綴',
        'shyapi_quota' => 'ShyAPI 固定額度',
        'shyapi_assigned_to' => 'ShyAPI 分配渠道',
        'is_open' => '是否上架',
        'coupon_id' => '可用折扣碼'
    ],
    'options' => [
    ],
    'helps' => [
        'retail_price' => '可以不填寫，主要用於展示',
        'picture' => '可不上傳，為預設圖片',
        'delivery_source' => '自動發貨商品可選擇本地卡密，或直接從 ShyAPI 的兌換碼庫存發貨',
        'in_stock' => '人工處理商品使用手動庫存。本地卡密自動發貨會讀取卡密數量。ShyAPI 自動發貨會即時讀取 ShyAPI 可用兌換碼數量',
        'buy_limit_num' => '防止惡意刷庫存，0為不限製客戶單次下單最大數量',
        'partner_redeem_enabled' => '開啟後，這個商品會出現在合夥人中心的佣金兌換列表裡。建議只給明確用於站內兌換的套餐開啟',
        'shyapi_name_prefix' => '可選。按兌換碼名稱前綴篩選庫存，例如 10刀、月付、閒魚。建議至少配置名稱前綴或固定額度其中一項',
        'shyapi_quota' => '可選。預設按售賣面值填寫，例如 10 表示 10 USD、50 表示 50 USD；系統會自動換算為 ShyAPI 內部 quota。留空或 0 表示不按額度篩選',
        'shyapi_assigned_to' => '導出兌換碼後會寫入這個渠道標記，便於區分 shop、xianyu 等庫存去向',
        'other_ipu_cnf' => '格式為[唯一標識(英文)=輸入框名字=是否必填]，例如：填寫 line_account=Line賬戶=true 表示產品詳情頁會新增一個 [Line賬戶] 輸入框，客戶可在其中輸入 [Line賬戶]，true 為必填，false 為選填。（一行一個）',
        'wholesale_price_cnf' => '例如：填寫 5=3 表示客戶購買 5 件或以上時，每件價格為 3 元。一行一個',

    ]
];
