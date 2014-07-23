<?php
/***********************************************************************
Cerb5 Web-API Library for PHP5
(c) Copyright 2010 WebGroup Media LLC
 
Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:
 
The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.
 
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
***********************************************************************/
 
/**
 * @author Jeff Standen <jeff@webgroupmedia.com>
 */
 
class Cerb5_WebAPI {
  private $_access_key = '';
  private $_secret_key = '';
 
  private $_url = '';
 
  private $_content_type = '';
 
  public function __construct($access_key, $secret_key) {
    $this->_access_key = $access_key;
    $this->_secret_key = $secret_key;
  }
 
  public function get($url) {
    return $this->_connect('GET', $url);
  }
 
  public function put($url, $payload) {
    return $this->_connect('PUT', $url, $payload);
  }
 
  public function post($url, $payload) {
    return $this->_connect('POST', $url, $payload);
  }
 
  public function delete($url) {
    return $this->_connect('DELETE', $url);
  }
 
  public function getContentType() {
    return $this->_content_type;
  }
 
  private function _sortQueryString($query) {
    // Strip the leading ?
    if(substr($query,0,1)=='?') $query = substr($query,1);
    $args = array();
    $parts = explode('&', $query);
    foreach($parts as $part) {
      $pair = explode('=', $part, 2);
      if(is_array($pair) && 2==count($pair))
        $args[$pair[0]] = $part;
    }
    ksort($args);
    return implode("&", $args);
  }
 
  private function _connect($verb, $url, $payload=null) {
    $header = array();
    $ch = curl_init();
 
    $verb = strtoupper($verb);
    $http_date = gmdate(DATE_RFC822);
 
    $header[] = 'Date: '.$http_date;
    $header[] = 'Content-Type: application/x-www-form-urlencoded; charset=utf-8';
 
    $postfields = '';
 
    if(!is_null($payload)) {
      if(is_array($payload)) {
        foreach($payload as $pair) {
          if(is_array($pair) && 2==count($pair))
            $postfields .= $pair[0].'='.rawurlencode($pair[1]) . '&';
        }
        rtrim($postfields,'&');
 
      } elseif (is_string($payload)) {
        $postfields = $payload;
      }
    }
 
    // HTTP verb-specific options
    switch($verb) {
      case 'DELETE':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        break;
 
      case 'GET':
        break;
 
      case 'PUT':
        $header[] = 'Content-Length: ' .  strlen($postfields);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        break;
 
      case 'POST':
        $header[] = 'Content-Length: ' .  strlen($postfields);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        break;
    }
 
    // Authentication
    $url_parts = parse_url($url);
    $url_path = $url_parts['path'];
 
    $url_query = '';
    if(isset($url_parts['query']) && !empty($url_parts))
      $url_query = $this->_sortQueryString($url_parts['query']);
 
    $secret = strtolower(md5($this->_secret_key));
 
    $string_to_sign = "$verb\n$http_date\n$url_path\n$url_query\n$postfields\n$secret\n";
    $hash = md5($string_to_sign); // base64_encode(sha1(
    $header[] = 'Cerb5-Auth: '.sprintf("%s:%s",$this->_access_key,$hash);
 
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
 
    $output = curl_exec($ch);
 
    $info = curl_getinfo($ch);
    $this->_content_type = $info['content_type'];
 
    curl_close($ch);
 
    return $output;
  }
};
