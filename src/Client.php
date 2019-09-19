<?php
namespace TymFrontiers\HTTP;
use \TymFrontiers\Validator;

class Client{
  const GET = 'GET';
  const PUT = 'PUT';
  const POST = 'POST';
  const PATCH = 'PATCH';
  const DELETE = 'DELETE';
  const OPTIONS = 'OPTIONS';
  const HEAD = 'HEAD';
  const TRACE = 'TRACE';

  const VERSION = "v1.x";

  protected $_data_type = "text";
  protected $_raw_param = false;
  protected $_method = "GET";
  protected $_url;

  protected $_status;
  protected $_status_code;
  protected $_header_size = 0;
  protected $_header;
  protected $_body;
  protected $_prop_vals = [
    "data_type" => ["json","text","html","css","javascript","xml"],
    "raw_param" => [false,"json","xml","text"],
    "method" => [self::GET,self::PUT,self::POST,self::PATCH,self::DELETE,self::OPTIONS,self::HEAD,self::TRACE]
  ];
  protected $_data_types = [
    "json"        => "application/json",
    "text"        => "text/plain",
    "html"        => "text/html",
    "css"         => "text/css",
    'javascript'  => 'application/javascript',
    'xml'         => 'application/xml'
  ];
  public $errors = [];


  function __construct(string $meth = self::GET, string $url="", array $params=[], array $header=[], array $options=[]){
    if( (new Validator())->url($url,["url","url"]) ){
      if( !empty($options) ){
        foreach($options as $prop=>$val){
          $this->setOpt($prop,$val);
        }
      }
      switch($meth){
        case self::POST:
          $this->POST($url,$params,$header);
          break;
        case self::PUT:
          $this->PUT($url,$params,$header);
          break;
        case self::PATCH:
          $this->PATCH($url,$params,$header);
          break;
        case self::DELETE:
          $this->DELETE($url,$params,$header);
          break;
        case self::HEAD:
          $this->HEAD($url,$params,$header);
          break;
        case self::TRACE:
          $this->TRACE($url,$params,$header);
          break;
        case self::OPTIONS:
          $this->OPTIONS($url,$params,$header);
          break;
        default:
          $this->GET($url,$params,$header);
          break;
      }
    }
  }
  public function GET(string $url, array $params=[], array $header){
    $this->_method = self::GET;
    if( !$this->_url = (new Validator())->url($url,["url","url"]) ){
      throw new \Exception("Invalid resource URL parsed.", 1);
    }
    return $this->_fetch($params,$header);
   }
  public function PUT(string $url, array $params=[], array $header){ return false; }
  public function POST(string $url, array $params=[], array $header){
    $this->_method = self::POST;
    if( !$this->_url = (new Validator())->url($url,["url","url"]) ){
      throw new \Exception("Invalid resource URL parsed.", 1);
    }
    return $this->_fetch($params,$header);
  }

  public function PATCH(string $url, array $params=[], array $header){ return false; }
  public function DELETE(string $url, array $params=[], array $header){ return false; }
  public function HEAD(string $url, array $params=[], array $header){ return false; }
  public function TRACE(string $url, array $params=[], array $header){ return false; }

  public function status(){ return $this->_status; }
  public function statusCode(){ return $this->_status_code; }
  public function header(){ return $this->_header; }
  public function body(){ return $this->_body; }
  public function setOpt(string $key, string $value){
    $key = \strtolower($key);
    // $value = \strtolower($value);
    if( \array_key_exists($key,$this->_prop_vals) && \in_array($value,$this->_prop_vals[$key]) ){
      $prop = "_{$key}";
      $this->$prop = $value;
      return true;
    }
    return false;
  }
  public function getOpt(string $key){
    if( \property_exists(__CLASS__,"_{$key}") ){
      $prop = "_{$key}";
      return $this->$prop;
    }
    return null;
  }
  private final function _fetch(array $params=[], array $header=[]){
    $response = false;
    $opts = [ "method" => "GET", "type" => "json","json_params"=>false];
    if( !empty($opt['method']) ) $opt['method'] = \strtoupper($opt['method']);

    $headers = [
      "Accept" => !\array_key_exists($this->_method, $this->_data_types)
        ? $this->_data_types['text']
        : $this->_data_types[$this->_method],
      "User-Agent" => "[{$_SERVER['HTTP_HOST']}]: Tym Frontiers HTTP Client/".self::VERSION
    ];
    if( !empty($header) ){
      foreach($header as $key=>$value){
        if( !\is_int($key) ) $headers[$key] = $value;
      }
    }
    $header = [];
    foreach($headers as $key=>$val){
      $header[] = \is_int($key) ? $val : "{$key}: {$val}";
    }

    if( !\function_exists('curl_init') ){
      $this->errors['Self'][] = [
      2, // viewer rank
      256, // error type: PHP user error
      "This request requires curl but was not found on this server.", // error message
      __FILE__, // file for errors
      __LINE__ // line for errors
      ];
      return false;
    }
    if( \in_array($this->_method,['GET','PUT']) && !empty($params) ){
      $params = \http_build_query($params);
      $this->_url .= "?".$params;
    }
    $ch = \curl_init();
    \curl_setopt($ch, CURLOPT_URL, $this->_url);
    if( \strtoupper($this->_method) == 'GET' ) \curl_setopt($ch,CURLOPT_HTTPGET,true );
    if( \strtoupper($this->_method) == 'POST' ) \curl_setopt($ch,CURLOPT_POST,true );
    if( \strtoupper($this->_method) == 'PUT' ) \curl_setopt($ch,CURLOPT_PUT,true );
    // prepare post fields
    if( $this->_method == 'POST' && !empty($params)){
      $params = ($this->_raw_param !== false && \strtolower($this->_raw_param) == 'json') ? \json_encode($params) : $params;
      \curl_setopt($ch,CURLOPT_POSTFIELDS,$params);
    }

    \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
    \curl_setopt($ch, CURLOPT_USERAGENT,  'Tym Frontiers HTTP Client/'.self::VERSION );
    \curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true );
    \curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    \curl_setopt($ch, CURLOPT_VERBOSE, true);
    // \curl_setopt($ch,CURLOPT_DEFAULT_PROTOCOL,'http');
    \curl_setopt($ch, CURLOPT_HEADER, true);
    \curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    $response = \curl_exec($ch);
    $this->_status_code = \curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $this->_status = Header::message($this->_status_code);
    $this->_header_size = \curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $this->_header = \substr($response, 0, $this->_header_size);
    $this->_body = \substr($response, $this->_header_size);
    if( \curl_errno($ch) ){
      $this->errors['Self'][] = [
        2, // viewer rank
        256, // error type
        \strip_tags( \curl_error($ch) ), // error message
        __FILE__, // file for errors
        __LINE__ // line for errors
      ];
      return false;
    }if( $this->_status_code !== 200 ){
      $this->errors['Self'][] = [
        2, // viewer rank
        256, // error type
        \substr( \strip_tags( $response ), 0, \strpos(\strip_tags( $response ), PHP_EOL)), // error message
        __FILE__, // file for errors
        __LINE__ // line for errors
      ];
      return false;
    }
    \curl_close ($ch);

    return true;
  }
}
