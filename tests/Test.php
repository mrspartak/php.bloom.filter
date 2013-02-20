<?php
error_reporting(E_ALL | E_PARSE);
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
		$string = uniqid();
		foreach( $bloom->hashes as $hash ) {
			$result[] = $hash->crc($string, $bloom->set_size);
			$seeds[] = $hash->seed;
		}
		sort( $result );
		foreach( $result as $k => $rslt )
			if( $result[$k+1] && $result[$k+1] == $rslt ) {
				$this->fail('Similar results by '.$bloom->hash_count.' hashes and seeds: '.json_encode($seeds).'. Result: '.json_encode($result));
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
}