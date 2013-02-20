<?
function loader($class)
{
	if( $class == 'Bloom' )
		$file = '../bloom.class.php';
	else
    $file = $class . '.php';
	if (file_exists($file)) {
			require $file;
	}
}

spl_autoload_register('loader');