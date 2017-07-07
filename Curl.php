<?php
/*
 * Версия 1.02 от 07.07.2017
 *
 * */
namespace Kymyzek\Curl;

class Curl
{
    private $ch;
    private $page;
    private $header='';
    private $content='';
    private $header_code=0;
    private $header_location='';
    private $follow=true;
    private $encoding=false;
    private $encoding_in='';
    private $encoding_out='';

    private $defaults = array(
        'CURLOPT_SSL_VERIFYPEER'    => 0,
        'CURLOPT_SSL_VERIFYHOST'    => 0,
        'CURLOPT_ENCODING'          => '',
        'CURLOPT_RETURNTRANSFER'    => 1,
        'CURLOPT_HEADER'            => 1,
        'CURLINFO_HEADER_OUT'       => true,
        'CURLOPT_NOBODY'            => 0,
        'CURLOPT_FOLLOWLOCATION'    => 0,
        'CURLOPT_FAILONERROR'       => 0,
        'CURLOPT_COOKIESESSION'     => 1,
        'CURLOPT_AUTOREFERER'       => 0,
        'CURLOPT_POST'              => false,
        'CURLOPT_HTTPHEADER'
            =>  Array("Content-Type:application/x-www-form-urlencoded"),
        'CURLOPT_COOKIEJAR'         => 'cookies.txt',
        'CURLOPT_COOKIEFILE'        => 'cookies.txt',
    );

    function __construct($param=array())
    {
        if (empty($_SERVER['HTTP_USER_AGENT']))
            // 04.03.2017 Chrome
            $this->defaults['CURLOPT_USERAGENT'] = 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Mobile Safari/537.36';
        else
            $this->defaults['CURLOPT_USERAGENT'] = $_SERVER['HTTP_USER_AGENT'];
        $param = array_merge($this->defaults,$param);
        $this->ch = curl_init();
        foreach ($param as $key => $val)
            curl_setopt($this->ch, constant($key), $val);
        return $this->ch;
    }

    function __destruct() {
        curl_close($this->ch);
    }

    public function setHeader($headers=array()) {
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
    }

    public function setEncoding($encoding_in,$encoding_out) {
        $this->encoding = true;
        $this->encoding_in = $encoding_in;
        $this->encoding_out = $encoding_out;
    }

    public function encoding() {
        if ($this->encoding)
            $this->page = mb_convert_encoding($this->page, $this->encoding_out, $this->encoding_in);
    }

    public function set($param_name,$param_value) {
        curl_setopt($this->ch, constant($param_name), $param_value);
    }

    public function referer($url) {
        curl_setopt($this->ch, CURLOPT_REFERER, $url);
    }

    public function get($url) {
        curl_setopt($this->ch, CURLOPT_POST, false);
        curl_setopt($this->ch, CURLOPT_URL, $url);
        return $this->exec($url);
    }

    public function post($url,$fields=array()) {
        $farr = array();
        foreach ($fields as $key => $val)
            $farr[] = "$key=$val";
        $post = implode('&',$farr);
        return $this->postBody($url,$post);
    }

    public function postBody($url,$post) {
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post);
        return $this->exec($url);
    }

    public function cookies($file) {
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, $file);
        curl_setopt($this->ch, CURLOPT_COOKIEFILE,$file);
    }

    public function getPage() {
        return $this->page;
    }

    public function getContent() {
        return $this->content;
    }

    public function savePage($file) {
        return file_put_contents($file, $this->getPage());
    }

    public function saveContent($file) {
        return file_put_contents($file, $this->getContent());
    }

    public function header() {
        return $this->header;
    }

    public function headerCode() {
        return curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
    }

    public function headerLocation() {
        return curl_getinfo($this->ch, CURLINFO_REDIRECT_URL);
    }

    public function follow($value) {
        $this->follow = $value;
    }

    private function exec($url){
        $this->page = curl_exec($this->ch);
        $this->header_code = $this->headerCode();

        if (302 == $this->header_code || 301 == $this->header_code)
            $this->header_location = $this->headerLocation();
        else
            $this->header_location = '';

        $header_size = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        $this->header = substr($this->page, 0, $header_size);
        $this->content = substr($this->page, $header_size);
        if ($this->follow && $this->header_location) {
            $this->referer($url);
            $this->content = $this->get($this->header_location);
        }
        return $this->content;
    }

}
