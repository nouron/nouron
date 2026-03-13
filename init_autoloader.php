<?php
/**
 * Laminas Framework autoloader setup.
 * Uses Composer autoloading exclusively.
 */

if (file_exists('vendor/autoload.php')) {
    include 'vendor/autoload.php';
} else {
    throw new RuntimeException(
        'Unable to load application. Run `composer install` first.'
    );
}
