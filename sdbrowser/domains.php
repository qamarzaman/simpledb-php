<?php

/**
 * domains.php
 * 
 * (c)2007, Eric J Hayes  - ejh237 at eatsleepcode dot com
 * License: Apache 2.0, Use as you like, but give credit where due
 * 
 * 
 * Ugly browsing for Amazon SimpleDB, uses the simpledb.php light wrapper
 * class to access SimpleDB.
 * 
 * This page displays a list of domains, starting at the beginning, and going $kPageSize at a time
 * http://.../sdbrowser/domains.php?next=NEXTTOKEN		<-next is optionsl
 * 
 * 
 * VERSION	DATE		WHO		COMMENTS
 * 0.1		12/31/07	ejh		Initial version, a bit distracted watching Oregon play
 *
 */

	include_once "simpledb.php";
	include_once "sdbrowser/awsconst.php";

	$kPageSize = 10;
	$base = "http://" . $_SERVER['SERVER_NAME'] . "/sdbrowser/";

	$sdb = new simpledb(awsconst::$kAccessKeyID, awsconst::$kSecretAccessKey);
	
	$header = "<b>ejh237 SimpleDB Browser</b><BR><BR>";

	if ( isset( $_REQUEST['next'] ) ) {
		$nextToken = $_REQUEST['next'];
	} else {
		$nextToken = "";
	}
	
	$status = @$_REQUEST['status'];
	if ( $status == 'domaindeleted' ) {
		$domainName = @$_REQUEST['domain'];
		$code = @$_REQUEST['code'];
		
		$header .= "** Domain Deleted '$domainName'. Result Code: $code<BR><BR>";
	}

	// get this pages domains
	$result = $sdb->listDomains($nextToken, $kPageSize);
	
	$code = @$result[simpledb::$kResultCode];
	if ( $code == 200 ) {
		$newToken = @$result[simpledb::$kResultNextToken];
		$list = @$result[simpledb::$kResultDomains];
		$domains = "<table><tr><td>Name</td><td></td><td>Actions</td></tr>";

		foreach ( $list as $domainName ) {
			$domains .= "<tr>";
			$domains .= "<td><a href='" . $base . "domain.php?action=view&domain=$domainName&dcurr=$nextToken'>$domainName</a></td>";
			$domains .= "<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
			$domains .= "<td><a href='" . $base . "domain.php?action=delete&domain=$domainName&dcurr=$nextToken'>delete</a></td>";
			$domains .= "</tr>";
		}
		$domains .= "</table>";
	} else {
		$domains = "List Domains failed: ResultCode = $code";
	}
	

	
	echo $header;

	if ( $nextToken != null ) {
		echo "<a href='" . $base . "domains.php'>First $kPageSize</a>";
	}
	if ( $newToken != null ) {
		if ( $nextToken != null ) echo " / ";
		echo "<a href='" . $base . "domains.php?next=$newToken'>Next $kPageSize</a>";
	}	
	
	
	echo "<BR><BR>";
	echo "<table><tr><td>";

	echo 	"Domains List<BR><BR>";
	echo 	$domains;
	
	echo 	"</td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>";
	
	echo 	"<BR><BR><table border=1 valign='top'>";
	echo 		"<tr><td align=center>Add Domain</td></tr>";
	echo 		"<tr><td>";
	echo 			"<table>";
	echo 				"<form name='newdomain' action='domain.php' method='POST'>";
	echo 				"<tr><td>Name</td><td><input type='text' size='30' name='domain' value=''></td>";
	echo 				"<td><input type='submit' name='action' value='add'></td></tr>";
	echo 				"</form>";
	echo 			"</table>";
	echo 		"</td></tr>";
	echo 	"</table>";

	echo "</td></tr></table>";
	
?>