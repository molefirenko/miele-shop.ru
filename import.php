<?php

class ImportXml {
    private $names = array(
        'Холодильники и морозильники',
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

    private $categories = array();

    public function get_data($url = 'https://miele-shop.ru/yam/imyandex.xml') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_FAILONERROR,1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $retValue = curl_exec($ch);          
        curl_close($ch);
        return $retValue;       
    }

    public function parse_xml($data) {
        $xml = simplexml_load_string($data);
        $categories = array();
        foreach ($xml->shop->categories->category as $category) {
            if (in_array($category[0],$this->names)) {
                $categories[] = array('name'=>(string)$category[0],'ids'=>array((int)$category['id']));
            }
        }
        $child_categories = array();
        foreach ($xml->shop->categories->category as $category) {
            if (!isset($category['parentId'])) continue;
            $id = (int)$category['id'];
            $parentId = (int)$category['parentId'];
            foreach ($categories as $key=>$cat) {
                if ($parentId == $cat['ids'][0]) {
                    $categories[$key]['ids'][] = $id;
                }
            }
        }
        $count = 0;
        $products = array();
        foreach ($xml->shop->offers->offer as $offer) {
            if ($offer['available'] == false) continue;
            $catId = $offer->categoryId;
            $skip = true;
            foreach ($categories as $category) {
                if (in_array($catId,$category['ids'])) {
                    $skip = false;
                }
            }
            if ($skip) continue;
            $products[] = 
            $count++;

        }
        print($count);
    }

    public function run_parser() {
        $data = $this->get_data();
        $this->parse_xml($data);

    }
}

$parser = new ImportXml();
$parser->run_parser();