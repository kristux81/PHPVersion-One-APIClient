<?php

require_once ('VOneResponseParser.php');
require_once ('VOneJsonResponseParser.php');
require_once ('curl_api.php');
require_once ('VOneConstants.php');


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
	
	private function getVOneUrl( $endPoint, $assetId ){
		if(empty($assetId)){
			return "";
		}
		
		return $this->server_url . $this->vone_instance . $endPoint . "?oidToken=" . urlencode ( $assetId );
	}
	
	private function getBaseUrl() {
		return $this->server_url . $this->vone_instance . $this->end_point;
	}
	
	private function getDefectActivityStream( $defectId ) {
		return $this->server_url . $this->vone_instance . VersionOneConstants::$endpoints ['activity'] . "/Defect:" . $defectId;
	}
	
	private function getMemberGroupUrl() {
		return $this->getBaseUrl () . VersionOneConstants::$resources ['member_group'];
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
		
		$logic_op = VersionOneConstants::$logic_op ['and'];
		$query = "where=";
		foreach ( $attrib_array as $attrib_op => $keypair ) {
			foreach ( $keypair as $attrib => $value ) {
				$query .= $attrib . $attrib_op . $value . $logic_op;
			}
		}
		
		return rtrim ( $query, $logic_op );
	}
	
	private function getQueryWhere2( $pairs ) {
		
		$logic_op = VersionOneConstants::$logic_op ['and'];
		$query = "[";
		foreach ( $pairs as $key => $val ) {
			$query .= $key . "=" . urlencode ( "'" . $val . "'" ) . $logic_op;
		}
	
		return rtrim ( $query, $logic_op ) . "]";
	}
	
	private function getQueryLike( $like_array ) {
		$query = "";
		foreach ( $like_array as $findIn => $find ) {
			$query .= "&find=" . urlencode ( $find ) 
			       . "&findin=" . VersionOneConstants::$defect_fields [$findIn];
		}
		
		return $query;
	}
	
	private function getCCBMemberGroupsList( $ccb_member_groups ){
		$attrib_lbl = VersionOneConstants::$member_fields ['group'];
		$logic_op = VersionOneConstants::$logic_op ['or'];
		
		$filters = "";
		foreach ( $ccb_member_groups as $ccb_group ) {
			$filters .= $attrib_lbl . "=" . urlencode ( "'" . $ccb_group . "'" ) . $logic_op;
		}
		
		return rtrim ( $filters, $logic_op );
	}
	
	
	/* -------------- PUBLIC MEMBERS ----------- */

	/**
	 * get version one style timestamp ( with comparision operator ) filter for date values 
	 * 
	 * @param $op : comparision operator
	 * @param $field : name of field
	 * @param $date : date value ( dd-mm-yy )
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
	 * Url : https://www9.v1host.com/AxwaySandbox/defect.mvc/Summary?oidToken=Defect%3A{defect id}
	 */
	public function getVOneDefectUrl( $defectId ){
		return $this->getVOneUrl( VersionOneConstants::$endpoints ['defect-ui'], $defectId);
	}
	
	/**
	 * Get Project Url to display a defect in Browser
	 * 
	 * Url : https://www9.v1host.com/AxwaySandbox/Project.mvc/Summary?oidToken=Scope%3A{scope_id}
	 */
	public function getVOneProjectUrl( $scopeId ){
		return $this->getVOneUrl( VersionOneConstants::$endpoints ['project-ui'], $scopeId);
	}
	
	/**
	 * Get Goal Url to display a defect in Browser
	 *
	 * Url : https://www9.v1host.com/AxwaySandbox/Goal.mvc/Summary?oidToken=Goal%3A{goal_id}
	 */
	public function getVOneGoalUrl( $goalId ){
		return $this->getVOneUrl( VersionOneConstants::$endpoints ['goal-ui'], $goalId);
	}
	
	/**
	 * search given member in member groups
	 * 
	 * Url : https://www9.v1host.com/AxwaySandbox/rest-1.v1/Data/MemberLabel?
	 *       sel=Name,Members.Nickname,Members&where=Name=%27CCB%20participants%27|Name=%27CCB%20admins%27
	 */
	function getMembersByGroups( $ccb_member_groups ){
		
		$select = array_values ( VersionOneConstants::$member_fields );	
		$request = $this->getMemberGroupUrl () 
		          . $this->getQuerySelect ( $select )
		          . "where=" . $this->getCCBMemberGroupsList ( $ccb_member_groups );
		
		$curl_handle = new curlSession ( $request );
		$response = $curl_handle->get ();
		
		$parser = new VersionOneResponseParser ( $response );		
		return $parser->getMembersList ( VersionOneConstants::$member_fields, $ccb_member_groups );
	}
	
	/**
	 * get all open projects
	 *
	 * Query : https://www9.v1host.com/AxwaySandbox/rest-1.v1/Data/Scope?sel=Name,Parent&where=AssetState=%2764%27
	 */
	function getAllOpenProjects() {
		
		$select = array_values(VersionOneConstants::$scope_fields);
		$request = $this->getScopeUrl () . $this->getQuerySelect ( $select ) .
		           $this->getQueryWhere ( array("=" => self::$active_state) );
		$curl_handle = new curlSession( $request );
		
		// debug turned off to avoid flooding of log files
		$curl_handle->setDebug(false);
		
		return $curl_handle->get() ;
	}
	
	/**
	 * get all open goals against the project
	 *
	 * Query : https://www9.v1host.com/AxwaySandbox/rest-1.v1/Data/Scope/{scopeId}
	 *         ?sel=Goals[Category.Name=%27Sustaining%27;AssetState=%2764%27]
	 */
	function getAllOpenGoals( $scope ) {
		$where = array (
				VersionOneConstants::$attributes ['state'] => VersionOneConstants::$state_values ['open'],
				VersionOneConstants::$goal_fields ['category'] => VersionOneConstants::$goal_cat ['sustaining'] 
		);
		
		$scopeId = substr ( $scope, strlen ( 'Scope:' ) );
		$request = $this->getScopeUrl ( $scopeId ) . "?sel=Goals" . $this->getQueryWhere2 ( $where );
		$curl_handle = new curlSession ( $request );
		
		return $curl_handle->get ();
	}
		
	/**
	 * get all open child projects for a given parent project
	 *
	 * Query : https://www9.v1host.com/AxwaySandbox/rest-1.v1/Data/Scope/{scopeId}
	 *         ?sel=ChildrenMeAndDown[AssetState=%2764%27]
	 */
	function getAllOpenChildProjects( $projectId ) {
		$active_state = array (
				VersionOneConstants::$attributes ['state'] => VersionOneConstants::$state_values ['open'] 
		);

		$request = $this->getScopeUrl ( $projectId ) 
		         . "?sel=ChildrenMeAndDown" . $this->getQueryWhere2 ( $active_state );
		
		$curl_handle = new curlSession ( $request );
		$response = $curl_handle->get ();
		
		$parser = new VersionOneResponseParser ( $response );
		
		// debug turned off to avoid flooding of log files
		$parser->setDebug(false);
		
		return $parser->getChildProjects ();
	}
	
	
	/**
	 * Get all defect data (selected fields) per project
	 * 
	 * Queries : 
	 * 
	 * (1) Single project (scope.Name)
	 *     https://www9.v1host.com/AxwaySandbox/rest-1.v1/Data/Defect?sel=Number,Name,CreateDate,ChangeDate,
	 *     Status.Name,Source.Name,Custom_Rating6,Owners.Name,Custom_OS,Custom_Platform,Scope.Name,Scope.EndDate,
	 *     Custom_PeoplesoftId,Custom_CaseId,Custom_AlertLevel.Name,Custom_CustomerName2
	 *     &where=Scope.Name=%27{Project_name}%27;Source.Name=%27Customer%27;AssetState=%2764%27
	 *     
	 * (2) Multi projects
	 *     https://www9.v1host.com/AxwaySandbox/rest-1.v1/Data/Defect?sel=Number,Name,CreateDate,ChangeDate,
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
		
		$t_defect_fields = VersionOneConstants::$defect_fields;
		
		// attributes for "?sel="
		$select = array_values ( $t_defect_fields );
		
		// default minimum filters
		$where = array (
				$t_defect_fields ['defect_source'] => $this->getQueryEncodedList ( VersionOneConstants::CUSTOMER ) 
		);
		
		// set project filter only if non empty project list
		if (! empty ( $p_project_id )) {
			$where += array (
					$t_defect_fields ['project_id'] => $this->getQueryEncodedList ( $p_project_id ) 
			);
		}
		
		if ($show_closed) {
			$where += self::$all_state;
		} else {
			$where += self::$active_state;
		}
		
		// filters till this point must be applied with comparision operator "="
		$where = array (
				"=" => $where 
		);
		
		// user selected additional filters
		foreach ( $where_filter as $filter_op => $filters ) {
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
		         . $this->getQueryLike ( $where_filter ['like'] ) 
		         . $this->getQuerySort ( $sort, $sort_oder );
		
		$curl_handle = new curlSession ( $request );
		$response = $curl_handle->get ();
		
		$parser = new VersionOneResponseParser ( $response );
		return $parser->getDefectDetails ( $t_defect_fields );
	}
		
	/**
	 * Get all CCB Notes per defect from Activity Stream End Point
	 * 
	 * Query : https://www9.v1host.com/AxwaySandbox/api/ActivityStream/Defect:{defect id}
	 * OR      https://www9.v1host.com/AxwaySandbox/api/ActivityStream/Defect:{defect id}?anchorDate={time-stamp}
	 * 
	 * Example time-stamp = 2015-05-18T08%3A11%3A48.45Z 
	 * 
	 */ 
	function getAllNotes( $defectId ){
	
		$request = $this->getDefectActivityStream( $defectId );
	
		$curl_handle = new curlSession ( $request );
		$response = $curl_handle->get();
		
		$parser = new VersionOneJsonResponseParser( $response );
		
		// debug turned off to avoid flooding of log files
		$parser->setDebug(false);
		
		return $parser->getFieldHistory( VersionOneConstants::$defect_fields['note'] );
	}
	
}

$VersionOneInstance = new VersionOne ();
