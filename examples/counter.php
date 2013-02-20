<?
require '../bloom.class.php';
define('NL', "<br>\r\n");

/*
* Init object for 10 entries
* counter option is required for delete ability but it much slower
**/
$bloom = new Bloom(array(
	'entries_max' => 10,
	'counter' => true
));

while( count($vars) < 10 )
	$vars[] = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);

/*
* Set 10 values to object
**/
$bloom->set($vars);

/*
* Check existance of one object, return boolean
**/
echo $bloom->has( $vars[ rand(0, count($vars)-1) ] ), NL;
/*
* Check existance of one object, return number of Hashes aprovemts
**/
echo $bloom->has( $vars[ rand(0, count($vars)-1) ], false ), NL;

/*
* Check existance of many objects, return boolean
**/
echo json_encode( $bloom->has( array_slice($vars, 0, 6) ) ), NL;
/*
* Check existance of many objects, return number of Hashes aprovemts
**/
echo json_encode( $bloom->has( array_slice($vars, 0, 6), false ) ), NL;

/*
* Try to delete one object, returns true
**/
echo $bloom->delete( $vars[1] ), NL;
/*
* Try to delete many objects, returns array of boolean
**/
echo json_encode( $bloom->delete( array_slice($vars, 0, 6) ) ), NL;