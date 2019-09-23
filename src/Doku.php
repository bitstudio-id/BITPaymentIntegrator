<?php

namespace BITStudio\BITPaymentIntegrator;

use Exception;

class Doku {

	private $isProduction;
	private $curlOptions;

	private $dokuMallidSandbox;
	private $dokuSharedkeySandbox;
	private $dokuUrlSandbox;
	
	private $dokuMallidProduction;
	private $dokuSharedkeyProduction;
	private $dokuUrlProduction;
	
	public function __construct()
    {
		$this->isProduction = env('DOKU_IS_PRODUCTION');
		$this->curlOptions  = array();

		$this->dokuMallidSandbox    = env('DOKU_MALLID_SANDBOX');
		$this->dokuSharedkeySandbox = env('DOKU_SHAREDKEY_SANDBOX');
		$this->dokuUrlSandbox       = env('DOKU_URL_SANDBOX');

		$this->dokuMallidProduction    = env('DOKU_MALLID_PRODUCTION');
		$this->dokuSharedkeyProduction = env('DOKU_SHAREDKEY_PRODUCTION');
		$this->dokuUrlProduction       = env('DOKU_URL_PRODUCTION');
    }

    public function config($params)
    {
		$this->dokuMallidProduction    = $params['mallid_production'];
		$this->dokuSharedkeyProduction = $params['shared_key_production'];

		$this->dokuMallidSandbox    = $params['mallid_sandbox'];
		$this->dokuSharedkeySandbox = $params['shared_key_sandbox'];
		$this->isProduction         = $params['production'];
    }

    private function getMallId()
    {
      	return $this->isProduction ? $this->dokuMallidProduction : $this->dokuMallidSandbox;
    }

    private function getSharedKey()
    {
      	return $this->isProduction ? $this->dokuSharedkeyProduction : $this->dokuSharedkeySandbox;
    }

    private function getBaseUrl()
    {
      	return $this->isProduction ? $this->dokuUrlProduction : $this->dokuUrlSandbox;
    }

    public function generateWordsKey($price, $receipt)
    {
    	$words_key = sha1($price.'.00'.Doku::getMallId().''.Doku::getSharedKey().''.$receipt);
    	return $words_key;
    }

    public function generateBasket($product)
    {
    	$basket = '';
    	foreach ($product as $key => $value) {
			$value['name']  = str_replace(',', '-', $value['name']);
			$value['qty']   = preg_replace( '/[^0-9]/', '', $value['qty']);
			$value['price'] = preg_replace( '/[^0-9]/', '', $value['price']);

    		$basket .= $value['name'].','.$value['price'].'.00,'.$value['qty'].','.($value['price'] * $value['qty']).'.00;';
    	}

    	return $basket;
    }
}