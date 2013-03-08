<?php

if (class_exists('\Nohex\\Eix\\Core\\ClassLoader')) {
	// Classes will be loaded from the main/ folder.
	\Nohex\Eix\Core\ClassLoader::addClassPath(__DIR__ . '/main/');
	// If available, classes will be also loaded from the lib/ folder.
	\Nohex\Eix\Core\ClassLoader::addClassPath(__DIR__ . '/lib/', false);
	// If available, classes will be also loaded from the test/ folder.
	\Nohex\Eix\Core\ClassLoader::addClassPath(__DIR__ . '/test/', false);
} else {
    // Eix should already be in the context.
    throw new \RuntimeException('Eix not present.');
}