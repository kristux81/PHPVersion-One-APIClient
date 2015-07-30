<?php

class curlSession {
	private $debug = false;
	private $error = false;
	private $ssl = false;
	private $retry = false;
	private $curl = null;
	private $request = "";
	private $response = "";
	
	function __construct($request) {
		global $g_curl_debug, 
		       $g_vone_global_user, 
		       $g_vone_global_pswd, 
		       $g_curl_cainfo, 
		       $g_curl_ssl_verify, 
		       $g_curl_retry;
		
		$this->debug = $g_curl_debug;
		$this->request = $request;
		$this->ssl = $g_curl_ssl_verify ;
		$this->retry = $g_curl_retry;
		
		$curl = curl_init ();
		curl_setopt ( $curl, CURLOPT_URL, $this->request );
		curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, true );
		
		// allows https client requests
		if ( $this->ssl ) {
			curl_setopt ( $curl, CURLOPT_CAINFO, $g_curl_cainfo );
		}
		
		// switch off ssl host verification if global setting turned off
		// CAUTION : Will make system vulnerable to MITM attacks
		else{
			curl_setopt ( $curl, CURLOPT_SSL_VERIFYHOST, 0 );
			curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, 0 );
		}

		// if ccb edit rights and valid auth token 
		if (user_is_ccb_participant ( auth_get_current_user_id () )) {
			$t_access_token = user_get_access_token ( auth_get_current_user_id () );
			curl_setopt ( $curl, CURLOPT_HTTPHEADER, array ("Authorization: Bearer " . $t_access_token) );
		}
		
		// else use BASIC Auth with System User 
		else {
			curl_setopt ( $curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
			curl_setopt ( $curl, CURLOPT_USERPWD, $g_vone_global_user . ":" . $g_vone_global_pswd );
		}
		
		// debug curl sent request
		curl_setopt ( $curl, CURLINFO_HEADER_OUT, $this->debug );
		
		$this->curl = $curl;
	}
	
	function get() {
		$this->response = curl_exec ( $this->curl );
		
		// debug messages ( based on global curl debug setting )
		$this->debugger ();
		
		// return error (prepended to response ) for diagnostics
		if (! empty ( $this->error )) {
			if ($this->ssl && $this->retry) {
				helper_turn_off_ssl ();
				$handle = new curlSession ( $this->request );
				return $handle->get ();
			}
			return $this->error . $this->response;
		}
		
		return $this->response;
	}
	
	function post($payload, $content_type = "application/xml") {
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
			log_event ( LOG_AJAX, "[REQUEST] : " . $this->request );
			
			// Headers contain user's Authorization tokens ( hence avoid logging them )
			log_event ( LOG_AJAX, "CURL HEADER : " . curl_getinfo ( $this->curl, CURLINFO_HEADER_OUT ) );
			
			if (! $this->response) {
				$this->error = curl_error ( $this->curl ) . '", [Err Code]: ' . curl_errno ( $this->curl );
				log_event ( LOG_AJAX, "[ERROR] : '" . $this->error );
			} else {
				log_event ( LOG_AJAX, "[RESPONSE] : " . $this->response );
			}
		}
	}
	
	function __destruct() {
		if ($this->curl) {
			curl_close ( $this->curl );
		}
	}
	
	/**
	 * debugger setting (for local override)
	 */
	function setDebug( $debug=true ){
		$this->debug = $debug ;
	}
}
