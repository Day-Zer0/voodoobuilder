<?php
require_once("config.php");

/*
* SHA512
* @param (string) $x - password to encrypt
* @return (string) $x - password encrypted
* @example : sha512("abc");
*/
function sha512($x){
   return hash("sha512",$x);
}

/*
* BCRYPT ENCRYPT PASSWORD
* @param (string) $password - password to encrypt
* @return (string) $password - password encrypted
* @example : password_encrypt("abc");
*/
function password_encrypt($password){
$options = ['cost' => 14,];
$sha512 = hash('sha512', $password);
return password_hash($sha512, PASSWORD_BCRYPT, $options);
}



/*
* BCRYPT DECRYPT PASSWORD
* @param (string) $password - password to check
* @param (string) $hash - hash in the DB
* @return (boolean)
*/
function password_check($password,$hash){
$sha512 = hash('sha512', $password);
if (password_verify($sha512, $hash)){
return 1;  
}else{   
return 0;   
}}

/*
* RANDOM NUMBER
* @param (integer) $lenght - lenght of random number to generate
* @return (string) $text - random number generated
* @example random_number(4) return "9372"
*/
function random_number($lenght){
	$string = "";
	for($i=0;$i<$lenght;$i++){
		$string .= mt_rand(0, 9);
	}
	return $string;
}

/* 
* GET TOKEN CSRF
* @return (string) $token - Token csrf
* @example : csrf() return "12312312312312312312312312312312"
*/
function csrf(){
	if (isset($_SESSION['csrf'])){
		return htmlentities($_SESSION['csrf']);
	}else{
		$_SESSION['csrf'] = random_number(32);
		return htmlentities($_SESSION['csrf']);
	}
}


/*
* CHECK TOKEN CSRF 
* @param (string) $input - token reiceved to convalidate
* @return (boolean) $output - True / False
* @example csrf_check("1234") return "false"
*/
function csrf_check($input){
	if ($input===csrf()){
		return true;
	}else{
		return false;
	}
}
?>