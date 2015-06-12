<?php

require_once ('VOneResponseParser.php');
require_once ('VOneJsonResponseParser.php');

/**
 * Version One constants Class
 */
abstract class VersionOneConstants {
	
	const CUSTOMER = "Customer" ;
	
	static $instances = array (	
	      /* Your server address goes here */
	);
	
	static $endpoints = array (
			"xml"      => "rest-1.v1/Data/", // xml only no json
			"json"     => "query.v1", // requires oauth2
			"ui"       => "defect.mvc/Summary",
			"activity" => "api/ActivityStream",
	);
	
	static $resources = array (
			"scope" => "Scope",
			"defect"=> "Defect",
	);
	
	static $attributes = array (
			"name"          => "Name",
			"parent"        => "Parent",
			"state"         => "AssetState",
	);
	
	static $state_values = array (
			"open" => "64",
			"closed" => "128"
	);
	
	static $sort_order = array(
			"A" => "",
			"D" => "-"
	);
	
	static $defect_fields = array(
			"defect_id" => "Number",
			"defect_summary" => "Name",
			"defect_create_date" => "CreateDate",
			"defect_change_date" => "ChangeDate",
			"defect_status" => "Status.Name",
			"defect_source" => "Source.Name",
			"defect_assignee" => "Owners.Name",
			"project_id" => "Scope.ID",
			"project_name" => "Scope.Name",
			"project_end_date" => "Scope.EndDate",
	);
	
}



/** 
 * Version One Rest Query Request Class 
 */
class VersionOne {
	
	static $active_state ;
	static $all_state ;
	
	private $server_url ;
	private $vone_instance ;
	private $end_point ;
	
	/** 
	 * constructor
	 */
	function __construct(){
		global $g_vone_server_url,
		       $g_vone_use_instance ;
		
		$this->server_url = $g_vone_server_url ;
		$this->vone_instance = VersionOneConstants::$instances [ $g_vone_use_instance ] ;
		$this->end_point = VersionOneConstants::$endpoints ['xml'] ;
		
		self::$active_state = array(
				VersionOneConstants::$attributes ['state'] => 
				          $this->getQueryEncodedList ( VersionOneConstants::$state_values ['open'] )
		);
		self::$all_state = array(
				VersionOneConstants::$attributes ['state'] =>
				$this->getQueryEncodedList ( array( VersionOneConstants::$state_values ['open'],
						                            VersionOneConstants::$state_values ['closed'] ))
		);
	}
	
	
	/* ---- PRIVATE MEMBERS ----- */
	
	private function getBaseUrl() {
		return $this->server_url . $this->vone_instance . $this->end_point;
	}
	
	private function getDefectActivityStream( $defectId ) {
		return $this->server_url . $this->vone_instance . VersionOneConstants::$endpoints ['activity'] . "/Defect:" . $defectId;
	}
	
	private function getScopeUrl( $scopeId = null) {
		$url = $this->getBaseUrl () . VersionOneConstants::$resources ['scope'];
		if (! empty ( $scopeId )) {
			$url .= "/" . $scopeId;
		}
		
		return $url;
	}
	
	private function getDefectUrl( $defectId = "" ) {
		$base_url = $this->getBaseUrl () . VersionOneConstants::$resources ['defect'];
		
		$t_defect = "";
		if (! empty ( $defectId )) {
			$t_defect = "/" . $defectId;
		}
		return $base_url . $t_defect;
	}
	
	private function getQuerySelect( $attrib ) {
		return "?sel=" . implode ( ",", $attrib ) . "&";
	}
	
	private function getQueryComment( $comment ) {
		if (empty ( $comment )) {
			return "";
		}
		return "?comment=" . urlencode ( $comment );
	}
	
	private function getQuerySort( $attrib, $order ) {
			
		// default sort order = not required = ascending
		$t_order = "";
		if (! empty ( $order )) {
			$t_order = VersionOneConstants::$sort_order [$order];
		}
		
		if (! empty ( $attrib ) && isset ( VersionOneConstants::$defect_fields [$attrib] )) {
			return "&sort=" . $t_order . VersionOneConstants::$defect_fields [$attrib];
		}
		
		return "";
	}

	private function getQueryEncodedList( $p_field ){
		$t_field = "";
		if (is_array ( $p_field )) {
			
			$Str = "";
			foreach ( $p_field as $field ) {
				$Str .= urlencode ( "'" . $field . "'" ) . ",";
			}
			
			$t_field = rtrim ( $Str, "," );
		} else {
			$t_field = urlencode ( "'" . $p_field . "'" );
		}
		
		return $t_field;
	}
	
	private function getQueryWhere( $attrib_array ) {
		$query = "where=";
		foreach ( $attrib_array as $attrib_op => $keypair ) {
			foreach ( $keypair as $attrib => $value ) {
				$query .= $attrib . $attrib_op . $value . ";";
			}
		}
		
		return rtrim ( $query, ";" );
	}
	
	private function getQueryLike( $like_array ) {
		$query = "";
		foreach ( $like_array as $findIn => $find ) {
			$query .= "&find=" . urlencode ( $find ) . "&findin=" . VersionOneConstants::$defect_fields [$findIn];
		}
		
		return $query;
	}
	
	private function getQueryWhereActive() {
		$t_lbl_state = VersionOneConstants::$attributes ['state'];
		$t_val_state = VersionOneConstants::$state_values ['open'];
		
		return "[" . $t_lbl_state . "=" . urlencode ( "'" . $t_val_state . "'" ) . "]";
	}
	
	private function getQueryChildrenProjects( ) {
		return "?sel=ChildrenMeAndDown";
	}
	
	private function getPayload( $ccb_date, $note_content, $new_project_id){
		$payload = "<Asset>";
		if (! empty ( $ccb_date )) {
			$t_date_field = VersionOneConstants::$defect_fields ['ccb_date'];
			$payload .= "<Attribute name=\"$t_date_field\" act=\"set\">$ccb_date</Attribute>";
		}
		
		if (! empty ( $note_content )) {
			$t_note_field = VersionOneConstants::$defect_fields ['ccb_note'];
			$payload .= "<Attribute name=\"$t_note_field\" act=\"set\">$note_content</Attribute>";
		}
		
		if (! empty ( $new_project_id )) {
			$payload .= "<Relation name=\"Scope\" act=\"set\"><Asset idref=\"$new_project_id\" /></Relation>";
		}
		
		$payload .= "</Asset>";
		
		return $payload;
	}
	
	
	/* -------------- PUBLIC MEMBERS ----------- */

	/**
	 * get version one style timestamp ( with comparision operator ) filter for date values 
	 * 
	 * @param $op : comparision operator
	 * @param $field : name of field
	 * @param $date : date value ( yy-mm-dd )
	 * @return filter array (for building query ) with operator 
	 */
	public static function date2TimeStampFilter($op, $field, $date){
		if (empty ( $date )) {
			return array ();
		}
	
		$a_begin_time = array (
				$field => $date . "T00:00:00"   // start of current day
		);
	
		$a_end_time = array (
				$field => $date . "T23:59:59"   // end of current day
		);
	
		switch ($op) {
			case "=" : // date >= {DATE00:00:00} && date <= {DATE23:59:59}
				return array (
				">=" => $a_begin_time,
				"<=" => $a_end_time
				);
					
			case ">" : // date > {DATE23:59:59}
			case "<=" : // date <= {DATE23:59:59}
				return array (
				$op => $a_end_time
				);
					
			case "<" : // date < {DATE00:00:00}
			case ">=" : // date >= {DATE00:00:00}
				return array (
				$op => $a_begin_time
				);
		}
	}
	
	/**
	 * Get Defect Url to display a defect in Browser
	 * 
	 * Query : {server instance}/defect.mvc/Summary?oidToken=Defect%3A{defect id}
	 */
	public static function getVOneDefectUrl( $defectId ){
		global $g_vone_server_url, $g_vone_use_instance ;
	
		$base_uri = $g_vone_server_url .
					VersionOneConstants::$instances [ $g_vone_use_instance ] .
					VersionOneConstants::$endpoints ['ui'];
		$query = "?oidToken=Defect%3A";
	
		return $base_uri . $query . $defectId ;
	}
	
	/**
	 * get all open projects
	 *
	 * Query : {server instance}/rest-1.v1/Data/Scope?sel=Name,Parent&where=AssetState=%2764%27
	 */
	function getAllOpenProjects() {
		
		$select = array (
				VersionOneConstants::$attributes ['name'],
				VersionOneConstants::$attributes ['parent'] 
		);
		
		$request = $this->getScopeUrl () . $this->getQuerySelect ( $select ) .
		           $this->getQueryWhere ( self::$active_state );
		$curl_handle = new curlSession( $request );
		
		return $curl_handle->get() ;
	}
		
	/**
	 * get all open child projects for a given parent project
	 *
	 * Query : {server instance}/rest-1.v1/Data/Scope/{scopeId}?sel=ChildrenMeAndDown[AssetState=%2764%27]
	 */
	function getAllOpenChildProjects( $projectId ) {
		
		$request = $this->getScopeUrl ( $projectId ) 
		     . $this->getQueryChildrenProjects()
		     . $this->getQueryWhereActive();
		
		$curl_handle = new curlSession( $request );
		$response = $curl_handle->get() ;
		
        $parser = new VersionOneResponseParser( $response );
        		
		return $parser->getChildProjects();
	}
	
	
	/**
	 * Get all defect data (selected fields) per project
	 * 
	 * Queries : 
	 * 
	 * (1) Single project (scope.Name)
	 *     {server instance}/rest-1.v1/Data/Defect?sel=Number,Name,CreateDate,ChangeDate,
	 *     Status.Name,Source.Name,Custom_Rating6,Owners.Name,Custom_OS,Custom_Platform,Scope.Name,Scope.EndDate,
	 *     Custom_PeoplesoftId,Custom_CaseId,Custom_AlertLevel.Name,Custom_CustomerName2
	 *     &where=Scope.Name=%27{Project_name}%27;Source.Name=%27Customer%27;AssetState=%2764%27
	 *     
	 * (2) Multi projects
	 *     {server instance}/rest-1.v1/Data/Defect?sel=Number,Name,CreateDate,ChangeDate,
	 *     Status.Name,Source.Name,Custom_Rating6,Owners.Name,Custom_OS,Custom_Platform,Scope.Name,Scope.EndDate,
	 *     Custom_PeoplesoftId,Custom_CaseId,Custom_AlertLevel.Name,Custom_CustomerName2
	 *     &where=Scope.Name=%27{Project_name_1}%27,%27{Project_name_2}%27,%27{Project_name_3}%27
	 *     ;Source.Name=%27Customer%27;AssetState=%2764%27
	 *     
	 */
	function getCustomerDefectsByProject( $selected_filters ) {
			
		// user filters
		$p_project_id = $selected_filters ['projects'];
		$where_filter = $selected_filters ['where'];
		$show_closed = $selected_filters ['closed'];
		$sort = $selected_filters ['sort'] ['by'];
		$sort_oder = $selected_filters ['sort'] ['order'];
		
		$t_defect_fields = VersionOneConstants::$defect_fields ;
		
		// attributes for "?sel="
		$select = array_values ( $t_defect_fields ) ;
			
		// default minimum filters
		$where = array (
				$t_defect_fields ['project_id'] => $this->getQueryEncodedList( $p_project_id ),
				$t_defect_fields ['defect_source'] => $this->getQueryEncodedList( VersionOneConstants::CUSTOMER),
		);
		
		if($show_closed){
			$where += self::$all_state ;
		}else {
			$where += self::$active_state ;
		}
		
		// filters till this point must be applied with comparision operator "="
		$where = array("=" => $where);
		
		// user selected additional filters
		foreach ($where_filter as $filter_op => $filters ) {
			if (is_array ( $filters ) && count ( $filters ) > 0) {
					
				// skip like filter at this stage
				if ($filter_op == "like")
					continue;
				
				foreach ( $filters as $filter_key => $filter_value ) {
					
					// $where_filter keys must be replaced with $defect_fields values
					$t_defect_field = $t_defect_fields [$filter_key];
					
					// set user selected filters
					if (isset ( $t_defect_field )) {
						$where [$filter_op] [$t_defect_field] = $this->getQueryEncodedList ( $filter_value );
					}
				}
			}
		}

		$request = $this->getDefectUrl () 
		           . $this->getQuerySelect ( $select ) 
		           . $this->getQueryWhere ( $where )
		           . $this->getQueryLike( $where_filter['like'] )
		           . $this->getQuerySort( $sort, $sort_oder ) ;
		
		$curl_handle = new curlSession( $request );
		$response = $curl_handle->get() ;
		
		$parser = new VersionOneResponseParser ( $response );
		
		// debug mode
		$parser->setDebug();
		
		return $parser->getDefectDetails ( $t_defect_fields );
	}
		
	/**
	 * Get all CCB Notes per defect from Activity Stream End Point
	 * 
	 * Query : {server instance}/api/ActivityStream/Defect:{defect id}
	 * OR      {server instance}/api/ActivityStream/Defect:{defect id}?anchorDate={time-stamp}
	 * 
	 * Example time-stamp = 2015-05-18T08%3A11%3A48.45Z 
	 * 
	 */ 
	function getHistory( $defectId ){
	
		$request = $this->getDefectActivityStream( $defectId );
	
		$curl_handle = new curlSession ( $request );
		$response = $curl_handle->get();
		
		$parser = new VersionOneJsonResponseParser( $response, true);
		return $parser->getFieldHistory( VersionOneConstants::$defect_fields['defect_status'] );
	}
	
}

$VersionOneInstance = new VersionOne ();
