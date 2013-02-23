<?php
error_reporting(E_ERROR | E_PARSE);
include '../bloom.class.php';

class Test extends PHPUnit_Framework_TestCase
{
	public function testInitiation()
	{
		$params_null = array();
		$this->assertEquals('object', gettype( new Bloom($params_null) ));
		
		$params_normal = array(
			'entries_max' => 10000,
			'set_size' => 40000,
			'error_chance' => 0.01
		);
		$bloom_normal = new Bloom($params_normal);
		$this->assertEquals('object', gettype( $bloom_normal ));
		$this->assertEquals( $params_normal['entries_max'], $bloom_normal->entries_max);
		$this->assertEquals( $params_normal['set_size'], $bloom_normal->set_size);
		$this->assertEquals( $params_normal['error_chance'], $bloom_normal->error_chance);
	}
	
	/**
    * @expectedException Exception
    */
	public function testInitiationExceptionRange()
	{
		$params_error = array(
			'entries_max' => -100,
		);
		$bloom = new Bloom($params_error);
	}
	
	/**
    * @expectedException Exception
    */
	public function testInitiationExceptionType()
	{		
		$params_error = array(
			'hash_count' => '123123',
		);
		$bloom = new Bloom($params_error);
	}
	
	public function testHashesDifference()
	{
		$bloom = new Bloom();
		foreach( $bloom->hashes as $hash ) {
			$seeds[] = $hash->seed;
		}
		sort( $seeds );
		foreach( $seeds as $k => $rslt )
			if( $seeds[$k+1] && $seeds[$k+1] == $rslt ) {
				$this->fail('Similar results by '.$bloom->hash_count.' hashes and seeds: '.json_encode($seeds).'.');
			}
	}
	
	public function testSetAndGetTheSame()
	{
		$params = array(
			'entries_max' => 1,
		);
		$bloom = new Bloom($params);
		$string = uniqid();
		$bloom->set( $string );
		$this->assertEquals( true, $bloom->has( $string ));
		$this->assertEquals( 1, $bloom->has( $string, false ));
	}
	
	public function testSetAndGetTheSameArray()
	{
		$params = array(
			'entries_max' => 1000,
		);
		$bloom = new Bloom($params);
		
		while( !$string[ $params['entries_max'] ] ) {
			$string[] = uniqid();
			$results_bool[] = true;
			$results_num[] = 1;
		}
		$bloom->set( $string );
		
		$this->assertEquals( $results_bool, $bloom->has( $string ));
		$this->assertEquals( $results_num, $bloom->has( $string, false ));
	}
	
	public function testSetAndGetDifferentArrayErrorChance()
	{
		$params = array(
			'entries_max' => 2000,
		);
		$bloom = new Bloom($params);
		
		while( !$string[ $params['entries_max']/2 ] ) {
			$string[] = uniqid() . rand();
			$check[] = uniqid() . rand();
			$results_bool[] = false;
			$results_num[] = 0;
		}
		$bloom->set( $string );
		$get = $bloom->has( $check );
		$proc = array_sum($get)/$params['entries_max'];
		if( $proc > $bloom->error_chance )
			$this->fail('Error chance greater. Error_chance '.$bloom->error_chance.'. Counted: '.$proc);
	}
	
	public function testCounterGetTest()
	{
		$params = array(
			'counter' => true
		);
		$bloom = new Bloom($params);
		$bloom->set = '';
		for($i=0; $i<strlen($bloom->alphabet); $i++)
			$bloom->set .= $bloom->alphabet[$i];
		$num = rand(0, strlen($bloom->alphabet));
		$this->assertEquals($bloom->alphabet[$num], $bloom->counter($num, 0, true));
		$bloom->counter($num, 1);
		$this->assertEquals($bloom->alphabet[$num+1], $bloom->counter($num, 0, true));
	}
	
	public function testUnsetTestOneToOneValue()
	{
		$params = array(
			'counter' => true
		);
		$bloom = new Bloom($params);
		$bloom->set('A good one');
		
		$this->assertEquals( true, $bloom->has('A good one') );
		$this->assertEquals( true, $bloom->delete('A good one') );
		
		$set = explode(',', $bloom->set);
		$this->assertEquals( 0, array_sum($set) );
	}
	
	public function testUnsetTestManyToManyValue()
	{
		$params = array(
			'counter' => true
		);
		$bloom = new Bloom($params);
		for( $i=0; $i < 10; $i++ ) {
			$vars[] = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
			$res[] = true;
		}
			
		$bloom->set($vars);
		$this->assertEquals( $res, $bloom->has($vars) );
		$this->assertEquals( $res, $bloom->delete($vars) );
		$set = explode(',', $bloom->set);
		$this->assertEquals( 0, array_sum($set) );
	}
	
	public function testEmptyCacheTest()
	{
		if(is_file('counter')) unlink('counter');
		if(is_file('default')) unlink('default');
		
		$params = array(
			'counter' => true
		);
		$bloom_counter = new Bloom(array('counter'=>true,'entries_max'=>10));
		$bloom_default = new Bloom(array('entries_max'=>10));
		for( $i=0; $i < 10; $i++ ) {
			$vars[] = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
		}
		$bloom = array(
			'counter' => $bloom_counter,
			'default' => $bloom_default
		);
		
		file_put_contents('counter', serialize( $bloom['counter'] ));
		file_put_contents('default', serialize( $bloom['default'] ));
		
		$bloom_counter->set($vars);
		$bloom_default->set($vars);
		$set_counter = $bloom_counter->set;
		$set_default = $bloom_default->set;
		
		$bloom_counter = null;
		$bloom_default = null;
		
		$bloom_counter = unserialize( file_get_contents('counter') );
		$bloom_default = unserialize( file_get_contents('default') );
		$bloom_counter->set($vars);
		$bloom_default->set($vars);
		
		$this->assertEquals($set_counter, $bloom_counter->set);
		$this->assertEquals($set_default, $bloom_default->set);
	}
	
	public function testFilledCacheTest()
	{
		if(is_file('counter')) unlink('counter');
		if(is_file('default')) unlink('default');
		
		$params = array(
			'counter' => true
		);
		$bloom_counter = new Bloom(array('counter'=>true,'entries_max'=>10));
		$bloom_default = new Bloom(array('entries_max'=>10));
		for( $i=0; $i < 10; $i++ ) {
			$vars[] = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
			$res[] = true;
		}
		
		$bloom_counter->set($vars);
		$bloom_default->set($vars);
		
		$bloom = array(
			'counter' => $bloom_counter,
			'default' => $bloom_default
		);
		file_put_contents('counter', serialize( $bloom['counter'] ));
		file_put_contents('default', serialize( $bloom['default'] ));
		
		$set_counter = $bloom_counter->set;
		$set_default = $bloom_default->set;
		
		$bloom_counter = null;
		$bloom_default = null;
		
		$bloom_counter = unserialize( file_get_contents('counter') );
		$bloom_default = unserialize( file_get_contents('default') );
		
		$this->assertEquals( $res, $bloom_counter->has($vars) );
		$this->assertEquals( $res, $bloom_default->has($vars) );
	}
}