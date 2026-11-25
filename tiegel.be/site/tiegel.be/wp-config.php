<?php
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
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'tiegel_local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost:/Users/clickclick/.tiegel-dev/mysql.sock' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', 'utf8mb4_unicode_ci' );

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
define( 'AUTH_KEY',          'l7lrJvAgSB0_0iT^/V80K+*r01<W)YJr%zKBnaKXvI X6:`95uS_Z;rhT68f;j]d' );
define( 'SECURE_AUTH_KEY',   '{lpN;~eC<V3#>L/KmV-=BQRIN!^gK[UGMijTCC_ej[=F/:PQSt7K7@D1c=NNf*ew' );
define( 'LOGGED_IN_KEY',     '^asni_tC%DE(osXD]lG91FIs]IvL}oImR@u=h<^)RD<All`)UyilarArd{M*K_qi' );
define( 'NONCE_KEY',         'G4?a;p0`aYUE7L>Wq*eaDt%#n>Zv*_U^@|0+9}+Do,~vgKO}&9u|:dWd|^T$SnPP' );
define( 'AUTH_SALT',         'S<p4&*O_cGR<o4.cW%D|4yJEH,ughQtd4-D>5ZA24g4f;|W_*tCSG|4!bw.uQ* g' );
define( 'SECURE_AUTH_SALT',  '3Q[/Nzo;zKZ> t6L~SCU 4k+jEs79`nf3|oEO{58#jpl_3hXAM|%e<|Yj39Gsq^T' );
define( 'LOGGED_IN_SALT',    'KO|Xc>aIcjU:&x*]+%Sc`jie*s.[Mm>s>/bv*#w>Ddj!J$z}To7R)hA|A~Vj0n<+' );
define( 'NONCE_SALT',        'Cp?ocU}6-4zS3Ae_7A5%$^K1|hNIABqYN%cZ8D`t<tdMQ?kD!8_Ts5BdP#=]VQoB' );
define( 'WP_CACHE_KEY_SALT', 'MryO58>ZPYzo%#(]Th2]>`A;KY5E4&|~eml#2SJ#$u&&iKQA:aAW>k/$7&8 trj#' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_1384671_';


/* Add any custom values between this line and the "stop editing" line. */



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
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', true );
}

define( 'WP_HOME', 'http://localhost:8080' );
define( 'WP_SITEURL', 'http://localhost:8080' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
