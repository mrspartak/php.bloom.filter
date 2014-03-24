<?
function loader($class)
{
	if( $class == 'Bloom' )
		$file = dirname(__FILE__) . '/../bloom.class.php';
	else
    $file = $class . '.php';
	if (file_exists($file)) {
			require $file;
	}
}

spl_autoload_register('loader');