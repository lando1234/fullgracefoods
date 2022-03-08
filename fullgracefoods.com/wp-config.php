<?php
define('WP_CACHE', true);
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
define( 'DB_NAME', 'laparroq_wp3' );

/** MySQL database username */
define( 'DB_USER', 'laparroq_wp3' );

/** MySQL database password */
define( 'DB_PASSWORD', 'M.aGxxGjQQ1damES0bk41' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'FdLlPrIQ72DPnF5wBObM0Hv5JmvmhVVLh3umvC7Ls9njigJjjrX3wEpKxJyPA7Qx');
define('SECURE_AUTH_KEY',  '0aP7Llkz6GC1FFhKEzsn3Tp6FJK4MvdSlO2EW9YeXVD57Bsyy3No9HzrBaPkisom');
define('LOGGED_IN_KEY',    'hcU5JajctBvY7uLQmgKVe3wLqqJH0Lxxff58ocJ7O28L2ixEWpk6UTtzvWnEMhyX');
define('NONCE_KEY',        'Ri9xvfzbTYVSA7mMTaC6JHbTQdIQMrolp27sxLrnirU72hU4m04suR1G2BbzddQl');
define('AUTH_SALT',        'JjVImURS627s9s1VM86Vt5S3xnyzFW3SkffDn6eyyzNX0iwTMcvPyn0kj378Z5vz');
define('SECURE_AUTH_SALT', 'Hi1aJjCUvN3GxkourAgGRAJeNA1tzmyCSpEYhsqYvDBjRlpnsmBFZJ7qV5HOJTh1');
define('LOGGED_IN_SALT',   'P13t37wRdJ6Nu1YkqKIFnuzwOjeaZbT991WispFoFtjmL2qISki4kxmprDjNAju1');
define('NONCE_SALT',       'FhynDciQqW5AOswXGRzhbqKwO2Sd6sXt1IbhD6mUNtnp8e1CirUKbiea2wnac3L9');

/**
 * Other customizations.
 */
define('FS_METHOD','direct');
define('FS_CHMOD_DIR',0755);
define('FS_CHMOD_FILE',0644);
define('WP_TEMP_DIR',dirname(__FILE__).'/wp-content/uploads');

/**
 * Turn off automatic updates since these are managed externally by Installatron.
 * If you remove this define() to re-enable WordPress's automatic background updating
 * then it's advised to disable auto-updating in Installatron.
 */
define('AUTOMATIC_UPDATER_DISABLED', true);


/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
