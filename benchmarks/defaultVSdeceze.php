<?
require '../bloom.class.php';
require 'other/BloomFilter/BloomFilter.php';
define('NL', "<br>\r\n# ");
/*
	deceze / BloomFilter
	https://github.com/deceze/BloomFilter
*/

$number = ( (int) $_GET['number'] ) ? (int) $_GET['number'] : 1000;
/*
* Init object for $num entries
**/
$bloom = new Bloom(array(
	'entries_max' => $number,
	'error_chance' => 0.01,
	'hash' => array(
		'strtolower' => false
	)
));
$foe = BloomFilter::constructForTypicalSize($bloom->set_size, $bloom->entries_max);

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
$stat['foe']['insert']['s'] = get_stat();
for( $i = 0; $i < $number; $i++ )
	$foe->add($vars[$i]);
$stat['foe']['insert']['e'] = get_stat();

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
$stat['foe']['has']['s'] = get_stat();
for( $i = 0; $i < $number; $i++ )
	$stat['foe']['has']['errors'][] = $foe->maybeInSet($check[$i]);
$stat['foe']['has']['e'] = get_stat();


/*
* Results
**/
$result['bloom']['insert']['time'] = $stat['bloom']['insert']['e']['timestamp'] - $stat['bloom']['insert']['s']['timestamp'];
$result['foe']['insert']['time'] = $stat['foe']['insert']['e']['timestamp'] - $stat['foe']['insert']['s']['timestamp'];
$result['bloom']['insert']['mem'] = $stat['bloom']['insert']['e']['memory'] - $stat['bloom']['insert']['s']['memory'];
$result['foe']['insert']['mem'] = $stat['foe']['insert']['e']['memory'] - $stat['foe']['insert']['s']['memory'];

$result['bloom']['has']['time'] = $stat['bloom']['has']['e']['timestamp'] - $stat['bloom']['has']['s']['timestamp'];
$result['foe']['has']['time'] = $stat['foe']['has']['e']['timestamp'] - $stat['foe']['has']['s']['timestamp'];
$result['bloom']['has']['mem'] = $stat['bloom']['has']['e']['memory'] - $stat['bloom']['has']['s']['memory'];
$result['foe']['has']['mem'] = $stat['foe']['has']['e']['memory'] - $stat['foe']['has']['s']['memory'];

$result['bloom']['total']['time'] = $result['bloom']['insert']['time'] + $result['bloom']['has']['time'];
$result['foe']['total']['time'] = $result['foe']['insert']['time'] + $result['foe']['has']['time'];
$result['bloom']['total']['mem'] = $result['bloom']['insert']['mem'] + $result['bloom']['has']['mem'];
$result['foe']['total']['mem'] = $result['foe']['insert']['mem'] + $result['foe']['has']['mem'];

echo '# <a href="https://github.com/mrspartak/php.bloom.filter" target="_blank">My Bloom</a> VS <a href="https://github.com/deceze/BloomFilter">Foe Bloom</a>', NL;

echo NL, '<strong>###########</strong>', NL;
echo 'Current number of objects: '.$number.', number of check objects: '. count($check), NL;
echo 'Choose number of objects: ', lnk(100), lnk(1000), lnk(10000), NL;
echo '<strong>## Setting ##</strong>', NL;


echo 'Bloom time: '.$result['bloom']['insert']['time'].' sec.', NL;
echo 'Foe time: '.$result['foe']['insert']['time'].' sec.', NL;
echo advantage($result['bloom']['insert']['time']/$result['foe']['insert']['time']), NL;
	
echo 'Bloom memory: '.$result['bloom']['insert']['mem'].' bytes.', NL;
echo 'Foe memory: '.$result['foe']['insert']['mem'].' bytes.', NL;
echo advantage($result['bloom']['insert']['mem']/$result['foe']['insert']['mem']), NL, NL;
	
	
echo '<strong>## Checking ##</strong>', NL;
echo 'Bloom time: '.$result['bloom']['has']['time'].' sec.', NL;
echo 'Foe time: '.$result['foe']['has']['time'].' sec.', NL;
echo advantage($result['bloom']['has']['time']/$result['foe']['has']['time']), NL;

echo 'Bloom memory: '.$result['bloom']['has']['mem'].' bytes.', NL;
echo 'Foe memory: '.$result['foe']['has']['mem'].' bytes.', NL;
echo advantage($result['bloom']['has']['mem']/$result['foe']['has']['mem']), NL, NL;


echo '<strong>## Total ##</strong>', NL;
echo 'Bloom time: '.$result['bloom']['total']['time'].' sec.', NL;
echo 'Foe time: '.$result['foe']['total']['time'].' sec.', NL;
echo advantage($result['bloom']['total']['time']/$result['foe']['total']['time']), NL;

echo 'Bloom memory: '.$result['bloom']['total']['mem'].' bytes.', NL;
echo 'Foe memory: '.$result['foe']['total']['mem'].' bytes.', NL;
echo advantage($result['bloom']['total']['mem']/$result['foe']['total']['mem']), NL, NL;

echo '<strong>## Errors ##</strong>', NL;
echo 'Bloom: '.array_sum($stat['bloom']['has']['errors']).' from '.$bloom->error_chance*count($check).' allowed by '.($bloom->error_chance*100).'% error chance.', NL;
echo 'Foe: '.array_sum($stat['foe']['has']['errors']).'.';

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