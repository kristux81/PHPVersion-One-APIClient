<?php

require_once ('core.php');
require_once ('XML2Array.php');


/**
 * Version One Rest Response Xml Parser
 */
class VersionOneResponseParser {
	
	private $response ;
	private $debug = false ;
	private $error = false ; 
	
	
	/**
	 * constructor
	 */
	public function __construct( $xml, $post = false ){
		global $g_vone_parser_debug ;
		
		$this->debug = $g_vone_parser_debug ;
		
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
		
		try{
			$this->response = XML2Array::createArray ( $xml );
		}catch (Exception $e){
			$this->error = $xml ;
		}
		
		// error handling for unauthorized access
		if(isset($this->response['Error'])){
			$this->error = $this->response['Error']['Message'] ;
		}
	}
	
	/**
	 * destructor
	 */
	public function __destruct(){
		$this->response = array();
	}

	/**
	 * debugger setting (for local override)
	 * 
	 * $debug = true / false 
	 */
	function setDebug( $debug=true ){
		$this->debug = $debug ;
	}
	
	
	/* ---- PRIVATE MEMBERS ----- */
	
	private function getAttributeValues( &$defect_detail, $defect_fields, $attributes ){
		
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
	
	private function getRelationAttribs( &$defect_detail, $resource_fields, $relations ){
		
		foreach ( $resource_fields as $field_key => $field_value ) {
			if ($relations ['@attributes'] ['name'] == $field_value) {
	
				$assets = $relations ['Asset'];
				if (isset ( $assets [0] )) {
					$t_asset_list = array ();
					foreach ( $assets as $asset ) {
						if (isset ( $asset ['@attributes'] ['idref'] )) {
							$t_asset_list [] = $asset ['@attributes'] ['idref'];
						}
					}
					$defect_detail [$field_key] = $t_asset_list;
				} else {
					if (isset ( $assets ['@attributes'] ['idref'] )) {
						$defect_detail [$field_key] = $assets ['@attributes'] ['idref'];
					}
				}
				break;
			}
		}
	}
	
	private function normalizeMemberArray( $member_list, $ccb_member_groups){
		
		$output = array ();
		foreach ( $member_list as $key => $value ) {
			$t_group = "";
			$t_members = "";
			foreach ( $value as $k => $v ) {
				if (! is_array ( $v ) && in_array ( $v, $ccb_member_groups )) {
					$t_group = $v;
				} else {
					$t_members = $v;
				}
			}
			
			$output [$t_group] = $t_members;
		}
		
		if ($this->debug) {
			log_event ( LOG_AJAX, print_r ( $output, true ) );
		}
		
		return $output;
	}
	
	/* -------------- PUBLIC MEMBERS ----------- */
	
	/**
	 * @return Array of Child projects
	 */
	public function getChildProjects() {
		if (! empty ( $this->error )) {
			return array( "error" => $this->error) ;
		}
		
		$output = array ();
		
		$names = $this->response ['Asset'] ['Attribute'] ['Value'];
		$ids = $this->response ['Asset'] ['Relation'] ['Asset'];
		
		$count = count ( $names );
		if ($count == 1) {
			$pid = $ids ['@attributes'] ['idref'];
			$output [$pid] = $names;
		} else {
			for($i = 0; $i < $count; $i ++) {
				if (isset ( $ids [$i] )) {
					$pid = $ids [$i] ['@attributes'] ['idref'];
					$output [$pid] = $names [$i];
				}
			}
		}
		
		if ($this->debug) {
			log_event ( LOG_AJAX, print_r ( $output, true ) );
		}
		
		return $output;
	}
	
	/**
	 * 
	 * @return Array of Defects with field values
	 */
	public function getDefectDetails( $defect_fields ) {
		if (! empty ( $this->error )) {
			return array (
					"error" => $this->error 
			);
		}
		
		$count = $this->response ['Assets'] ['@attributes'] ['total'];
		$defects = $this->response ['Assets'] ['Asset'];
		
		$defect_list = array ();
		for($i = 0; $i < $count; $i ++) {
			$defect_detail = array ();
			
			if ($count == "1") {
				$defect_attribs = $defects ['Attribute'];
				$defect_relation = $defects ['Relation'];
				$t_id = $defects ['@attributes'] ['id'];
			} else {
				$defect_attribs = $defects [$i] ['Attribute'];
				$defect_relation = $defects [$i] ['Relation'];
				$t_id = $defects [$i] ['@attributes'] ['id'];
			}
			
			$defect_detail ["id"] = $t_id ;
			
			// get Attributes
			foreach ( $defect_attribs as $attrib ) {
				$this->getAttributeValues ( $defect_detail, $defect_fields, $attrib );
			}
				
			// get Relations & Multi Relations
			foreach ( $defect_relation as $relation ) {
				$this->getRelationAttribs ( $defect_detail, $defect_fields, $relation );
			}
						
			$defect_list [ $t_id ] = $defect_detail;
		}
		
		if ($this->debug) {
			log_event ( LOG_AJAX, print_r ( $defect_list, true ) );
		}
		
		return $defect_list;
	}
		
	/**
	 * 
	 * @return Members by their CCB groups
	 */
	public function getMembersList( $member_fields, $ccb_member_groups ){
		if (! empty ( $this->error )) {
			return array (
					"error" => $this->error 
			);
		}
		
		$count = $this->response ['Assets'] ['@attributes'] ['total'];
		$members = $this->response ['Assets'] ['Asset'];
		
		$member_list = array ();
		for($i = 0; $i < $count; $i ++) {
			$member_detail = array ();
			$member_attribs = $members [$i] ['Attribute'];
			
			// get Attributes
			foreach ( $member_attribs as $attributes ) {
				$this->getAttributeValues ( $member_detail, $member_fields, $attributes );
			}
			
			$member_list [] = $member_detail;
		}
		
		if ($this->debug) {
			log_event ( LOG_AJAX, print_r ( $member_list, true ) );
		}
		
		return $this->normalizeMemberArray ( $member_list, $ccb_member_groups );
	}
	
}
