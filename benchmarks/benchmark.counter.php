<?
require '../bloom.class.php';
define('NL', "<br>\r\n# ");

$number = ( (int) $_GET['number'] ) ? (int) $_GET['number'] : 1000;
/*
* Init object for $num entries
**/
$bloom = new Bloom(array(
	'entries_max' => $number,
	'error_chance' => 0.01,
	'counter' => true,
	'hash' => array(
		'strtolower' => false
	)
));

while( count($vars) < $number )
	$vars[] = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, 10);

/*
* Insert to bloom 
**/
$stat['bloom']['insert']['s'] = get_stat();
$bloom->set($vars);
$stat['bloom']['insert']['e'] = get_stat();

/*
* Insert to array
**/
$stat['array']['insert']['s'] = get_stat();
for( $i = 0; $i < $number; $i++ )
	$array[] = $vars[$i];
$stat['array']['insert']['e'] = get_stat();

while( count($check) < $number/2 )
	$check[] = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);
	
/*
* Check existance with bloom
**/
$stat['bloom']['has']['s'] = get_stat();
$stat['bloom']['has']['errors'] = $bloom->has($check);
$stat['bloom']['has']['e'] = get_stat();

/*
* Check existance in array
**/
$stat['array']['has']['s'] = get_stat();
for( $i = 0; $i < $number; $i++ )
	$stat['array']['has']['errors'][] = in_array($check[$i], $array);
$stat['array']['has']['e'] = get_stat();


/*
* Delete from bloom
**/
$stat['bloom']['delete']['s'] = get_stat();
$bloom->delete($check);
$stat['bloom']['delete']['e'] = get_stat();
/*
* Check existance in array
**/
$stat['array']['delete']['s'] = get_stat();
for( $i = 0; $i < $number; $i++ )
	unset($array[$check[$i]]);
$stat['array']['delete']['e'] = get_stat();


/*
* Results
**/
$result['bloom']['insert']['time'] = $stat['bloom']['insert']['e']['timestamp'] - $stat['bloom']['insert']['s']['timestamp'];
$result['array']['insert']['time'] = $stat['array']['insert']['e']['timestamp'] - $stat['array']['insert']['s']['timestamp'];
$result['bloom']['insert']['mem'] = $stat['bloom']['insert']['e']['memory'] - $stat['bloom']['insert']['s']['memory'];
$result['array']['insert']['mem'] = $stat['array']['insert']['e']['memory'] - $stat['array']['insert']['s']['memory'];

$result['bloom']['has']['time'] = $stat['bloom']['has']['e']['timestamp'] - $stat['bloom']['has']['s']['timestamp'];
$result['array']['has']['time'] = $stat['array']['has']['e']['timestamp'] - $stat['array']['has']['s']['timestamp'];
$result['bloom']['has']['mem'] = $stat['bloom']['has']['e']['memory'] - $stat['bloom']['has']['s']['memory'];
$result['array']['has']['mem'] = $stat['array']['has']['e']['memory'] - $stat['array']['has']['s']['memory'];

$result['bloom']['del']['time'] = $stat['bloom']['delete']['e']['timestamp'] - $stat['bloom']['delete']['s']['timestamp'];
$result['array']['del']['time'] = $stat['array']['delete']['e']['timestamp'] - $stat['array']['delete']['s']['timestamp'];
$result['bloom']['del']['mem'] = $stat['bloom']['delete']['e']['memory'] - $stat['bloom']['delete']['s']['memory'];
$result['array']['del']['mem'] = $stat['array']['delete']['e']['memory'] - $stat['array']['delete']['s']['memory'];

$result['bloom']['total']['time'] = $result['bloom']['insert']['time'] + $result['bloom']['has']['time'];
$result['array']['total']['time'] = $result['array']['insert']['time'] + $result['array']['has']['time'];
$result['bloom']['total']['mem'] = $result['bloom']['insert']['mem'] + $result['bloom']['has']['mem'];
$result['array']['total']['mem'] = $result['array']['insert']['mem'] + $result['array']['has']['mem'];

echo NL, 'Bloom Filter vs in_array', NL;
echo '<strong>###########</strong>', NL;
echo 'Current number of objects: '.$number.', number of check objects: '. count($check), NL;
echo 'Choose number of objects: ', lnk(100), lnk(1000), lnk(10000), NL;


echo '<strong>## Setting ##</strong>', NL;
echo 'Bloom time: '.$result['bloom']['insert']['time'].' sec.', NL;
echo 'Array time: '.$result['array']['insert']['time'].' sec.', NL;
echo advantage($result['bloom']['insert']['time']/$result['array']['insert']['time']), NL;
	
echo 'Bloom memory: '.$result['bloom']['insert']['mem'].' bytes.', NL;
echo 'Array memory: '.$result['array']['insert']['mem'].' bytes.', NL;
echo advantage($result['bloom']['insert']['mem']/$result['array']['insert']['mem']), NL, NL;
	

echo '<strong>## Checking ##</strong>', NL;
echo 'Bloom time: '.$result['bloom']['has']['time'].' sec.', NL;
echo 'Array time: '.$result['array']['has']['time'].' sec.', NL;
echo advantage($result['bloom']['has']['time']/$result['array']['has']['time']), NL;

echo 'Bloom memory: '.$result['bloom']['has']['mem'].' bytes.', NL;
echo 'Array memory: '.$result['array']['has']['mem'].' bytes.', NL;
echo advantage($result['bloom']['has']['mem']/$result['array']['has']['mem']), NL, NL;


echo '<strong>## Deleting ##</strong>', NL;
echo 'Bloom time: '.$result['bloom']['del']['time'].' sec.', NL;
echo 'Array time: '.$result['array']['del']['time'].' sec.', NL;
echo advantage($result['bloom']['del']['time']/$result['array']['del']['time']), NL;

echo 'Bloom memory: '.$result['bloom']['has']['mem'].' bytes.', NL;
echo 'Array memory: '.$result['array']['has']['mem'].' bytes.', NL;
echo advantage($result['bloom']['del']['mem']/$result['array']['del']['mem']), NL, NL;


echo '<strong>## Total ##</strong>', NL;
echo 'Bloom time: '.$result['bloom']['total']['time'].' sec.', NL;
echo 'Array time: '.$result['array']['total']['time'].' sec.', NL;
echo advantage($result['bloom']['total']['time']/$result['array']['total']['time']), NL;

echo 'Bloom memory: '.$result['bloom']['total']['mem'].' bytes.', NL;
echo 'Array memory: '.$result['array']['total']['mem'].' bytes.', NL;
echo advantage($result['bloom']['total']['mem']/$result['array']['total']['mem']), NL, NL;



echo '<strong>## Errors ##</strong>', NL;
echo 'Bloom: '.array_sum($stat['bloom']['has']['errors']).' from '.$bloom->error_chance*count($check).' allowed by '.($bloom->error_chance*100).'% error chance.', NL;
echo 'Array: '.array_sum($stat['array']['has']['errors']).'.';

function get_stat() {
	return array(
		'memory' => memory_get_usage(),
		'timestamp' => gettimeofday(true) 
	);
}
function advantage($koef) {
	if($koef < 1)
		echo '<span style="color:green">advantage <strong>'.(1/$koef).'</strong> times</span>', NL;
	else
		echo '<span style="color:red">disadvantage <strong>'.$koef.'</strong> times</span>', NL;
}
function lnk($number) {
	echo ' # <a href="?number='.$number.'">'.$number.'</a> # ';
}