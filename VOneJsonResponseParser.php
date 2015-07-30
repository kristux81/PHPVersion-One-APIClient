<?php

/**
 * Version One Rest Response Json Parser
 */
class VersionOneJsonResponseParser {
	
	private $response ;
	private $debug = false ;
	private $error = false ; 
	
	function __construct( $json ){
		global $g_vone_parser_debug ;
		
		if (preg_match ( '/(<html>)/', $json )) {
			$this->error = $json;
			return;
		}
		
		$this->debug = $g_vone_parser_debug;
		
		$fixed_json = preg_replace ( '/{}/', '"new stdClass()"', $json );
		$this->response = json_decode ( $fixed_json, true );
		
		if ($this->debug) {
			log_event ( LOG_AJAX, "DECODED JSON RESPONSE : " . print_r ( $this->response, true ) );
		}
	}
	
	function __destruct(){
		$this->response = array();
	}
	
	function setDebug( $debug=true ){
		$this->debug = $debug ;
	}
	
	function getFieldHistory( $field ){
		if (empty ( $this->response ) && $this->error != false) {
			return array (
					"error" => $this->error 
			);
		}
		
		$output = array ();
		foreach ( $this->response as $item ) {
			
			$target = $item ['body'] ['target'];
			$time = $item ['body'] ['time'];
			$user = $item ['body'] ['actor'] ['username'];
			
			foreach ( $target as $changeSet ) {
				
				if ($changeSet ['name'] == $field) {
					$output [] = array (
							"value" => $changeSet ['newValue'],
							"user" => $user,
							"time" => $time 
					);
				}
			}
		}
		
		if ($this->debug) {
			log_event ( LOG_AJAX, "EXTRACTED FIELD HISTORY : " . print_r ( $output, true ) );
		}
		
		return $output;
	}
	
}

