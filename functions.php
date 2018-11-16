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
?>