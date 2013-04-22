<?php
/**
 * This is Eix's standard bootstrap, which adds the main/, lib/ and test/
 * folders of the current library/phar/application into the include path, and
 * makes sure the class loader is initialised.
 */

// Get the class loader.
$classLoader = null;
if (!class_exists('\\Nohex\\Eix\\Core\\ClassLoader')) {
    require_once __DIR__ . '/lib/nohex-eix-0.2.0.phar';
}
$classLoader = \Nohex\Eix\Core\ClassLoader::getInstance();
// Add the main source folder to the include path.
$classLoader->addPath(__DIR__ . '/main');
// Add the libraries folder to the include path.
$classLoader->addPath(__DIR__ . '/lib');
// Add the test source folder to the include path.
$classLoader->addPath(__DIR__ . '/test');
// Initialise Eix's class loader.
$classLoader->init();

// Bring up test dependencies.
require_once 'nohex-eix-0.2.0-devel.phar';
