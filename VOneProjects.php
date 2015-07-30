<?php

require_once ('VOneInterface.php');
global $VersionOneInstance;

// reply to ajax call	
// Goals for the given project
if (isset ( $_GET ['q'] ) && $_GET ['q'] == "goals") {
	echo $VersionOneInstance->getAllOpenGoals ( $_GET ['pid'] );
} 

// Project list for the tree
else {
	echo $VersionOneInstance->getAllOpenProjects ();	
}

