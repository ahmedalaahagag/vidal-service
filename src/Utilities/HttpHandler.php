<?php

namespace Hagag\VidalService\Utilities;
use GuzzleHttp;




class HttpHandler  {


	private $_guzzleClient;
	private $_baseUrl;
	public $closure;



	function __construct($baseUrl){
		$this->baseUrl = $baseUrl;
		$this->_guzzleClient = new GuzzleHttp\Client();
	}


	function get($url){
		try{
			$url = $this->baseUrl . $url;
			$response = $this->_guzzleClient->get($url);
			if($response->getStatusCode() == 200){
				return $response->getBody()->getContents();
			}
			return $this->_handleError($response);
		}
		catch(\Exception $e){
			return $this->_handleError($e->getMessage());
		}

	}



	function post($url, $data){
		try{
			$url = $this->baseUrl . $url;
			$response = $this->_guzzleClient->post($url, $data);
			if ($response->getStatusCode() == 200) {
	            return $response->getBody()->getContents();
	        }
	        return $this->_handleError($response);
		}
		catch(\Exception $e){
			return $this->_handleError($e->getMessage());
		}
	}


	function _handleError($response){
		return false;
	}





}


?>
