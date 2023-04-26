<?php
define( 'WP_CACHE', true );

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'casejonline_wp653' );

/** Database username */
define( 'DB_USER', 'casejonline_wp653' );

/** Database password */
define( 'DB_PASSWORD', '.!tq0p3]@OAS68' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'mqizhjghbc45gmjss7wh04ztmjluxepchmfgqxqklweityuzws2dmh6trjiz6obo' );
define( 'SECURE_AUTH_KEY',  'ztayqjsyvotefkch2jncjtooyuvdrjxm4kzcm8nppxpgxo3bpkypcblls4iy3cuk' );
define( 'LOGGED_IN_KEY',    'krduz3kdliyghoquxianzziruamxdwn25xnasx9zrxtsnnbmionlxhhrntqjy5in' );
define( 'NONCE_KEY',        'jcdtzzy7a67te2omblw0nhhuvzzg7tinuxq91xarauckvoggjenefwkqvchkgax8' );
define( 'AUTH_SALT',        'lopqaqal7lzfo6xtfi9ebfgclr2ksdl62qpfx2yyjr3am9em4mb2mtpm4z3waqv6' );
define( 'SECURE_AUTH_SALT', '7i6gijgsb3cqimgovasb3ukykx81czhmqbxnu3f1dwcvayhgpbrza9vv2cgcrm9j' );
define( 'LOGGED_IN_SALT',   'e7bqouyfa16ya4tafzptze9knyxf6x3jijvjptvqeziwft0lvyczqv7r6sdxiabk' );
define( 'NONCE_SALT',       'aq1gbfi10wdbe5ms3zdglezsqnb36kwcqnafkwgfg40da7bnnxkb8cvfxhyrslj7' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wpju_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
