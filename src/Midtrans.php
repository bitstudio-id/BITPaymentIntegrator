<?php

namespace BITStudio\BITPaymentIntegrator;

use Exception;

class Midtrans {

	private $serverKeyProduction;
	private $serverKeySandbox;
	private $isProduction;
	private $curlOptions;

	private $sandboxBaseUrl;
	private $productionBaseUrl;
	private $snapSanboxBaseUrl;
	private $productionSanboxBaseUrl;

    public function __construct()
    {
    	$this->serverKeySandbox = env('SERVER_KEY_SANDBOX');
    	$this->serverKeyProduction = env('SERVER_KEY_PRODUCTION');
    	$this->isProduction = env('IS_PRODUCTION');
    	$this->curlOptions = array();

    	$this->sandboxBaseUrl = env('SANDBOX_BASE_URL');
    	$this->productionBaseUrl = env('PRODUCTION_BASE_URL');
    	$this->snapSanboxBaseUrl = env('SNAP_SANDBOX_BASE_URL');
    	$this->productionSanboxBaseUrl = env('SNAP_PRODUCTION_BASE_URL');
    }

    public function config($params)
    {
        $this->serverKeyProduction = $params['server_key_production'];
        $this->serverKeySandbox = $params['server_key_sandbox'];
        $this->isProduction = $params['production'];
    }

    private function getServerKey()
    {
      	return $this->isProduction ? $this->serverKeyProduction : $this->serverKeySandbox;
    } 

    private function getBaseUrl()
    {
      	return $this->isProduction ? $this->productionBaseUrl : $this->sandboxBaseUrl;
    }   

    private function getSnapBaseUrl()
    {
      	return $this->isProduction ? $this->productionSanboxBaseUrl : $this->snapSanboxBaseUrl;
    }

    public function get($url, $server_key, $data_hash)
  	{
    	return $this->remoteCall($url, $server_key, $data_hash, false);
  	}

  	public function post($url, $server_key, $data_hash)
  	{
      	return $this->remoteCall($url, $server_key, $data_hash, true);
 	}

  	private function remoteCall($url, $server_key, $data_hash, $post = true)
    { 
      	$ch = curl_init();
      	$curl_options = array(
        	CURLOPT_URL => $url,
        	CURLOPT_HTTPHEADER => array(
          		'Content-Type: application/json',
          		'Accept: application/json',
          		'Authorization: Basic ' . base64_encode($server_key . ':')
        	),
        	CURLOPT_RETURNTRANSFER => 1,
      	);

      	if (count($this->curlOptions)) {
        	if ($this->curlOptions[CURLOPT_HTTPHEADER]) {
          		$mergedHeders = array_merge($curl_options[CURLOPT_HTTPHEADER], $this->curlOptions[CURLOPT_HTTPHEADER]);
          		$headerOptions = array( CURLOPT_HTTPHEADER => $mergedHeders );
        	} else {
          		$mergedHeders = array();
        	}
        	
        	$curl_options = array_replace_recursive($curl_options, $this->curlOptions, $headerOptions);
      	}

      	if ($post) {
        	$curl_options[CURLOPT_POST] = 1;
        	if ($data_hash) {
          		$body = json_encode($data_hash);
          		$curl_options[CURLOPT_POSTFIELDS] = $body;
        	} else {
          		$curl_options[CURLOPT_POSTFIELDS] = '';
        	}
      	}

      	curl_setopt_array($ch, $curl_options);
      	$result = curl_exec($ch);
      	$info = curl_getinfo($ch);
      	if ($result === FALSE) {
       		throw new Exception('CURL Error: ' . curl_error($ch), curl_errno($ch));
      	} else {
        	$result_array = json_decode($result);
        	if ($info['http_code'] != 200 && $info['http_code'] != 201) {
        		if (isset($result_array->status_message)) {
        			$error = $result_array->status_message;
        		} else {
        			$error = implode(',', $result_array->error_messages);
        		}

        		if (isset($result_array->status_code)) {
        			$error_code = $result_array->status_code;
        		} else {
        			$error_code = $info['http_code'];
        		}
    			
    			$message = 'Midtrans Error (' . $error_code . '): '. $error;
        		throw new Exception($message, $error_code);
      		} else {
          		return $result_array;
        	}
      	}
    }

  	public function getSnapToken($params)
  	{
    	$result = Midtrans::post(Midtrans::getSnapBaseUrl(), Midtrans::getServerKey(), $params);
    	return $result;
  	}

  	public function status($receipt)
 	{
    	return Midtrans::get(Midtrans::getBaseUrl() . '/v2/' . $receipt . '/status', Midtrans::getServerKey(), false);
  	}

  	public function approve($receipt)
  	{
    	return Midtrans::post(Midtrans::getBaseUrl() . '/v2/' . $receipt . '/approve', Midtrans::getServerKey(), false)->status_code;
  	}

  	public function cancel($receipt)
  	{
    	return Midtrans::post(Midtrans::getBaseUrl() . '/v2/' . $receipt . '/cancel', Midtrans::getServerKey(), false)->status_code;
  	}

  	public function expire($receipt)
  	{
    	return Midtrans::post(Midtrans::getBaseUrl() . '/v2/' . $receipt . '/expire', Midtrans::getServerKey(), false);
  	}

  	public function token()
  	{
    	return Midtrans::get(Midtrans::getBaseUrl() . '/v2/token', Midtrans::getServerKey(), false);
  	}
}