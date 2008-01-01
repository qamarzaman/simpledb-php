<?php

/**
 * item.php
 * 
 * (c)2007, Eric J Hayes  - ejh237 at eatsleepcode dot com
 * License: Apache 2.0, Use as you like, but give credit where due
 * 
 * 
 * Ugly browsing for Amazon SimpleDB, uses the simpledb.php light wrapper
 * class to access SimpleDB.
 * 
 * This page displays a list of the Items for a domain, starting at the
 * beginning, and going $kPageSize at a time. Usually called by domain.php
 * or:  http://.../sdbrowser/item.php?action=view&domain=DOMAINNAME&item=ITEMNAME
 * 
 * Will allow you to view, delete, and add properties to an item,
 * 
 * 
 * VERSION	DATE		WHO		COMMENTS
 * 0.1		12/31/07	ejh		Initial version, a bit distracted watching Oregon play
 *
 */

	include_once "simpledb.php";
	include_once "sdbrowser/awsconst.php";

	
	$sdb = new simpledb(awsconst::$kAccessKeyID, awsconst::$kSecretAccessKey);
	$base = "http://" . $_SERVER['SERVER_NAME'] . "/sdbrowser/";
	$attributes = "";

	
	$header = "<b>ejh237 SimpleDB Browser</b><BR><BR>";

	if ( isset( $_REQUEST['action'] ) ) {
		$action = $_REQUEST['action'];
	} else {
		echo "Please supply a 'action' param.";
		exit;
	}

	if ( isset( $_REQUEST['domain'] ) ) {
		$domainName = $_REQUEST['domain'];
	} else {
		echo "Please supply a 'domain' param.";
		exit;
	}

	$domainToken = @$_REQUEST['dcurr'];
	
	if ( isset( $_REQUEST['item'] ) ) {
		$itemName = $_REQUEST['item'];
	} else {
		$itemName = "";
	}

	$status = @$_REQUEST['status'];
	if ( $status == "addeditem" ) {
		$header .= "** Item Created Successfully.<BR>";
		$header .= "** NOTE: Due to AWS lag between PUT and GET, you may need to reload in a moment to see the item as deleted.<BR><BR>";				
	}
	

	$name = @$_REQUEST['name'];
	$value = @$_REQUEST['value'];
	$replace = @$_REQUEST['replace'];

	$out = "";
	
	switch ( $action ) {
		case 'add':
			if ( $name === null || $value === null ) {
				$out .= "** Add Request Ignored because name and value not valid  name='$name' & value='$value'";
			} else {
				$result = $sdb->putAttribute($domainName, $itemName, $name, $value, $replace == null ? false : true);
				if ( $result[simpledb::$kResultCode] == 200 ) {
					header("Location:item.php?domain=$domainName&item=$itemName&action=view&status=addeditem");
				} else {
					header("Location:domain.php?domain=$domainName&item=$itemName&action=view&status=additemfailed&code=" . $result[simpledb::$kResultCode]);
				}
			}
			break;
		
		case 'delete':
			if ( $name === null || $value === null ) {
				$out .= "** Delete Request Ignored because name and value not valid  name='$name' & value='$value'";
			} else {
				$result = $sdb->deleteAttribute($domainName, $itemName, $name, $value, $replace == null ? false : true);
				$out .= "** Delete Request ($name=>$value) result='" . $result[simpledb::$kResultCode] . " " . $result[simpledb::$kResultMessage] . "'. <BR>";
				$out .= "** NOTE: Due to AWS lag between DELETE and GET, you may need to reload in a moment to see the new attribute deleted.<BR><BR>";
			}
			break;
		
	}

	$out .= "NAV: <a href='$base" . "domains.php" . (($domainToken == "") ? "" : "?next=$domainToken") . "'>Domain List</a> / ";
	$out .= "<a href='" . $base . "domain.php?action=view&domain=$domainName'>Domain '$domainName'</a><BR><BR>";
	$out .= "Attributes for item ";

	if ( $itemName == "" ) {
		$out .= "<i>new item</i><BR>";
	} else {
		$out .= "'<b>$itemName</b>'<BR>";
	}

	
	// get this item (if it is not empty)
	if ( $itemName != "" ) {
		$result = $sdb->getAttributes($domainName, $itemName);
		if ( $result[simpledb::$kResultCode] == 200 ) {
			$attributes = $result[simpledb::$kResultAttributes];
		}
		
		if ( $attributes == "" ) {
			echo $header . "Error loading item '$itemName', error code: " . $result[simpledb::$kResultCode] . " " . $result[simpledb::$kResultMessage];
			exit;
		}
	} else {
		// ok, new item
		$out .= "No Attributes Yet";
	}

	
	$out .= "<BR><table><tr>";
	
	if ( $attributes != "" ) {
		$out .= "<td valign=top>";
		
		$out .= "<table><tr>";
		$out .= 	"<td align='left' valign='middle'>Name</td>";
		$out .= 	"<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
		$out .= 	"<td align='left' valign='middle'>Value</td>";
		$out .= 	"<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
		$out .= 	"<td align='center'>Actions</td>";
		$out .= "</tr>";
		
		foreach ( $attributes as $a ) {
			$name = $a[simpledb::$kResultName];
			$value = $a[simpledb::$kResultValue];
			$out .= "<tr>";
			$out .= 	"<td align='left' valign='middle'>$name</td>";
			$out .= 	"<td></td>";
			$out .= 	"<td align='left' valign='middle'>$value</td>";
			$out .= 	"<td></td>";
			$out .= 	"<td align='center' valign='middle'><a href='$base" . "item.php?domain=$domainName&item=$itemName&name=$name&value=$value&action=delete'>delete</a></td>";
			$out .= "</tr>";
		}
		
		$out .= "</table>";
		
		$out .= "</td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
	} else {
		
	}

	
	$out .= "<td valign='top'>";
	
	$out .= 	"<table border=1>";
	$out .=  		"<tr><td align='center'>Add Attribute</td></tr>";
	$out .=  		"<tr><td>";
	$out .=  			"<table><tr><td>";
	$out .= 				"<form name='newitem' action='item.php?domain=$domainName&item=$itemName&action=view' method='POST'>";
	$out .=  				"<tr><td align='right'>Name</td><td><input type='text' size='30' name='name' value=''></tr></td>";
	$out .=  				"<tr><td align='right'>Value</td><td><input type='text' size='30' name='value' value=''></tr></td>";
	$out .=  				"<tr><td></td><td><input type='checkbox' name='replace'>Replace</td></tr>";
	$out .=  				"<tr><td></td><td align=right><input type='submit' name='action' value='add'>";
	$out .=  						"<input type='hidden' name='domain' value='$domainName'> ";
	$out .=  						"<input type='hidden' name='item' value='$itemName'> ";
	$out .=					"</tr></td>";
	$out .=  			"</table>";
	$out .=  		"</td></tr></table>";
	$out .=  "</form>";
	
	$out .= "</td></tr></table>";

	$out .= "</td></tr></table>";
	
	echo $header . $out;
?>