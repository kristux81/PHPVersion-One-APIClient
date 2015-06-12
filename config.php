<?php

/************************
 *  Version One Settings
 ************************/

$g_vone_server_url  = "https://www9.v1host.com" ;

//$g_vone_use_instance = "";
//$g_login_method = BASIC_AUTH ;
//$g_vone_global_user = "" ;
//$g_vone_global_pswd = "" ;

$g_vone_use_instance = "";
$g_login_method = ACCESS_TOKEN ;


/**************************
 * Date & Time Settings
 **************************/

$g_complete_date_format = 'Y-m-d H:i T';


/**************************
 * Logging & Debug Settings
 **************************/
/**
 * UNIX : 'file:/home/user/ccb.log'
 * WIN  : 'file:d:/ccb.log'
 */
$g_log_destination = 'file:d:/ccb.log';
$g_global_log_level = LOG_AJAX ;

// curl request response (true/false)
$g_curl_debug = true;
