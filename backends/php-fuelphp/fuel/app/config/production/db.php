<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2019 Fuel Development Team
 * @link       https://fuelphp.com
 */

/**
 * -----------------------------------------------------------------------------
 *  Database settings for production environment
 * -----------------------------------------------------------------------------
 *
 *  These settings get merged with the global settings.
 *
 */

return array(
	'default' => array(
		'connection' => array(
			'dsn'      => 'mysql:host=' . getenv('DB_HOST') . ';dbname=' . (getenv('DB_NAME') ?: 'fuel_prod'),
			'username' => getenv('DB_USERNAME') ?: 'fuel_app',
			'password' => getenv('DB_PASSWORD') ?: '',
		),
	),
);
