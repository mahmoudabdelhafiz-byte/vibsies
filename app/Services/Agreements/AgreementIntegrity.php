<?php
namespace App\Services\Agreements;
final class AgreementIntegrity {
 private const ORIGINAL_ORDER=['version','booking_id','customer_id','creator_profile_id','brief','price_minor','fee_minor','currency','payment_enabled'];
 public static function digest(array $terms):string {
  return hash('sha256',json_encode(self::canonicalize($terms),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
 }
 public static function verify(array $terms,string $stored):bool {
  if(hash_equals($stored,self::digest($terms)))return true;
  // Compatibility for older agreements hashed before PostgreSQL JSONB reordered keys.
  if(array_keys($terms)===[] || array_diff(array_keys($terms),self::ORIGINAL_ORDER))return false;
  $legacy=[];
  foreach(self::ORIGINAL_ORDER as $key)if(array_key_exists($key,$terms))$legacy[$key]=$terms[$key];
  return hash_equals($stored,hash('sha256',json_encode($legacy,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)));
 }
 private static function canonicalize(array $items):array {
  if(array_is_list($items))return array_map(fn($v)=>is_array($v)?self::canonicalize($v):$v,$items);
  ksort($items,SORT_STRING);
  foreach($items as &$value)if(is_array($value))$value=self::canonicalize($value);
  unset($value);
  return $items;
 }
}
