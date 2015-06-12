<?php

require_once ('core.php');

/**
 * Version One Rest Response Xml Parser
 */
class VersionOneResponseParser {
	
	private $response ;
	private $debug = false ;
	private $error = false ; 

	function setDebug(){
		$this->debug = true ;
	}
	
	function __construct( $xml, $post = false ){
		
		if( $post == true ){
			$body_begin_pos = strpos ( $xml, "<?xml" );
			$head = substr ( $xml, 0, $body_begin_pos );
			$body = substr ( $xml, $body_begin_pos );
			
			if (strpos ( $head, "200 OK" )) {
				$xml = $body;
			} else {
				$this->error = $body ;
				return ;
			}
		}
		
		$this->response = XML2Array::createArray ( $xml );
		
		// error handling for unauthorized access
		if(isset($this->response['Error'])){
			$this->error = $this->response['Error']['Message'] ;
		}
	}
	
	function __destruct(){
		$this->response = array();
	}
	
	function getChildProjects() {
		if (! empty ( $this->error )) {
			return array( "error" => $this->error) ;
		}
		
		$names = $this->response ['Asset'] ['Attribute'] ['Value'];
		$ids = $this->response ['Asset'] ['Relation'] ['Asset'];
		
		$count = count ( $names );
		$output = array ();
		for($i = 0; $i < $count; $i ++) {
			if (isset ( $ids [$i] )) {
				// $pid = substr ( $ids[ $i ] ['@attributes'] ['idref'], strlen ( 'Scope:' ) );
				$pid = $ids [$i] ['@attributes'] ['idref'];
				$output [$pid] = $names [$i];
			}
		}
		
		if ($this->debug) {
			log_event ( LOG_AJAX, print_r ( $output, true ) );
		}
		
		return $output;
	}
	
	function getValues( &$defect_detail, $defect_fields, $attributes ){
		foreach ( $defect_fields as $field_key => $field_value ) {
			if ($attributes ['@attributes'] ['name'] == $field_value) {
				if (isset ( $attributes ['@value'] )) {
					$defect_detail [$field_key] = $attributes ['@value'];
				} elseif (isset ( $attributes ['Value'] )) {
					$defect_detail [$field_key] = $attributes ['Value'];
				}
				break;
			}
		}
	}
	
	function getDefectDetails( $defect_fields ) {
		if (! empty ( $this->error )) {
			return array( "error" => $this->error) ;
		}
	
		$count =  $this->response ['Assets'] ['@attributes'] ['total'];
		$defects = $this->response ['Assets'] ['Asset'] ;
	
		$defect_list = array();
		for( $i = 0; $i < $count; $i ++) {
			$defect_detail = array ();
			
			if($count == "1"){
				$defect_attribs = $defects ['Attribute'];
				$defect_relation = $defects ['Relation'];
				$defect_detail ["id"] = substr ( $defects ['@attributes'] ['id'], strlen ( 'Defect:' ) );
			}else {
				$defect_attribs = $defects [$i] ['Attribute'];
				$defect_relation = $defects [$i] ['Relation'];
				$defect_detail ["id"] = substr ( $defects [$i] ['@attributes'] ['id'], strlen ( 'Defect:' ) );
			}
			
			// get project id
			foreach ( $defect_relation as $relation ) {
				if( isset( $relation ['@attributes']['idref'] )){
					$defect_detail [ 'project_id' ] = $relation ['@attributes']['idref'] ;
					break ;
				}
			}
			
			// get all other attributes ( defect fields )
			foreach ( $defect_attribs as $attrib ) {
				$this->getValues( $defect_detail, $defect_fields, $attrib );
			}
			
			$defect_list [ $defect_detail ["id"] ] = $defect_detail ;
		}
	
		if ( $this->debug ) {
			log_event ( LOG_AJAX, print_r( $defect_list, true) );
		}
		
		return $defect_list ;
	}
		
}

