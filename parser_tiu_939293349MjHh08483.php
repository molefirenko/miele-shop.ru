<?php

/*

Парсер данных и yaml

http://benzotehnika.tiu.ru/yandex_market.xml?html_description=0&hash_tag=aee801b177ec446d3b701a258923af36&yandex_cpa=1&group_ids=&exclude_fields=&sales_notes=&product_ids=

*/

define("URL","http://benzotehnika.tiu.ru/yandex_market.xml?html_description=0&hash_tag=aee801b177ec446d3b701a258923af36&yandex_cpa=1&group_ids=&exclude_fields=&sales_notes=&product_ids=");
define('DB_SERVER','127.0.0.1');
define('DB_USER','admin_7cotok');
define('DB_CHARSET','utf8');
define('DB_PASS','gQoTMg9nBe');
define('DB_NAME','admin_7cokok');
error_reporting(E_ALL);
ini_set('display_errors', 'On');

class  dataParser {
  public function get_catalog() {
    //Define header array for cURL requestes
    $header = array('Contect-Type:application/xml', 'Accept:application/xml', 'Accept-Charset: windows-1251,utf-8');

    //Define base URL
    $url = URL;

    //Initialise cURL object
    $ch = curl_init();

    //Set cURL options
    curl_setopt_array($ch, array(
        CURLOPT_HTTPHEADER => $header, //Set http header options
        CURLOPT_URL => $url, //URL sent as part of the request
        CURLOPT_HTTPGET => TRUE //Set cURL to GET method
    ));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_ENCODING, '');

    //Define variable to hold the returned data from the cURL request
    $data = curl_exec($ch);

    //Close cURL connection
    curl_close($ch);

    //Print results
    return $data;
  }

  public function parse_data($str_xml) {
	if (empty($str_xml)) {
		echo "No data recieved from tiu".PHP_EOL;
	}
    $products = array();
    $xml_data = new SimpleXMLElement($str_xml);
    $offers = $xml_data->shop->offers->offer;
    foreach ($offers as $offer) {
        $products[] = array('name'=>$offer->name,'vendorCode'=>$offer->vendorCode,'price'=>$offer->price);
    }
    return $products;
  }

  public function add_to_db($products,$real_add_to_db) {
	try {
		$db = new PDO('mysql:host='.DB_SERVER.';dbname='.DB_NAME, DB_USER, DB_PASS, array( PDO::ATTR_PERSISTENT => false));
		$db->exec("set names utf8");
		$query = $db->prepare("SELECT `id_product`,`reference`,`price` FROM `ps_product`");
		$query->execute();		
	}
	catch(PDOException $e) {
        echo $e->getMessage().PHP_EOL;
        $message .= $e->getMessage().PHP_EOL;		
	}
    $vendorCodes = array();
    foreach ($products as $product) {
      $vendorCodes[] = $product['vendorCode'];
    }
    $message = 'Отчет о работе парсера:'.PHP_EOL;
    $matches = 0;
    while ($row=$query->fetch(PDO::FETCH_OBJ)){
      if (in_array($row->reference,$vendorCodes) && $row->reference !="") {
        $price = '';
        foreach ($products as $product) {
          if ($product['vendorCode'] == $row->reference) {
            $price = $product['price'];
            break;
          }
        }
        if (floatval($price) != floatval($row->price)) {
          $val = intval($price);
          if ($real_add_to_db) {
            try {
              $sql = "UPDATE `ps_product` SET `price`=".$val." WHERE `reference`='".$row->reference."'";
              $upd_query = $db->prepare($sql);
              $upd_query->execute();
              $sql = "UPDATE `ps_product_shop` SET `price`=".$val." WHERE `id_product`='".$row->id_product."'";
              $upd_query = $db->prepare($sql);
              $upd_query->execute();
              echo $upd_query->rowCount() . " records UPDATED successfully with Reference: ".$row->reference." and ID: ".$row->id_product.PHP_EOL;
              $message .= $upd_query->rowCount() . " records UPDATED successfully with Reference: ".$row->reference." and ID: ".$row->id_product.PHP_EOL;
            }
            catch(PDOException $e){
              echo $e->getMessage().PHP_EOL;
              $message .= $e->getMessage().PHP_EOL;
            }
          }
          else {
            $sql = "UPDATE `ps_product` SET `price`=".$val." WHERE `reference`='".$row->reference."'";
            echo $sql.PHP_EOL;
            $message .= $sql.PHP_EOL;
          }
          $matches++;
        }

      } else {
        if ($row->reference !="") {
          //echo "Not in list: ".$row->reference.PHP_EOL;
        }
      }
    }
    echo "Matches: ".$matches.PHP_EOL;
    $message .= "Matches: ".$matches.PHP_EOL;
    $headers = 'From: info@7-cotok.ru' . "\r\n" .
            'Reply-To: info@7-cotok.ru' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();
    mail('savinov@mediaceh.com,molefirenko@yandex.ru','Отчет парсера 7-соток.ru',$message,$headers);
  }
}

$p = new dataParser();
$str_data = $p->get_catalog();
$products = $p->parse_data($str_data);
// Не делать изменения в базе
$p->add_to_db($products,true);
// Делать изменения в базе
//$p->add_to_db($products,true);
