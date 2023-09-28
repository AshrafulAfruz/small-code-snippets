<?php
require 'vendor/autoload.php';
//include('simple_html_dom.php');

function curlCall($url)
{
	$url ="http://api.scraperapi.com?api_key=6d21e7ce5c3c6ae340f7d8b0e5f788bb&url=".$url; 
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, $url); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,TRUE); 
	curl_setopt($ch, CURLOPT_HEADER,TRUE); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,0); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0); 
	$response = curl_exec($ch); 
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch); 

	if($httpcode!=200)
	{
		return 0;
	}

	return $response;
}

function addMedicine($medicine_name, $strength, $group_name, $company_or_brand, $dosage_form, $pack_size, $unit_price, $medicine_type)
{
    $sql = "insert into scrapped_datas (medicine_name, strength, group_name, company_or_brand, dosage_form, pack_size, unit_price, medicine_type)
                values (:medicine_name, :strength, :group_name, :company_or_brand, :dosage_form, :pack_size, :unit_price, :medicine_type)";
    $stmt = \App\DBConnection::myQuery($sql);
    $stmt->bindValue(':medicine_name',html_entity_decode($medicine_name));
    $stmt->bindValue(':strength',html_entity_decode($strength));
    $stmt->bindValue(':group_name',html_entity_decode($group_name));
    $stmt->bindValue(':company_or_brand',html_entity_decode($company_or_brand));
    $stmt->bindValue(':dosage_form',html_entity_decode($dosage_form));
    $stmt->bindValue(':pack_size',html_entity_decode($pack_size));
    $stmt->bindValue(':unit_price',html_entity_decode($unit_price));
    $stmt->bindValue(':medicine_type',html_entity_decode($medicine_type));
    $stmt->execute();
}


$per_script = 2;

$sql="select last_processed_page from processed_pages where id=1";
$stmt = \App\DBConnection::myQuery($sql);
$stmt->execute();
$last_processed_page = $stmt->fetch(\PDO::FETCH_ASSOC)['last_processed_page'];

if(empty($last_processed_page))
    $last_processed_page=0;

for($i=1;$i<=$per_script;$i++)
{
    $page = $last_processed_page+$i;
    $url[] = 'https://medex.com.bd/brands?page='.$page;
}

// $processed_page = $last_processed_page + $per_script;
// $qry_values="";

try {
    \App\DBConnection::myDb()->beginTransaction();

	for($i=1;$i<=$per_script;$i++)
	{
		$response = curlCall($url[$i-1]);
		if($response==0)
		{
			throw new Exception('Request Failed.');
		}

		$html_dom = str_get_html($response);
		$medicine_elements = $html_dom->find('.hoverable-block');

		if($medicine_elements!== null)
		{
			$last_processed_page++;
		}

		foreach ($medicine_elements as $medicine) { 

			$medicine_name = trim($medicine->find('.data-row > .data-row-top',0)->plaintext);
			$strength_node = $medicine->find('.grey-ligten',0);
			if($strength_node!== null)
			{
				$strength = trim($strength_node->plaintext);
			}
			else
			{
				$strength = '';
			}
			
			$group_name = trim($medicine->find('.data-row-strength',0)->next_sibling()->plaintext);
			$company_or_brand = trim($medicine->find('.data-row-company',0)->plaintext);

			$link = $medicine->href;

			$details_response = curlCall($link);
			if($details_response==0)
			{
				throw new Exception('Request Failed.');
			}
			$details_page_dom = str_get_html($details_response);

			$dosage_form = trim($details_page_dom->find('.h1-subtitle',0)->plaintext);
			$pack_size_info_node = $details_page_dom->find('.pack-size-info',0);  

			if($pack_size_info_node!== null)
			{
				$pack_size = trim($pack_size_info_node->plaintext);
				$pos = strpos($pack_size, ':');
				$pack_size = substr($pack_size, 1, $pos-1);

				$unit_price = trim($details_page_dom->find('.package-container span',1)->plaintext);
				$unit_price = str_replace("৳ ", "",$unit_price);

				addMedicine($medicine_name, $strength, $group_name, $company_or_brand, $dosage_form, $pack_size, $unit_price, 4);
				//echo "block 1 *******************************************************************************<br>";
			}
			else
			{
				$multi_variant_1_node = $details_page_dom->find('.package-container',0);
				//$multi_variant_1_node = $details_page_dom->find('.packages-wrapper .package-container:nth-child(1) span',0);
				if ($multi_variant_1_node !== null) {
				    $pack_size = trim($multi_variant_1_node->find('span',0)->plaintext);
				    $pack_size = str_replace(":", "",$pack_size);

				    $unit_price = trim($multi_variant_1_node->find('span',1)->plaintext);
				    $unit_price = str_replace("৳ ", "",$unit_price);

				    addMedicine($medicine_name, $strength, $group_name, $company_or_brand, $dosage_form, $pack_size, $unit_price, 4);
				    //echo "block 2 *******************************************************************************<br>";
				}

				$multi_variant_2_node = $details_page_dom->find('.package-container',1);
				if ($multi_variant_2_node !== null) {
				    $pack_size = trim($multi_variant_2_node->find('span',0)->plaintext);
				    $pack_size = str_replace(":", "",$pack_size);

				    $unit_price = trim($multi_variant_2_node->find('span',1)->plaintext);
				    $unit_price = str_replace("৳ ", "",$unit_price);
				    addMedicine($medicine_name, $strength, $group_name, $company_or_brand, $dosage_form, $pack_size, $unit_price, 4);
				     //echo "block 3 *******************************************************************************<br>";
				   // echo $pack_size2." 22222 ".$unit_price2."<br>";
				}

				//$multi_variant_3_node = $details_page_dom->find('.packages-wrapper .package-container:nth-child(3) span',0);
				$multi_variant_3_node = $details_page_dom->find('.package-container',2);
				if ($multi_variant_3_node !== null) {
				    $pack_size = trim($multi_variant_3_node->find('span',0)->plaintext);
				    $pack_size = str_replace(":", "",$pack_size);

				    $unit_price = trim($multi_variant_3_node->find('span',1)->plaintext);
				    $unit_price = str_replace("৳ ", "",$unit_price);
				    addMedicine($medicine_name, $strength, $group_name, $company_or_brand, $dosage_form, $pack_size, $unit_price, 4);
				    //echo "block 4 *******************************************************************************<br>";
				    //echo $pack_size3." 33333 ".$unit_price3."<br>";
				}
			}
			//$clicked_page->filter('.pack-size-info');
			//echo $medicine_name." - ".$strength." - ".$group_name." - ".$company_or_brand." - ".$dosage_form." - ".$pack_size." - ".$unit_price."<br>";
		} 
	}

	$sql = "update  processed_pages set last_processed_page=:last_processed_page where id=1";
	$stmt = \App\DBConnection::myQuery($sql);
	$stmt->bindValue('last_processed_page',$last_processed_page);
	$stmt->execute();

	\App\DBConnection::myDb()->commit();
} catch (Exception $e) {
    \App\DBConnection::myDb()->rollBack();
    echo $e->getMessage();
}
