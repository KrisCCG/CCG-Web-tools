<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'WPCACHEHOME', '/home/clients/bb59b9bb68f9d5f0bbd421f23f3ae8ed/sites/clickclick.be/wp-content/plugins/wp-super-cache/' );
define('WP_CACHE', true);
define('DB_NAME', 'eu3mfw_WP1384048');

/** MySQL database username */
define('DB_USER', 'eu3mfw_WP1384048');

/** MySQL database password */
define('DB_PASSWORD', 'uvxUJ0Y7mM');

/** MySQL hostname */
define('DB_HOST', 'eu3mfw.myd.infomaniak.com');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'hkVezW~1/4}Md+&j6JGR7z!7<52KwX@Hs@PieqFA!C*?^Own4~`P3acpM,.qi7FX');
define('SECURE_AUTH_KEY',  'lite5Aj??qw,^r^tPY|dDQb|1-v3|>=jBxPCY&DLfx#Mh(nS!BJ~J@kF|N3|g~.I');
define('LOGGED_IN_KEY',    ')^Ya>`*4tSn+8Y{J4bXSefr(1-79jyh(0>#io#iCYAA0>|UrR`Z#eVE(TYwML@B>');
define('NONCE_KEY',        '3L>2Ya/8lL:yUek4a|T&HhNha|<8Dr@1~lw_pj*:*{rI!U}QWvSD86eSnfLLst6&');
define('AUTH_SALT',        'mo)j_UPZSpiN;2gNG|V8A-BY3qn_B!?KKCIS,91pbR@i)cS>9j+Zx/~zlvpz,ajh');
define('SECURE_AUTH_SALT', 'i#P~1ZIjt_CvkjjOUj}M|V_AIVI&Z;v#=bJ7HHeep>KP.R@M7_!v2!D:na9EQGx/');
define('LOGGED_IN_SALT',   'Xd:QcgJ0~M#<:vA~rX?V%4%dIs,J0yYvbz{hX!v20;rHP(5Enysi~g:layJW!|hu');
define('NONCE_SALT',       'RVKU@j2Ms@+i/&jB~:_j3>v1~:;Sdr5@cR9q00yvRuBZX<Dq;w:/hZAXcm%B^xF#');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_1384048_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
//define('WPLANG', 'en_US');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG',          false);
define('WP_DEBUG_LOG',      false);
define('WP_DEBUG_DISPLAY',  false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
