<?php
namespace App\Services\Creators;
final class PortfolioVideoUrl {
 public static function valid(string $value):bool {
  if(strlen($value)>500 || preg_match('/[\x00-\x20\x7f]/',$value))return false;
  $url=parse_url($value);
  if(!is_array($url) || ($url['scheme']??'')!=='https' || isset($url['user'],$url['pass'])
   || isset($url['user']) || isset($url['pass']) || isset($url['port']) || isset($url['fragment']))return false;
  $host=strtolower($url['host']??'');$path=$url['path']??'';
  if(in_array($host,['youtu.be'],true))return (bool)preg_match('~^/[A-Za-z0-9_-]{11}/?$~D',$path);
  if(in_array($host,['youtube.com','www.youtube.com'],true)){
   if($path!=='/watch')return false;
   parse_str($url['query']??'',$args);
   return isset($args['v']) && is_string($args['v']) && (bool)preg_match('/^[A-Za-z0-9_-]{11}$/D',$args['v']);
  }
  if(in_array($host,['vimeo.com','www.vimeo.com'],true))
   return (bool)preg_match('~^/[0-9]{6,12}/?$~D',$path);
  return false;
 }
}
