<?php
require_once("config.php");

/* Funzioni generiche */
function random_number($lenght){
    $string = "";
    for($i=0;$i<$lenght;$i++){
        $string .= mt_rand(0, 9);
    }
    return $string;
}


/* Funzioni di sicurezza */
function sha512($x){
    return hash("sha512",$x);
}

function password_encrypt($password){
$options = ['cost' => 14,];
$sha512 = hash('sha512', $password);
return password_hash($sha512, PASSWORD_BCRYPT, $options);
}

function password_check($password,$hash){
$sha512 = hash('sha512', $password);
if (password_verify($sha512, $hash)){
return 1;  
}else{   
return 0;   
}}

function csrf(){
    if (isset($_SESSION['csrf'])){
        return htmlentities($_SESSION['csrf']);
    }else{
        $_SESSION['csrf'] = random_number(32);
        return htmlentities($_SESSION['csrf']);
    }
}

function csrf_check($input){
    if ($input===csrf()){
        return true;
	}else{
        return false;
    }
}


/* Amazon API */
function generateRequestUrl($keyword)
{
    $uri = "/onca/xml";

    $params = array(
        "Service" => "AWSECommerceService",
        "Operation" => "ItemSearch",
        "AWSAccessKeyId" => $GLOBALS['access_key'],
        "AssociateTag" => $GLOBALS['associative_tag'],
        "SearchIndex" => "All",
        "Keywords" => $keyword,
        "ResponseGroup" => "Images,ItemAttributes,Offers"
    );
    
    // Set current timestamp if not set
    if (!isset($params["Timestamp"])) 
    {
        $params["Timestamp"] = gmdate('Y-m-d\TH:i:s\Z');
    }
    
    // Sort the parameters by key
    ksort($params);
    
    $pairs = array();
    
    foreach ($params as $key => $value) 
    {
        array_push($pairs, rawurlencode($key)."=".rawurlencode($value));
    }
    
    // Generate the canonical query
    $canonical_query_string = join("&", $pairs);
    
    // Generate the string to be signed
    $string_to_sign = "GET\n".$GLOBALS['endpoint']."\n".$uri."\n".$canonical_query_string;
    
    // Generate the signature required by the Product Advertising API
    $signature = base64_encode(hash_hmac("sha256", $string_to_sign, $GLOBALS['secret_key'], true));
    
    // Generate the signed URL
    $request_url = 'https://'.$GLOBALS['endpoint'].$uri.'?'.$canonical_query_string.'&Signature='.rawurlencode($signature);

    //echo $request_url;

    return $request_url;
}

function getXmlDocument($asin)
{
    $request_url = generateRequestUrl($asin);
    $response = file_get_contents($request_url);

    if($http_response_header[0] != "HTTP/1.1 200 OK")
    {
        return; //errore, forse troppe richieste?
    }

    $xml = new DOMDocument();
    $xml->loadXML($response);

    return $xml;
}

function isAvailable($asin)
{
    $xml = getXmlDocument($asin);

    $errorsNode =  $xml->getElementsByTagName("Items")[0]->getElementsByTagName("Request")[0]->getElementsByTagName("Errors")[0];
    if(isset($errorsNode))
    {
        if($errorsNode->getElementsByTagName("Error")[0]->getElementsByTagName("Code")[0]->nodeValue == "AWS.ECommerceService.NoExactMatches")
        {
            return false; //non disponibile
        }
        //probabilmente dovrei implementare qualche altro controllo per altri tipi di errore
    }

    return true;
}

function getProductPrice($asin, $searchUsed = false)
{
    $xml = getXmlDocument($asin);

    $lowestNewPriceNode = $xml->getElementsByTagName("Items")[0]->getElementsByTagName("Item")[0]->getElementsByTagName("OfferSummary")[0]->getElementsByTagName("LowestNewPrice")[0];
    $lowestNewPriceValue =  $lowestNewPriceNode->getElementsByTagName("Amount")[0]->nodeValue;
    
    if($searchUsed)
    {
        $lowestUsedPriceNode = $xml->getElementsByTagName("Items")[0]->getElementsByTagName("Item")[0]->getElementsByTagName("OfferSummary")[0]->getElementsByTagName("LowestUsedPrice")[0];
        if(isset($lowestUsedPriceNode))
        {
            $lowestUsedPriceValue = $lowestUsedPriceNode->getElementsByTagName("Amount")[0]->nodeValue;
        }
    }

    $lowestPrice = $lowestNewPriceValue;
    $conditions = "new";
    
    if(isset($lowestUsedPriceValue))
    {
        if(isset($lowestNewPriceValue))
        {
            if($lowestUsedPriceValue < $lowestNewPriceValue)
            {
                $lowestPrice = $lowestUsedPriceValue;
                $conditions = "used";
            }
        }
        else
        {
            $lowestPrice = $lowestUsedPriceValue;
            $conditions = "used";
        }
    }

    return array("price" => $lowestPrice, "conditions" => $conditions);
}

function getProductImagesUrl($asin)
{
    $xml = getXmlDocument($asin);
    $largeImage = $xml->getElementsByTagName("Items")[0]->getElementsByTagName("Item")[0]->getElementsByTagName("LargeImage")[0]->getElementsByTagName("URL")[0]->nodeValue; //500*500px
    $mediumImage = $xml->getElementsByTagName("Items")[0]->getElementsByTagName("Item")[0]->getElementsByTagName("MediumImage")[0]->getElementsByTagName("URL")[0]->nodeValue; //160*160px
    $smallImage = $xml->getElementsByTagName("Items")[0]->getElementsByTagName("Item")[0]->getElementsByTagName("SmallImage")[0]->getElementsByTagName("URL")[0]->nodeValue; //75*75px

    return array("small"=>$smallImage, "medium"=>$mediumImage, "large"=>$largeImage);
}


?>