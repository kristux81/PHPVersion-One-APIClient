<?php

/**
 * Version One constants Class
 */
abstract class VersionOneConstants {

	const CUSTOMER = "Customer" ;

	static $instances = array (
			/* Your server address goes here */
	);

	static $endpoints = array (
			"xml"       => "rest-1.v1/Data/", // xml only, no json
			"json"      => "query.v1", // requires oauth2
			"activity"  => "api/ActivityStream", // json only, no xml
			
			"defect-ui"  => "defect.mvc/Summary",  // for the browsers
			"project-ui" => "Project.mvc/Summary",  // for the browsers
			"goal-ui"    => "Goal.mvc/Summary",  // for the browsers
	);
	
	static $actions = array (
			"set" => "set",
			"add" => "add",
			"remove" => "remove", 
	);
	
	static $resources = array (
			"scope" => "Scope",
			"defect"=> "Defect",
			"goal"  => "Goals",
			"member" => "Member",
			"member_group" => "MemberLabel"
	);

	static $attributes = array (
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
	
	static $logic_op = array(
			"and" => ";",
			"or" => "|",
	);
	
	// Member field mapping
	static $member_fields = array(
			"group" => "Name",
	/*		"member_id" => "Members",   */
			"member_name" => "Members.Nickname",
	);
	
	// Goal fields mapping CCB => Version One
	static $goal_fields = array(
			"category" => "Category.Name",
	);
	
	static $goal_cat = array (
			"sustaining" => "Sustaining",
	);
	
	// Project fields mapping CCB => Version One
	static $scope_fields = array (
			"name" => "Name",
			"parent" => "Parent", 
	);

	// defect fields mapping CCB => Version One
	static $defect_fields = array(
			"defect_id" => "Number",
			"defect_summary" => "Name",
			"defect_create_date" => "CreateDate",
			"defect_change_date" => "ChangeDate",
			"defect_status_id" => "Status.Order",
			"defect_status" => "Status.Name",
			"defect_source" => "Source.Name",
			"defect_priority" => "Custom_Rating6",
			"defect_assignee" => "Owners.Name",
			"defect_os" => "Custom_OSFamily",
			"defect_platform" => "Custom_Platform2",
			"project_id" => "Scope.ID",
			"project_name" => "Scope.Name",
			"goal" => "Goals",
			"goal_name" => "Goals.Name",
			"project_end_date" => "Scope.EndDate",
			"note" => "Custom_note",
	);

}