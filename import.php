<?php
/*
        ◦ Холодильники
        ◦ Вытяжки
        ◦ Сушильные машины
        ◦ Стиральные машины
        ◦ Кофемашины
        ◦ Посудомойки
        ◦ Гладильные системы
        ◦ Духовые шкафы
        ◦ Микроволновые печи
        ◦ Варочные панели
        ◦ Пароварки
        ◦ Стиральные машины с сушкой
*/
$names = array(
    'Холодильники',
    'Вытяжки',
    'Сушильные машины',
    'Стиральные машины',
    'Кофемашины',
    'Посудомойки',
    'Гладильные системы',
    'Духовые шкафы',
    'Микроволновые печи',
    'Варочные панели',
    'Пароварки',
    'Стиральные машины с сушкой'
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,'https://miele-shop.ru/yam/imyandex.xml');
curl_setopt($ch, CURLOPT_FAILONERROR,1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$retValue = curl_exec($ch);          
curl_close($ch);

//echo $retValue;

$xml = simplexml_load_string($retValue);

foreach ($xml->shop->categories->category as $category) {
    if (isset($category['parentId'])) {
        continue;
    }
    if (!in_array($category[0],$names)) {
        continue;
    }
    print($category['id']);
    print(' - ');
    print($category[0]);
    print(PHP_EOL);
}
