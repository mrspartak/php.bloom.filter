php.bloom.filter
================
[![Build Status](https://travis-ci.org/mrspartak/php.bloom.filter.png)](https://travis-ci.org/mrspartak/php.bloom.filter)

[Bloom filter](http://en.wikipedia.org/wiki/Bloom_filter) - is the best way to check object existance and save memory and disk usage.

### Usage
Examples and benchmarks are available in examples directory.

##### Basic parameters:
 * **entries_max** (int) Maximal number of entries objects. Default: 100.
 * **error_chance** (float) (0;1) Chance of error check existance. Used for automatic setup next 2 parameters. Default: 0.001.
 * **set_size** (int) Size of set object. Default: calculated.
 * **hash_count** (int) Count of unique hash Objects. Default: calculated.
 * **counter** (boolean) Uses only if ypu want to remove elements too. Slows down. Default: false.
 * **hash** (array) Parameters for Hash objects.
  * **strtolower** (boolean) Put string to lower case or not. Default: true;

##### Simple code example:
```php
include 'bloom.class.php';

$parameters = array(
  'entries_max' => 2
);
$bloom = new Bloom($parameters);

/**
* Set value to Bloom filter
* You can put single string or array of infinite deep of strings
*
* @param mixed
* @return BloomObject
*/
$bloom->set('Some string');
echo $bloom->has('Some string'); //true
echo $bloom->has('Some string', false); //0

/**
* Test set with given array or string, to check it existance
* You can put single string or array of infinite deep of strings
*
* @param mixed (array) or (string)
* @param boolean return boolean or float number of tests
* @return mixed (array) or (boolean) or (float)
*/
echo $bloom->has('Other string'); //false

/**
* Unset value from Bloom filter
* You can put single string or array of infinite deep of strings
* Only works with counter parameter
* @param mixed
* @return mixed (boolean) or (array)
*/
$bloom->delete('Some string');
echo $bloom->has('Some string'); //false
```

##### Caching

You can also cache the whole object, just using serialize() and unserialize() functions

##### When to use
* Get/Set operations are more than 0.001
* You need a fast check with big set, more than 100 000 entries

##### Use cases:
* to cooldown HDD usage
* to speedup checking of object existence (with counter 40% slower, but still more faster than native)
* to save memory

![alt text](https://p1.assorium.ru/?cid=9bb2778b5bd875e57c2b8bd46c3b2f5c "Logo Title Text 1")
