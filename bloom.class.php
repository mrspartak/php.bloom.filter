<?php
/**
* Bloom filter php
* 
* This is bloom's filter phph implementation
* 
* @author Spartak Kagramanayan <mr.spartak@rambler.ru>
* @version 0.5
*/

/**
* Main BloomClass
* Use cases:
* -to cooldown HDD usage
* -to speedup checking of object excistance
* -to save memory (only without counter)
*
* When to use:
* -Get/Set operations are more than 0.001
* -You need a fast check with big set, more than 100 000 entries
*/
class Bloom 
{
	/**
	* Bloom object container
	* @var mixed
	*/
	public $set;
	
	/**
	* Array of Hashes objects
	* @var array
	*/
	public $hashes;
	
	/**
	* Error chance (0;1)
	* @var float
	*/
	public $error_chance;
	
	/**
	* Size of set variable
	* @var int
	*/
	public $set_size;
	
	/**
	* Number of different hash objects
	* @var int
	*/
	public $hash_count;
	
	/**
	* Number of current entries
	* @var int
	*/
	public $entries_count;
	
	/**
	* Number of entries max
	* @var int
	*/
	public $entries_max;
	
	/**
	* Use counter or not (for remove ability)
	* @var boolean
	*/
	public $counter;
	
	/**
	* Map of user setup parameters
	* @access private
	* @var boolean
	*/
	private $map = array(
		'entries_max' => array(
			'type' => 'integer',
			'min' => 0
		),
		'error_chance' => array(
			'type' => 'double',
			'min' => 0,
			'max' => 1
		),
		'set_size' => array(
			'type' => 'integer',
			'min' => 100
		),
		'hash_count' => array(
			'type' => 'integer',
			'min' => 1
		),
		'counter' => array(
			'type' => 'boolean'
		),
		'hash' => array(
			'strtolower' => array(
				'type' => 'boolean'
			)
		)
	);
	
	/**
	* Class initiation
	* 
	* @param array
	* @return BloomObject
	*/
	public function __construct($setup = null)
	{
		/**
		*	Default parameters
		*/
		$params = array(
			'entries_max' => 100,
			'error_chance' => 0.001,
			'counter' => false,
			'hash' => array(
				'strtolower' => true
			)
		);
		
		/**
		*	Applying income user parameters 
		*/
		$params = Map::apply($this->map, $params, $setup);
		
		/**
		*	Setting every parameters as properties
		*/
		foreach($params as $key => $value)
			$this->$key = $value;
		
		/**
		*	Calculating size of set using maximum number of entries and error chance
		*/
		if(!$this->set_size)
			$this->set_size = -round( ( $this->entries_max * log($this->error_chance) ) / pow(log(2), 2) );
			
		/**
		*	Calculating number of hashes using maximum number of entries and set size
		*/
		if(!$this->hash_count)
			$this->hash_count = round($this->set_size * log(2) / $this->entries_max);
		
		/**
		*	Setting up HashObjects to hashes property
		*/
		for($i = 0; $i < $this->hash_count; $i++)
			$this->hashes[] = new Hash($params['hash']);
		
		/**
		*	Initiation set
		*/
		for($i = 0; $i < $this->set_size; $i++)
			$this->set .= '0';
		
		return $this;
	}
	
	/**
	* For serializing
	*
	* @return object for serializing
	*/	
	public function __sleep() {
		foreach( $this as $key => $attr )
			if($key != 'set' && $this->entries_count == 0)
				$result[] = $key;	
		return $result;
	}
	
	/**
	* For unserializing
	*
	* @return object unserialized object
	*/	
	public function __wakeup() {
		if($this->entries_count == 0)
			for($i = 0; $i < $this->set_size; $i++)
				$this->set .= '0';
	}
	
	/**
	* Set value to Bloom filter
	*
	* @param mixed
	* @return BloomObject
	*/	
	public function set( $mixed ) {
		/**
		*	In case of array given, no matter how depp it is, 
		* method calls itself recursively with each array element
		*/
		if( is_array( $mixed ) )
			foreach( $mixed as $arg )
				$this->set( $arg );
		/**
		*	Otherwise method set mark into set property by using every HashObject
		*/		
		else
			for( $i=0; $i < $this->hash_count; $i++ )
				$this->set[ $this->hashes[$i]->crc( $mixed, $this->set_size ) ] = 1;	
		
		return $this;
	}
	
	/**
	* Test set with given array or string, to check it existance
	*
	* @param mixed (array) or (string)
	* @param boolean
	* @return mixed (array) or (boolean) or (float)
	*/
	public function has( $mixed, $boolean = true ) {
		/**
		*	In case of array given will be returned array,
		* and method call's itself recursively with ararray's alements
		*/	
		if( is_array( $mixed ) ) {
			foreach( $mixed as $key => $arg )
				$result[$key] = $this->has( $arg, $boolean );
				
			return $result;	
		}	else {
			for( $i=0; $i < $this->hash_count; $i++ ) {
				$value = $this->set[ $this->hashes[$i]->crc( $mixed, $this->set_size ) ];
				
				/**
				*	$boolean parameter allows to choose what to return
				* boolean or the procent of entries pass
				*/
				if( $boolean && !$value )
					return false;
				elseif( $boolean === false )
					$c += ($value) ? 1 : 0;	
			}
			
			return ( $boolean === true ) ? true : $c/$this->hash_count;
		}
	}
}

/**
* MapClass
* Use cases:
* -check user's setup parameters and merge it with default one
*
* Map view:
* paramskey => arrayMap
*
* ArrayMap view
* -null (boolean): checks required parameter
* -type (string): checks for type of parameter (values from gettype())
* -min, max (float, int): allowed range of number value
*
* Throws Ecpetion with message
*/
class Map {
	
	/**
	* Main method, applie's default and given parameters with map
	*
	* @param array map
	* @param array default parameters
	* @param array given parameters
	* @return array merged result parameters
	*/
	static public function apply($map, $initial, $setup) {
		self::circl( $map, $setup );	
		return array_merge( $initial, (array) $setup );	
	}
	
	/**
	* Recursively follows map
	*
	* @param array map
	* @param array given parameters
	*/
	static private function circl( $map, $rabbit ) {
		foreach( $map as $k => $element ) {
			if( is_array( $element ) && !$element['type'] && $rabbit[$k] ) {
				unset( $rabbit[$k] );
				self::circl( $element, $rabbit[$k] );
			} else
				self::check( $element, $rabbit[$k] );
				unset( $rabbit[$k] );
		}
		
		if( $rabbit )
			throw new Exception('Unexpected array arguments. '.json_encode( $rabbit ));
	}
	
	/**
	* Check map rules for given object
	*
	* @param array map
	* @param mixed given parameters element
	*/
	static private function check( $map, $rabbit ) {
		/**
		*	required statement check
		*/
		if( $map['null'] === false && !$rabbit)
			throw new Exception('Must be not NULL');
		
		/**
		*	If no element exists, exit
		*/
		if( !$rabbit )
			return true;
			
		/**
		*	Check for type
		*/
		if( $map['type'] !== gettype($rabbit) && $map['type'] )
			throw new Exception('Wrong type '.gettype($rabbit).'! Must be '.$map['type']);
		
		/**
		*	Check for minimal range
		*/
		if( $map['min'] > $rabbit && $map['min'] !== null )
			throw new Exception('Interval overflow by '.$rabbit.'! Must be '.$map['min']);
			
		/**
		*	Check for maximal range
		*/
		if( $map['max'] < $rabbit && $map['max'] !== null )
			throw new Exception('Interval overflow by '.$rabbit.'! Must be '.$map['max']);	
	}
}

/**
* HashClass
* Use cases:
* -creates random hash generator
*/
class Hash {
	/**
	* Seed for unification every HashObject
	* @var array
	*/
	public $seed;
	
	/**
	* Parameters
	* @var array
	*/
	public $params;
	
	/**
	* Initialization
	*
	* @param array parameters
	* @return object HashObject
	*/
	public function __construct( $params ) {
		$this->params = $params;
		$this->seed[] = rand(0, 2048);
		$this->seed[] = rand(0, 2048);
	}
	
	/**
	* Hash use's crc32 and md5 algorithms to get number less than $size parameter
	*
	* @param mixed object to hash
	* @param int max number to return
	* @return int
	*/
	public function crc($string, $size) {
		$string = strval( $string );
		
		if( $this->params['strtolower'] === true )
			$string = mb_strtolower( $string, 'UTF-8' );
		
		return abs( crc32( md5( $this->seed[0] . $string . $this->seed[1] ) ) ) % $size;	
	}
}