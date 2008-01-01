<?php

/**
 * simple.php
 * 
 * (c)2007, Eric J Hayes  - ejh237 at eatsleepcode dot com
 * License: Apache 2.0, Use as you like, but give credit where due
 * 
 * 
 * "simple" PHP wrapper class for Amazon's SimpleDB. Sorry, the AWS available MVC PHP is just
 * too complicated, and frankly doesn't do enough (IMO, for me, no dig intended! sorry)
 * 
 * I'll write some usage samples soon, but for now, you can look at the ugly "sdbrowser" as
 * an example
 * 
 * 
 * VERSION	DATE		WHO		COMMENTS
 * 0.1		12/31/07	ejh		Initial version, a bit distracted watching Oregon play
 *
 */


class simpledb {
	
	// use these to extract the result values
	static public $kResultCode						= "Result";			// returned for all calls
	static public $kResultMessage					= "Message";		// returned for all calls
	static public $kResultUsage						= "BoxUsage";		// returned for all SUCCESSFUL calls
	
	static public $kResultNextToken					= "NextToken";		// for ListDomains and Query (OPTIONAL)
	static public $kResultDomains					= "DomainNames";	// for ListDomains 
	static public $kResultAttributes				= "Attributes";		// for GetAttributes = array(array(kResultName=>, kResultValue=>), ...)
	static public 	$kResultName					= "Name";			// for GetAttributes = Name=>
	static public 	$kResultValue					= "Value";			// for GetAttributes = Value=>
	static public $kResultDomainName				= "DomainName";		// for GetAttributes and Query
	static public $kResultItemName					= "ItemName";		// for GetAttributes
	static public $kResultItems						= "Items";			// for Query

	
	// valid tests for query
	static public $kQueryStartsWith = "starts-with";
	static public $kQueryEquals = "=";
	static public $kQueryNotEqual = "!=";
	static public $kQueryLessThan = "<";
	static public $kQueryGreaterThan = ">";
	static public $kQueryLessThanOrEquals = "<=";
	static public $kQueryGreaterThanOrEquals = ">=";
	
	
	
	
	
	// member variables supplied to constructor
	private $mAccessKeyID;
	private $mSecretAccessKey;
	private $mDefaultDigitPad;
	private $mBuildAttribute;
	
	// AWS SimpleDB API Constants
	static private $kServiceEndpoint				= "sdb.amazonaws.com";
	static private $kUserAgent						= "AWS SimpleDB PHP Wrapper - ejh237";
	
	static private $kSDBAction_CreateDomain			= "CreateDomain";
	static private $kSDBAction_DeleteDomain			= "DeleteDomain";
	static private $kSDBAction_ListDomains			= "ListDomains";
	static private $kSDBAction_PutAttributes		= "PutAttributes";
	static private $kSDBAction_DeleteAttributes		= "DeleteAttributes";
	static private $kSDBAction_GetAttributes		= "GetAttributes";
	static private $kSDBAction_Query				= "Query";
	
	static private $kSDBResult_BoxUsage				= "BoxUsage";
	static private $kSDBResult_NextToken			= "NextToken";
	static private $kSDBResult_Attribute			= "Attribute";
	static private $kSDBResult_Name					= "Name";
	static private $kSDBResult_Value				= "Value";
	static private $kSDBResult_ItemName				= "ItemName";
	
	static private $kSDBValue_APIVersion			= "2007-11-07";
	static private $kSDBValue_TimestampFormat		= "Y-m-d\TH:i:s.\\0\\0\\0\\Z";
	static private $kSDBValue_oldSignatureVersion	= 0;
	static private $kSDBValue_newSignatureVersion	= 1;
	
	static private $kSDBParam_Action				= "Action";
	static private $kSDBParam_AWSAccessKeyId		= "AWSAccessKeyId";
	static private $kSDBParam_Timestamp				= "Timestamp";
	static private $kSDBParam_Version				= "Version";
	static private $kSDBParam_SignatureVersion		= "SignatureVersion";
	static private $kSDBParam_Signature				= "Signature";
	
	static private $kSDBParam_DomainName			= "DomainName";
	static private $kSDBParam_MaxNumberOfDomains	= "MaxNumberOfDomains";
	static private $kSDBParam_NextToken				= "NextToken";
	static private $kSDBParam_ItemName				= "ItemName";
	static private $kSDBParam_Attribute				= "Attribute.";
	static private $kSDBParam_Name					= 				".Name";
	static private $kSDBParam_Value					= 				".Value";
	static private $kSDBParam_Replace				= 				".Replace";
	static private $kSDBParam_Expression			= "QueryExpression";
	static private $kSDBParam_MaxNumberOfItems		= "MaxNumberOfItems";
	
	
	/**
	* Constructor
	*
	* @param string $accessKeyID			// your AWS "Access Key ID"
	* @param string $secretAccessKey		// your AWS "Seceret Access Key"
	* @param integer $defaultPad			// zero for no padding, positive value for padding"
	*/
	function __construct($accessKeyID, $secretAccessKey, $defaultPad = 8) {
		$this->mAccessKeyID = $accessKeyID;
		$this->mSecretAccessKey = $secretAccessKey;
		$this->mBuildAttribute = array();
		$this->mDefaultDigitPad = $defaultPad;
	}
	
	
	/**
	* AWS SimpleDB API - CreateDomain
	* NOTE: This call will take a while (AWS says 10 seconds)
	*
	* @param string $domainName			// the domain to create
	* 
	* @return array						// array(simpledb::$kResultCode=>(int)http_response_code, simpledb::$kResultBody=>http_response_content)
	*/
	function createDomain($domainName) {
		$params = array(self::$kSDBParam_DomainName=>$domainName);
		
		return $this->_simpleDBRequest(self::$kSDBAction_CreateDomain, $params);
	}
	
	
	/**
	* AWS SimpleDB API - DeleteDomain
	* NOTE: This call will take a while (AWS says 10 seconds)
	*
	* @param string $domainName			// the domain to delete
	* 
	* @return array						// array(simpledb::$kResultCode=>(int)http_response_code, simpledb::$kResultBody=>http_response_content)
	*/
	function deleteDomain($domainName) {
		$params = array(self::$kSDBParam_DomainName=>$domainName);
		
		return $this->_simpleDBRequest(self::$kSDBAction_DeleteDomain, $params);
	}
	
	
	/**
	* AWS SimpleDB API - ListDomains
	*
	* @param string $nextToken = ""		// Optional - Used if you are calling this in a paged manor
	* @param string $maxDomains = 100	// Optional - Used if you are calling this in a paged manor (MAX = 100)
	* 
	* @return array						// array(kResultCode=>, kResultBody=>, kResultBoxUsage=>,
	* 											 kResultDomainNames=>array(DomainName, ...), [kResultNextToken ])
	*/
	function listDomains($nextToken = "", $maxDomains = 100) {
		$params = array(self::$kSDBParam_MaxNumberOfDomains=>$maxDomains);
		if ( $nextToken != "" ) $params[self::$kSDBParam_NextToken] = $nextToken;
		
		$result = $this->_simpleDBRequest(self::$kSDBAction_ListDomains, $params);
		
		return $result;
	}
	


	/**
	* AWS SimpleDB API - PutAttribute - SIMPLE version, just for setting a single attrib
	*
	* @param string		$domainName			// The domain the item is in
	* @param string		$itemName			// The name of the item
	* @param string 	$attribName			// The name of the attrib
	* @param string 	$attribValue		// The value of the attrib
	* @param boolean	$replace = false	// true if the attributes should replace the existing (IGNORED FOR DELETE CALL)
	* @param boolean	$pad = -1			// 0 for no padding, -1 for default, >0 for pad value
	* 
	* @return array							// array(kResultCode=>, kResultBody=>, kResultBoxUsage=>)
	*/
	function putAttribute($domainName, $itemName, $attribName, $attribValue, $replace = false, $pad = -1) {
		$this->attributeINIT();
		$this->attributeADD($attribName, $attribValue, $replace, $pad);
		return $this->putAttributes($domainName, $itemName);
	}
	
	
	/**
	* AWS SimpleDB API - PutAttributes 
	*
	* @param string		$domainName			// The domain the item is in
	* @param string		$itemName			// The name of the item
	* 
	* @return array							// array(kResultCode=>, kResultBody=>, kResultBoxUsage=>)
	*/
	function putAttributes($domainName, $itemName) {
		return $this->_putOrDeleteAttributes( self::$kSDBAction_PutAttributes, $domainName, $itemName);
	}
	

	/**
	* AWS SimpleDB API - DeleteAttributes - SIMPLE version, just for deleting a single attrib
	*
	* @param string		$domainName			// The domain the item is in
	* @param string		$itemName			// The name of the item
	* @param string 	$attribName			// The name of the attrib
	* @param string 	$attribValue		// The value of the attrib
	* @param boolean	$pad				// 0 for no padding, -1 for default, >0 for pad value
	* 
	* @return array							// array(kResultCode=>, kResultBody=>, kResultBoxUsage=>)
	*/
	function deleteAttribute($domainName, $itemName, $attribName, $attribValue, $pad = -1) {
		$this->attributeINIT();
		$this->attributeADD($attribName, $attribValue, false, $pad);
		return $this->deleteAttributes($domainName, $itemName);
	}


	/**
	* AWS SimpleDB API - DeleteAttributes - you must use the attributeADD() to add attributes
	* NOTE: This call will take a while (AWS says 10 seconds)
	*
	* @param string		$domainName			// The domain the item is in
	* @param string		$itemName			// The name of the item
	* 
	* @return array							// array(kResultCode=>, kResultBody=>, kResultBoxUsage=>)
	*/
	function deleteAttributes($domainName, $itemName) {
		return $this->_putOrDeleteAttributes(self::$kSDBAction_DeleteAttributes, $domainName, $itemName);
	}
	
	
	/**
	* AWS SimpleDB API - GetAttributes - Return all of the attributes for an item
	*
	* @param string $domainName				// the domain name
	* @param string $itemName				// the item's name
	* 
	* @return array(kResultCode, kResultMessage=>, kResultBoxUsage=>, kResultAttributes=>array(array(kResultAttribute=>, kResultValue=>), ...))
	*/
	function getAttributes($domainName, $itemName) {
		$params = array();
		$params[self::$kSDBParam_DomainName] = $domainName;
		$params[self::$kSDBParam_ItemName] = $itemName;
		
		$return = $this->_simpleDBRequest(self::$kSDBAction_GetAttributes, $params);

		// i return these so that i can pass the result around and have it contain its own context
		$return[self::$kResultDomainName] = $domainName;
		$return[self::$kResultItemName] = $itemName;
		
		return $return;
	}
	

	/**
	* AWS SimpleDB API - Query  (use the query* calls to create your ing YOUR QUERY string)
	*
	* @param string		$domainName			// the domain name
	* @param string		$expression = 100	// built by hand or using expression*() utils
	* @param string		$nextToken = ""		// OPTIONAL - token supplied on last paged call
	* @param integer	$maxItems = 100		// OPTIONAL = max items you want returned 1-250, default = 100
	*
	* @return array(kResultCode, kResultMessage=>, kResultBoxUsage=>, kResultAttributes=>array(array(kResultName=>, kResultValue=>), ...))
	*/
	function query($domainName, $expression, $nextToken = "", $maxItems = 100) {
		$params = array();
		$params[self::$kSDBParam_DomainName] = $domainName;
		$params[self::$kSDBParam_Expression] = $expression;
		$params[self::$kSDBParam_MaxNumberOfItems] = $maxItems;
		if ( $nextToken != "" ) $params[self::$kSDBParam_NextToken] = $nextToken;
		
		$return = $this->_simpleDBRequest(self::$kSDBAction_Query, $params);

		// i return this so that i can pass the result around and have it contain its own context
		$return[self::$kResultDomainName] = $domainName;
		
		return $return;
	}
	
	
	/**
	 * AWS SimpleDB - Simulated API Call - Delete an Item (gets the attributes, then removes them all)
	 *
	 * @param string	$domainName
	 * @param string	$itemName
	 * 
	 * @return array(kResultCode, kResultMessage=>, kResultBoxUsage=>)
	 */
	function deleteItem($domainName, $itemName) {
		
		// first, get all of the attributes
		$result = $this->getAttributes($domainName, $itemName);
		if ( $result[self::$kResultCode] == 200 ) {
			$usage = @$result[self::$kResultUsage];		//remember the get cost
			
			// build up an attrib list for all attributes
			$this->attributeINIT();
			$attributes = $result[self::$kResultAttributes];
			foreach ( $attributes as $attribute ) {
				$name = $attribute[self::$kResultName];
				$value = $attribute[self::$kResultValue];
				$this->attributeADD($name, $value, false, 0);	// don't pad, as it is			
			}
			
			// and delete them all
			$result = $this->deleteAttributes($domainName, $itemName);
			
			// accumulate the usage of both calls
			$usage += @$result[self::$kResultUsage];
			$result[self::$kResultUsage] = $usage;
			
			// and return the result
			return $result;
		}
		
		// return the GET failure
		return $result;
	}
	
	
	
	// -------------------------------------------------------------------
	//
	//		C A L L E R   U T I L I T I E S
	//
	// -------------------------------------------------------------------
	
	
	/**
	* For debugging (to see what you've built), you shouldn't need this otherwise
	*
	* @return array			// the attributes array you've created through multiple attributeADD calls
	*/
	function attributeGET() {
		return $this->mBuildAttribute;
	}
	
	
	/**
	* Prepare for the creating an attribute (used for set). Call to start
	* after you call putAttributes() or deleteAttributes() after the call succeeds, this will be auto-called
	*
	*/
	function attributeINIT() {
		$this->mBuildAttribute = array();
	}
	
	
	/**
	* Add an attribute entry
	*
	* @param string 	$name				// attrib name
	* @param string 	$value				// attrib value
	* @param boolean	$replace = false	// OPTIONAL - if you want the named attrib to be replaced
	* @param boolean	$pad = -1			// 0 for no padding, -1 for default, >0 for pad value
	*/
	function attributeADD($name, $value, $replace = false, $pad = -1) {
		
		if ( array_key_exists($name, $this->mBuildAttribute) ) {
			// okay, it DOES exist, lets update it
			$entry = $this->mBuildAttribute[$name];
			$values = $entry["values"];
			if ( array_key_exists($value, $values) ) {
				// okay, already exists, guess we have to ignore it
			} else {
				// add the new value
				$values[] = $this->pad($value, $pad);
				$entry["values"] = $values;
			}
			
			if ( $replace == true ) {
				$entry["replace"] = true;
			}
		} else {
			// new attribute name, lets add a new one
			$entry = array();
			$entry["values"] = array($this->pad($value, $pad));
			if ( $replace == true ) {
				$entry["replace"] = true;
			}
		}
		
		// now place the entry (add or update)
		$this->mBuildAttribute[$name] = $entry;
	}
	
		
	/**
	* Create a test for an attribute with a list ORed together
	*
	* @param string 	$attribute			// the attribute to test
	* @param const 		$test				// constant from kQuery* list
	* @param string 	$value				// string OR Array of tests
	* @param boolean 	$not = false		// if you want the whole thing NOTted
	* @param integer	$pad = -1			// 0 for no padding, -1 for default, >0 for pad value
	* 
	* @return string						// comparison string
	*/
	function expressionComparisonOR($attribute, $test, array $values, $not = false, $pad = -1) {
		return $this->expressionComparison($attribute, $test, $values, $not, false, $pad);
	}

	
	/**
	 * Create a BETWEEN comparison, with support for auto-padding numbers
	 *
	 * @param string	$attribute			// attribute to search
	 * @param var		$low				// low boundry
	 * @param var		$high				// high boundry
	 * @param boolean	$inclusive = false	// include the high and low value?
	 * @param boolean	$not	= false		// if you want the result NOTed
	 * @param integer 	$pad	= -1		// pass 0 for no padding, else a positive number. -1 give default supplied on constructor
	 * 
	 * @return string						// comparison string
	 */
	function expressionBetween($attribute, $low, $high, $inclusive = true, $not = false, $pad = -1) {
		$out = "[";
		if ( $out == true ) $out .= "NOT ";
		
		$low = $this->pad($low, $pad);
		$high = $this->pad($high, $pad);
		
		$out .= "'$attribute' ";
		$out .= ($inclusive == true ) ? self::$kQueryGreaterThanOrEquals : self::$kQueryGreaterThan;
		$out .= " '$low'";
		
		$out .= " AND ";

		$out = "'$attribute' ";
		$out .= ($inclusive == true ) ? self::$kQueryLessThanOrEquals : self::$kQueryLessThan;
		$out .= " '$high'";
		
		$out .= "]";
		
		return $out;
	}
	
	/**
	* Create a test for an attribute with a list ANDed together
	*
	* @param string $attribute			// the attribute to test
	* @param const $test					// constant from kQuery* list
	* @param string $value				// string OR Array of tests
	* @param boolean $not = false			// if you want the whole thing NOTted
	* 
	* @return string						// comparison string
	*/
	function expressionComparisonAND($attribute, $test, array $values, $not = false) {
		return $this->expressionComparison($attribute, $test, $values, $not, true);
	}

	
	/**
	 * Create a a simple single comparison (if you are doing a list, you can use the AND / OR wrappers)
	 *
	 * @param string $attribute			// the attribute to test
	 * @param const $test					// constant from kQuery* list
	 * @param string $valueArrayOrSingle	// string OR Array of values to test
	 * @param boolean $not = false			// if you want the whole thing NOTted
	 * @param boolean $and = true			// AND or OR
	 * 
	 * @return string						// comparison string
	 */
	function expressionComparison($attribute, $test, $valueArrayOrSingle, $not = false, $and = true) {
		$out = "[";
		if ( $not == true ) $out .= "NOT ";
		
		if ( is_array($valueArrayOrSingle) ) {
			$first = true;
			foreach ( $valueArrayOrSingle as $value ) {
				if ( $first == true ) {
					$first = false;
			} else {
					$out .= " AND ";
				}
				$out .= "'$attribute' $test '$value'";
			}
		} else {
			$out .= "'$attribute' $test '$value'";
		}
 		$out .= "]";
 		
 		return $out;
	}
	
	
	/**
	 * Intersection between 2 comparisons
	 *
	 * @param string $comparison1					// comparison string
	 * @param string $comparison2					// comparison string
	 * @param boolean $not = false					// if you want the whole thing NOTed
	 * 
	 * @return string								// query expression string
	 */
	function expressionIntersection($comparison1, $comparison2, $not = false) {
		$out = "";
		if ( $not == true ) $out .= "NOT ";
		
		$out .= $comparison1 . 	" intersection " . $comparison2;
		
		return $out;
	}
	

	/**
	 * Union of 2 comparisons
	 *
	 * @param string $comparison1					// comparison string
	 * @param string $comparison2					// comparison string
	 * @param boolean $not = false					// if you want the whole thing NOTed
	 * 
	 * @return string								// query expression string
	 */
	function expressionUnion($comparison1, $comparison2, $not = false) {
		$out = "";
		if ( $not == true ) $out .= "NOT ";
		
		$out .= $comparison1 . 	" union " . $comparison2;
		
		return $out;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	// ----------------------------------------------------------
	// 
	//      P R I V A T E   F U N C T I O N S
	//
	// ----------------------------------------------------------

	/**
	 * Pad an INT or FLOAT to the pad size requested. safe to call, it will do the right thing
	 *
	 * @param var $value
	 * @param int $pad
	 * 
	 * @return string
	 */
	function pad($value, $pad) {
		if ( $pad == -1 ) $pad = $this->mDefaultDigitPad;

		if ( $pad > 0 ) {
			if ( is_numeric($value) ) {
				return str_pad($value, $pad, "0", STR_PAD_LEFT);
			}
		}
				
		return $value;
	}
	
	
	/**
	 * Helper as put and delete are nearly identical in function
	 *
	 * @param string $action				// kSDBAction_PutAttributes or kSDBAction_DeleteAttributes
	 * @param string $domainName			// the Domain Name
	 * @param string $itemName				// the Item Name
	 * 
	 * @return array						// array(kResultCode=>, kResultBody=>, kResultBoxUsage=>)
	 */
	private function _putOrDeleteAttributes($action, $domainName, $itemName) {
		
		$params = array();
		
		$params[self::$kSDBParam_DomainName] = $domainName;
		$params[self::$kSDBParam_ItemName] = $itemName;
		
		$counter = 0;
		foreach ( $this->mBuildAttribute as $name=>$entry ) {
			$values = $entry['values'];
			if ( $action == self::$kSDBAction_PutAttributes ) {
				$replace = @$entry['replace'];
			}

			foreach ( $values as $value ) {
				$params[self::$kSDBParam_Attribute . $counter . self::$kSDBParam_Name]  = $name;
				$params[self::$kSDBParam_Attribute . $counter . self::$kSDBParam_Value] = $value;
				if ( $action == self::$kSDBAction_PutAttributes && $replace == true ) {
					$params[self::$kSDBParam_Attribute . $counter . self::$kSDBParam_Replace] = "true";
				}
				
				$counter++;	
			}
		}
		
		$result = $this->_simpleDBRequest($action, $params);
		
		return $result;
	}

	
	/**
	 * Make a request to the SimpleDB service
	 *
	 * @param string $action		// amazon action
	 * @param array $params			// array of the params
	 * 
	 * @return array				// array("result"=>(int)$code, "body"=>$responseBody)
	 */
	private function _simpleDBRequest($action, array $params) {
		if ( $params == null ) $params = array();

		$params[self::$kSDBParam_Action]			= $action;
		$params[self::$kSDBParam_AWSAccessKeyId]	= $this->mAccessKeyID;
		$params[self::$kSDBParam_Timestamp] 		= gmdate(self::$kSDBValue_TimestampFormat, time());
		$params[self::$kSDBParam_Version] 			= self::$kSDBValue_APIVersion;
		$params[self::$kSDBParam_SignatureVersion] = 1;
		$params[self::$kSDBParam_Signature] 		= $this->_signParams($params);
		
		return $this->_post($params);
	}

	
	/**
	 * Sign the parameters, following Amazon's guidelines for either version 0 or version 1 signing
	 *
	 * @param array $params			// array of all (except for the signiture) params to be passed to amazon
	 * 
	 * @return string				// signature string
	 */
	private function _signParams(array $params) {
		
		$version = $params[self::$kSDBParam_SignatureVersion];
		switch ( $version ) {
			
			case self::$kSDBValue_oldSignatureVersion:
				// old way is just the action and timestamp
				$data =  $parameters[self::$kSDBParam_Action] .  $parameters[self::$kSDBParam_Timestamp];
				break;
			
			case self::$kSDBValue_newSignatureVersion:
				// new safer version - sort the params, and jam them together $key$value...
				uksort($params, "strnatcasecmp");
				
				$data = "";
				foreach ($params as $key=>$value) {
					$data .= $key . $value;
				}
				break;

			default:
				return "INVALID_SIGNATURE_VERSION";	// just return something that will cause the call to fail
				
		}
		
		// Hash the heck out of it
		return base64_encode (	pack("H*", sha1((str_pad($this->mSecretAccessKey, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) .
								pack("H*", sha1((str_pad($this->mSecretAccessKey, 64, chr(0x00)) ^ (str_repeat(chr(0x36), 64))) .
								$data)))) );
	}
	
	
	/**
	 * Perform POST post with exponential retries on error 500 and 503. 
	 * 
	 * @param array $params			// all params to pass on the post
	 * 
	 * @return array				// array(Status=>(int)$code, ResponseBody=>$responseBody)
	 */
	private function _post(array $params) {
		
		// build the content
		$content = http_build_query($params, "", "&");
		
		// and the post contents
		$post  = "POST / HTTP/1.0"															. "\r\n";
		$post .= "Host: " 			. self::$kServiceEndpoint 								. "\r\n";
		$post .= "Content-Type: " 	. "application/x-www-form-urlencoded; charset=utf-8"	. "\r\n";
		$post .= "Content-Length: " . strlen($content)										. "\r\n";
		$post .= "User-Agent: " 	. self::$kUserAgent 									. "\r\n";
		$post .= 																			  "\r\n";
		$post .= $content;

		$socket = @fsockopen(self::$kServiceEndpoint, 80, $errno, $errstr, 10);
  		if ( $socket ) {
			fwrite($socket, $post);

			$response = "";
			while ( !feof($socket) ) {
				$response .= fgets($socket, 1024);
			}
			fclose($socket);
		
			// uncomment if you want raw logs of all of your simpledb responses
			$this->log("simpledb", "CONTENT=$content<BR><BR>RESPONSE=$response");
			
			// turn the result into an key=>value array of results
			return $this->_extractResult($response);
		}
		
		// return a fail result array
		return array(self::$kResultCode=>404, self::$kResultMessage=>self::$kServiceEndpoint . " not found ($errstr)");
	}
	
	
	/**
	 * Brute force the content out, trying 
	 *
	 * @param string $result			// the result from the simpleDB
	 */
	private function _extractResult($result) {
		$return = array();
		
		// get the first three things things from the header we care about
		$headerlines = explode("\r\n", $result, 2);
		list($protocol, $code, $message) = explode(" ", $headerlines[0], 3);
		$return[self::$kResultCode] = $code;
		$return[self::$kResultMessage] = $message;
		
		// get the XML portion
		$rightheader = strpos($result, "\r\n\r\n");							// find the end of the header
		$leftxml = strpos($result, ">" . chr(10), $rightheader);
		$xml = substr($result, $leftxml+2);

		// lets grab the cost, incase you care
		$start = 0;
		$return[self::$kResultUsage] = $this->_xmlGetTag($xml, self::$kSDBResult_BoxUsage, $start);

		// okay, we need to get the next tag, as that tells us the result type
		$start = 0;
		$responseTag = $this->_xmlGetNextTag($xml, $start);
		$type = substr($responseTag, 0, (strlen($responseTag)-strlen("Response")));	// remove the word "Response" so the type will match a constant
		
		// now get what we want for each type
		switch ( $type ) {
			case self::$kSDBAction_CreateDomain:
			case self::$kSDBAction_DeleteDomain:
			case self::$kSDBAction_PutAttributes:
			case self::$kSDBAction_DeleteAttributes:
				// nothing to do
				break;

			case self::$kSDBAction_ListDomains:
				
				// get the domains into an array
				$start = 0;
				$domains = array();
				$domainName = $this->_xmlGetTag($xml, self::$kSDBParam_DomainName, $start);
				while ( $domainName != null ) {
					$domains[] = $domainName;
					$domainName = $this->_xmlGetTag($xml, self::$kSDBParam_DomainName, $start);				
				}
				$return[self::$kResultDomains] = $domains;
				
				// get the nextTag, if present
				$start = 0;
				$nextTag = $this->_xmlGetTag($xml, self::$kSDBResult_NextToken, $start);
				if ( $nextTag != null ) {
					$return[self::$kSDBResult_NextToken] = $nextTag;
				}
				break;

			case self::$kSDBAction_GetAttributes:
				$attributes = array();

				$start = 0;
				$attribute = $this->_xmlGetTag($xml, self::$kSDBResult_Attribute, $start);
				while ( $attribute != null ) {
					$entry = array();
					
					$s = 0;
					$entry[self::$kResultName] = $this->_xmlGetTag($attribute, self::$kSDBResult_Name, $s);
					
					$s = 0;	// start at the beginning again, because it is small, and they could reverse ordering...
					$entry[self::$kResultValue] = $this->_xmlGetTag($attribute, self::$kSDBResult_Value, $s);
					
					// save off this one
					$attributes[] = $entry;

					// and get the next
					$attribute = $this->_xmlGetTag($xml, self::$kSDBResult_Attribute, $start);
				}
				$return[self::$kResultAttributes] = $attributes;
				break;

			case self::$kSDBAction_Query:
				$items = array();

				$start = 0;
				$item = $this->_xmlGetTag($xml, self::$kSDBResult_ItemName, $start);
				while ( $item != null ) {
					$items[] = $item;

					// and get the next
					$item = $this->_xmlGetTag($xml, self::$kSDBResult_ItemName, $start);
				}
				$return[self::$kResultItems] = $items;
				
				// get the nextTag, if present
				$start = 0;
				$nextTag = $this->_xmlGetTag($xml, self::$kSDBResult_NextToken, $start);
				if ( $nextTag != null ) {
					$return[self::$kSDBResult_NextToken] = $nextTag;
				}
				
				break;

			Default:
				break;

		}
		
		return $return;
	}

	
	/**
	 * Get EITHER the next tag OR the content for the next tag
	 *
	 * @param string $content			// the haystack
	 * @param integer &$start			// VAR - the start of where to look. Either contains Start of content, or END of tag
	 * @param boolean $getContent		// true if you want the tag content
	 * @return string					// either the TAG or the CONTENT
	 */
	private function _xmlGetNextTag($xml, &$start, $getContent = false) {
		$left = strpos($xml, "<", $start);
		if ( $left === false ) return null;		// not found
			
		$right = strpos($xml, ">", $left);
		if ( $right === false ) return null;	// not found
		
		$raw = substr($xml, $left+1, ($right-$left)-1);
		$parts = explode(" ", $raw, 2);
		
		$tag = $parts[0];
		
		// no content, just return the tag, and set the start to the beginning of the content
		if ( $getContent == false ) {
			$start = $right + 1;
			return $tag;
		}
		
		// okay, if they want content, return that
		$bodyleft = $right + 1;
		$bodyright = strpos($xml, "</$tag>", $bodyleft);
		if ( $bodyright === false ) { echo "close tag not found"; return null; }
		
		$content = substr($xml, $bodyleft, ($bodyright-$bodyleft));
		$start = $bodyright + strlen("</$tag>");
		return $content;
	}
	
	
	/**
	 * Get the contents of the NEXT tag that matches the supplied tag string
	 *
	 * @param unknown_type $xml				// the xml
	 * @param unknown_type $tag				// tag to find
	 * @param unknown_type &$start			// where to start looking, value updated to be the offset to the end of the close tag
	 * 
	 * @return string						// contents between the open and close tag
	 */
	private function _xmlGetTag($xml, $tag, &$start) {
		// find the start of the start tag
		$left = strpos($xml, "<$tag", $start);
		if ( $left === false ) return null;		// not found
		
		// and the right edge of the start tag
		$right = strpos($xml, ">", $left);
		if ( $right === false ) return null;	// not found
		
		
		// now find the close tag
		$bodyleft = $right + 1;
		$bodyright = strpos($xml, "</$tag>", $bodyleft);
		if ( $bodyright === false ) { echo "close tag not found"; return null; }
		
		$content = substr($xml, $bodyleft, ($bodyright-$bodyleft));
		$start = $bodyright + strlen("</$tag>");
		return $content;
	}

	
	/**
	 * simple log for debugging
	 *
	 * @param unknown_type $file
	 * @param unknown_type $contents
	 */
	private function log($file, $contents) {
		$file = fopen("/tmp/$file.log", "a");
		$d = date("Y/m/d H:m:s");
		
		fwrite($file, "$d => $contents" . "\r\n");
		fclose($file);
	}
};
?>