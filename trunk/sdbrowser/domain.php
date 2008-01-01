<?php

/**
 * domain.php
 * 
 * (c)2007, Eric J Hayes  - ejh237 at eatsleepcode dot com
 * License: Apache 2.0, Use as you like, but give credit where due
 * 
 * 
 * Ugly browsing for Amazon SimpleDB, uses the simpledb.php light wrapper
 * class to access SimpleDB.
 * 
 * This page displays the entries for a domain, usually called from domains.php
 * or: http://.../sdbrowser/domain.php?action=view&domain=DOMAINNAME&next=NEXTTOKEN		<-next is optional
 * 
 * Will allow you to view, add, and delete
 * 
 * 
 * VERSION	DATE		WHO		COMMENTS
 * 0.1		12/31/07	ejh		Initial version, a bit distracted watching Oregon play
 *
 */

	include_once "utils_ip.php";
	include_once "simpledb.php";
	include_once "sdbrowser/awsconst.php";

	$kPageSize = 10;
	$base = "http://" . $_SERVER['SERVER_NAME'] . "/sdbrowser/";

	$sdb = new simpledb(awsconst::$kAccessKeyID, awsconst::$kSecretAccessKey);
	
	if ( isset( $_REQUEST['action'] ) ) {
		$action = $_REQUEST['action'];
	} else {
		echo "Please supply an 'action' param.";
		exit;
	}

	if ( isset( $_REQUEST['domain'] ) ) {
		$domainName = $_REQUEST['domain'];
	} else {
		echo "Please supply a 'domain' param.";
		exit;
	}

	$todisplay = "";
	
	switch ( $action ) {
		case 'delete':
			doDelete($sdb, $domainName);
			break;
		
		case 'deleteitem':
			$itemName = @$_REQUEST['item'];
			if ( $itemName == "" ) {
				echo "Delete Item Ignored, no item specified.<BR><BR>";
			} else {
				$request = $sdb->deleteItem($domainName, $itemName);
				if ( $request[simpledb::$kResultCode] == 200 ) {
					header("Location:domain.php?domain=$domainName&action=view&status=itemdeleted&item=$itemName");
				} else {
					echo "Delete Item failed. Result: " . $request[simpledb::$kResultCode] . " " . $request[simpledb::$kResultCode] . "<BR>";
				}
			}
			break;
			
		case 'add':
			$addresult = doAdd($sdb, $domainName);
			$todisplay = doView($sdb, $domainName, $kPageSize, $base, $addresult);
			break;

		case 'view':
			$todisplay = doView($sdb, $domainName, $kPageSize, $base);
			break;
		
		
		default:
			$todisplay = "Unknown action.";
	}
	
	echo $todisplay;
	exit;
	
	
	
	
	
	
	
	function doDelete(simpledb $sdb, $domainName) {
		$result = $sdb->deleteDomain($domainName);
		$next = @$_REQUEST['dcurr'];
		header("Location:domains.php?domain=$domainName&action=view&next=$next&status=domaindeleted&code=" . $result[simpledb::$kResultCode]);
	}
	
	function doAdd(simpledb $sdb, $domainName) {
		$result = $sdb->createDomain($domainName);
		if ( $result[simpledb::$kResultCode] == 200 ) {
			return "Added '$domainName' Created.  Result was: " . $result[simpledb::$kResultCode] . " " . $result[simpledb::$kResultMessage] . "<BR><BR>";
		} else {
			return "Error Adding Domain: " . $result[simpledb::$kResultCode] . " " . $result[simpledb::$kResultMessage] . "<BR><BR>";
		}
	}
	
	function doView(simpledb $sdb, $domainName, $maxItems, $base, $addresult = "") {
		if ( isset( $_REQUEST['next'] ) ) {
			$nextToken = $_REQUEST['next'];
		} else {
			$nextToken = "";
		}

		if ( isset( $_REQUEST['dcurr'] ) ) {
			$domainToken = $_REQUEST['dcurr'];
		} else {
			$domainToken = "";
		}

		$header = "<b>ejh237 SimpleDB Browser</b><BR><BR>";
		$header .= "NAV: <a href='$base" . "domains.php" . (($domainToken == "") ? "" : "?next=$domainToken") . "'>Domain List</a><BR><BR>";
		$header .= $addresult;
		
		$status = @$_REQUEST['status'];
		$itemName = @$_REQUEST['item'];
		if ( $status == "itemdeleted" ) {
			$itemName = @$_REQUEST['item'];
			$header .= "** Item Deleted '$itemName'<BR>";
			$header .= "** NOTE: Due to AWS lag between DELETE and GET, you may need to reload in a moment to see the item as deleted.<BR><BR>";				
		} elseif ( $status == "additemfailed" ) {
			$code = @$_REQUEST['code'];
			$header .= "** Item Add failed for '$itemName'. Result Code: $code<BR>";
		}

		// get a page of this domain's items
		$result = $sdb->query($domainName, "", $nextToken, $maxItems);
		
		$code = @$result[simpledb::$kResultCode];
		if ( $code == 200 ) {
			$newToken = @$result[simpledb::$kResultNextToken];

			$header .= "Domain Records for '$domainName'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			if ( $nextToken != null ) {
				$header .= "<a href='" . $base . "domain.php?action=view&domain=$domainName'>First $maxItems</a>";
			}
			if ( $newToken != null ) {
				if ( $nextToken != null ) $header .= " / ";
				$header .= "<a href='" . $base . "domain.php?action=view&domain=$domainName&next=$newToken'>Next $maxItems</a>";
			}

			$itemNames = $result[simpledb::$kResultItems];
			$fields = array();
			$items = array();
			
			// make the unique set of fields
			foreach ( $itemNames as $itemName ) {
				$result = $sdb->getAttributes($domainName, $itemName);
				$code = @$result[simpledb::$kResultCode];
				if ( $code == 200 ) {
					$attribs = $result[simpledb::$kResultAttributes];
					$items[$itemName] = $attribs;
					
					foreach ( $attribs as $a ) {
						$name = $a[simpledb::$kResultName];
						$fields[$name] = "1";
					}
				}
			}
			
			// okay, lets make the sorted list of fields, and store their order as the value  array(key=>order, ...)
			ksort($fields);
			$keys = array_keys($fields);
			$count = 1;
			foreach ( $keys as $key ) {
				$fields[$key] = $count;
				$count++;
			}
			
			$todisplay = "<table border='0' cellspacing='5'><tr><td align='top' valign='middle'><b>Item Name</b></td>";
			$didone = false;
			foreach ( $fields as $field=>$value ) {
				$didone = true;
				$todisplay .= "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
				$todisplay .= "<td align='center' valign='middle'><b>$field</b></td>";
			}
			if ( $didone == false ) {
				$todisplay .= "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
				$todisplay .= "<td align='center' valign='middle'><b><i>none</i></b></td>";
			}
			$todisplay .= "</tr>";
			
			$didone = false;
			foreach ( $items as $item=>$attribs ) {
				$didone = true;
				
				// make an output array of empty records for each unique key
				$out = array();
				foreach ( $fields as $field=>$value ) {
					$out[$field] = "";
				}

				foreach ( $attribs as $a ) {
					$name = $a[simpledb::$kResultName];
					$value = $a[simpledb::$kResultValue];
					
					$content = @$out[$name];
					
					if ( $content == null ) {
						$content = $value;
					} else {
						$content .= ", $value";
					}
				
					$out[$name] = $content;
				}
			
				$todisplay .= "<tr>";
				$todisplay .= "<td align='left' valign='middle'><a href='" . $base . "item.php?domain=$domainName&dcurr=$domainToken&item=$item&action=view'>$item</a> ";
				$todisplay .= 									 "(<a href='" . $base . "domain.php?domain=$domainName&next=$domainToken&item=$item&action=deleteitem'>delete</a>)</td>";
				foreach ( $out as $key=>$value ) {
					$todisplay .= "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
					$todisplay .= "<td align='center' valign='middle'>$value</td>";
				}
				
				$todisplay .= "</tr>";
			}
			
			if ( $didone == false ) {
				$todisplay .= "<td><i>no attributes</i></td>";
			}
			
			$todisplay .= "</table>";

			
			$form =  "<table border=1>";
			$form .=  	"<tr><td align='center'>Add Item</td></tr>";
			$form .=  	"<tr><td><table>";
			$form .=  		"<form name='newitem' action='item.php?domain=$domainName&action=view' method='POST'>";
			$form .=  		"<tr><td align='right'>Item Name</td><td><input type='text' size='30' name='item' value=''></tr></td>";
			$form .=  		"<tr><td align='right'>Attribute Name</td><td><input type='text' size='30' name='name' value=''></tr></td>";
			$form .=  		"<tr><td align='right'>Attribute Value</td><td><input type='text' size='30' name='value' value=''></tr></td>";
			$form .=  		"<tr><td></td><td align=right><input type='submit' name='action' value='add'>";
			$form .=  				"<input type='hidden' name='domain' value='$domainName'> ";
			$form .= 		"</tr></td>";
			$form .=  		"</form>";
			$form .=  	"</td></tr></table>";
			$form .=  "</table>";
			
			
			$out = "<br><br>";
			$out .= "<table><tr><td valign='top'>";
			$out .= 	$todisplay;
			$out .= "</td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
			$out .= "<td valign='top'>";
			$out .= 	$form;
			$out .= "</td></tr></table>";
		} else {
			$out = "Get Domain failed for '$domainName'. Result Code = $code " . $result[simpledb::$kResultMessage]; 
		}

		return $header . $out;
	}
	

?>