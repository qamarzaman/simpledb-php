# Introduction #

I wanted a straight forward PHP wrapper around Amazon's SimpleDB (sdb). This is what I ended up with


# Details #

I've tried to keep everything in a single .php file. There is a call for each call in the sdb, with the addition of a few more convenient compound calls.


Here is what the Class looks like:


```
    // constructor that lets you keep your keys elsewhere, and specify default
    // padding for attribute values which pass is_numberic()
    $sdb = new simpledb($yourAccessKeyID, $yourSecretKey, $defaultPadding);
   
    // Nice easy wrappers for all of the core API methods. Return is in the form
    // of an array containing: ResultCode, ResultMessage, BoxUsage and call
    // specific data.

    $sdb->createDomain($domainName);
   
    $sdb->deleteDomain($domainName);

    // return also contains [NextToken], and an array(domainName, ...)
    $sdb->listDomains($nextToken = "", $maxDomains = 100);

    $sdb->putAttributes($domainName, $itemName);
   
    $sdb->deleteAttributes($domainName, $itemName);

    // return also contains an array(array(attribName=>, attribValue=>), ...)
    $sdb->getAttributes($domainName, $itemName);

    // return also contains [NextToken], and an array(ItemName, ...)
    $sdb->query($domainName, $expression, $nextToken = "", $maxItems = 100);   

    // some convenience versions of the core API methods
    $sdb->deleteAttribute($domainName, $itemName, $attribName, $attribValue, $pad = -1);
   
    $sdb->putAttribute($domainName, $itemName, $attribName, $attribValue, $replace = false, $pad = -1);
   
    // deletes all attributes for an Item
    $sdb->deleteItem($domainName, $itemName);          

    // Helpers for creating an Attribute Array
    $sdb->attributeINIT();
    $sdb->attributeADD($name, $value, $replace = false, $pad = -1);
   
    // Helpers for creating the query expression string
    $sdb->expressionComparisonOR($attribute, $test, array $values, $not = false, $pad = -1);
    $sdb->expressionComparisonAND($attribute, $test, array $values, $not = false);
    $sdb->expressionBetween($attribute, $low, $high, $inclusive = true, $not = false, $pad = -1);
    $sdb->expressionComparison($attribute, $test, $valueArrayOrSingle, $not = false, $and = true);
    $sdb->expressionIntersection($comparison1, $comparison2, $not = false);
    $sdb->expressionUnion($comparison1, $comparison2, $not = false);
```