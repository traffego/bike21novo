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
define( 'DB_NAME', 'u540810116_base_store' );

/** Database username */
define( 'DB_USER', 'u540810116_base_store' );

/** Database password */
define( 'DB_PASSWORD', 'Traffego444#' );

/** Database hostname */
define( 'DB_HOST', ( isset( $_SERVER['HTTP_HOST'] ) && strpos( $_SERVER['HTTP_HOST'], 'bike21novo.local' ) !== false ) ? '31.97.208.47' : 'localhost' );

/** Otimizações de Performance Local */
define( 'WP_MEMORY_LIMIT', '512M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );
define( 'DISABLE_WP_CRON', true );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          '@61UB`O/HXypIgYYm1CU[`keDEu$XDwL-Uz75{9u?:[gPeY>vr{:uqA<tFg#7UIY' );
define( 'SECURE_AUTH_KEY',   'cFBSEeta$`$=C5-MexeT[-gBZEr}FBmW{. PCIqNm84Ua@ZD:,+`AI+p~(zI5Lu?' );
define( 'LOGGED_IN_KEY',     'GyIIwL3oT)Culz%VESKDqyf?*%&mj$M`(i?,Y5:,C`dB0l`=mCxFX97!j|y5]`C&' );
define( 'NONCE_KEY',         'p-)a([AOmqDRi N[qN<2CLfD9T3WlFirBdK1>R/)}~`.C<x1l<nw=T_h`V)o`R`[' );
define( 'AUTH_SALT',         ',Pw.cu7L%NwA0u<K X]VLOV.}[NAb5ZN!J9TSdM.v%QmbE|P]24a1>qp97;!qXg`' );
define( 'SECURE_AUTH_SALT',  'Xq++=9<Prn7{-xx@J5+~gqrFy64Y2V=8<pN{A#$^MbV,VfT*M.JHDM}~ld(+ojcP' );
define( 'LOGGED_IN_SALT',    'r!-+W<u47>NZyCm4:c=`mwF( z-=k4K+#mJmgl?nmh/]xhNxHELm;%4-;`o<qEMU' );
define( 'NONCE_SALT',        'XH9#mmx9rowP?wK/*a T}A[gRpke=*%2)OOTybI1gN<Li^CbL$/pm~bXhDq[ aP4' );
define( 'WP_CACHE_KEY_SALT', 'IK+a d@Z-116S4k[CPUHA2<g]}7s,2AfKC#l#^X~YFXBR|r}z-rtDq2?(_K2kdzR' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


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
	define( 'WP_DEBUG', false );
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
