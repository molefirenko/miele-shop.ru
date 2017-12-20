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
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
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
        $products[] = array(
            'ID',
            'Активен',
            'Имя',
            'Категория',
            'Цена',
            'Отображать цену',
            'Бренд',
            'Штрихкод',
            'Сводка',
            'Описание',
            'URL изображений',
        );
        $pid = 1;
        foreach ($xml->shop->offers->offer as $offer) {
            if ($offer['available'] == false) continue;
            $catId = $offer->categoryId;
            $catName = '';
            $skip = true;
            foreach ($categories as $category) {
                if (in_array($catId,$category['ids'])) {
                    $skip = false;
                    $catName = $category['name'];
                }

            }
            if ($skip) continue;

            $params = array();
            $description = '';
            foreach ($offer->param as $param) {
                if (strpos($param['name'],'Описание') !== false) {
                    $description = (string)$param;
                }
            }

            $pictures = array();
            foreach($offer->picture as $picture) {
                $pictures[] = (string)$picture;
            } 
            
            $products[] = array(
                $pid,
                1,
                (string)$offer->model,
                $catName,
                (int)$offer->price,
                1,
                (string)$offer->vendor,
                (int)$offer->barcode,
                str_replace(',','<br>',(string)$offer->description),
                $description,
                implode(',',$pictures),
            );
            
            $pid++;
            $count++;

        }
        return $products;
    }

    public function create_file($data) {
        $date = new DateTime();
        $filename = 'miele'.$date->format('Ymd').'.csv';
        $file = fopen($filename,'w');
        foreach ($data as $row) {
            fputcsv($file,$row,';');
        }

        fclose($file);
    }

    public function scr_output($data) {
        foreach ($data as $single) {
            print_r($single);
            break;
        }
    }

    public function run_parser() {
        $data = $this->get_data();
        $products = $this->parse_xml($data);
        $this->create_file($products);
        // $this->scr_output($products);
        echo 'End'.PHP_EOL;
    }
}

$parser = new ImportXml();
$parser->run_parser();