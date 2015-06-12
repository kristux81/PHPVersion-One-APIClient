<?php

class curlSession {
	
	private $debug = false;
	private $curl = null;
	private $request = "";
	private $response = "";
	
	function __construct( $request ) {
		global $g_curl_debug, $g_vone_global_user, $g_vone_global_pswd, $g_login_method;
		
		$this->debug = $g_curl_debug;
		$this->request = $request;
		
		$curl = curl_init ();
		curl_setopt ( $curl, CURLOPT_URL, $this->request );
		curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, true );
		
		// allows https client requests
		curl_setopt ( $curl, CURLOPT_CAINFO, ini_get ( 'curl.cainfo' ) );
		
		// Version One Auth headers
		switch ($g_login_method) {
			
			case BASIC_AUTH :
				curl_setopt ( $curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
				curl_setopt ( $curl, CURLOPT_USERPWD, $g_vone_global_user . ":" . $g_vone_global_pswd );
				break;
			
			case ACCESS_TOKEN :
				$t_access_token = user_get_access_token ( auth_get_current_user_id () );
				curl_setopt ( $curl, CURLOPT_HTTPHEADER, array (
						"Authorization: Bearer " . $t_access_token 
				) );
				break;
		}
		
		// debug curl sent request
		curl_setopt ( $curl, CURLINFO_HEADER_OUT, $this->debug );
		
		$this->curl = $curl;
	}
	
	function get() {
		$this->response = curl_exec ( $this->curl );
		
		// debug messages ( based on global curl debug setting )
		$this->debugger ();
		
		return $this->response;
	}
	
	function post( $payload, $content_type = "application/xml" ) {
		if ($this->debug) {
			log_event ( LOG_AJAX, "POST PAYLOAD : " . $payload );
		}
		
		curl_setopt ( $this->curl, CURLOPT_CUSTOMREQUEST, "POST" );
		curl_setopt ( $this->curl, CURLOPT_POST, true );
		curl_setopt ( $this->curl, CURLOPT_POSTFIELDS, $payload );
		curl_setopt ( $this->curl, CURLOPT_HEADER, true );
		
		return $this->get ();
	}
	
	function debugger() {
		if ($this->debug) {
			log_event ( LOG_AJAX, "CURL REQUEST : " . $this->request );
			log_event ( LOG_AJAX, "CURL HEADER : " . curl_getinfo ( $this->curl, CURLINFO_HEADER_OUT ) );
			
			if (! $this->response) {
				log_event ( LOG_AJAX, "CURL ERROR : '" . curl_error ( $this->curl ) . '" - Err Code: ' . curl_errno ( $this->curl ) );
			} else {
				log_event ( LOG_AJAX, "CURL RESPONSE : " . $this->response );
			}
		}
	}
	
	function __destruct() {
		if ($this->curl) {
			curl_close ( $this->curl );
		}
	}
}
