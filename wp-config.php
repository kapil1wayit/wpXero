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
define('DB_NAME', 'wpdemo');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'admin786');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

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
define('AUTH_KEY',         'DZD,QO$6Jx3%%g4J{65JPvB3vmsD.rs|ss,V5m,VoKgCs57N<7F0F?>n_S4Oe63 ');
define('SECURE_AUTH_KEY',  'BcUTdKb)DBe[XgV{~$-N/A/JrNk|D!pWV^R#^rb|u1a#Ypy$7`ZApZ%AwTWhzNTA');
define('LOGGED_IN_KEY',    'Qd,<1qi1wV66w{F!H+6Qr`dm>^dLEK4 Dj<#<kP,~dXD283BqC~lSu2=(PiD|Y8k');
define('NONCE_KEY',        'Eo{ 0UN@|MG9&3kY7_hIOh)&!6,xT9mSNOvrsxa70BX4B`,4j<i-7}}ed>ekgX.1');
define('AUTH_SALT',        'i#*#;v8$1{V>{$LwPO.CeAa6an~8Bo2qGXd]yeH}~-|BkXN0VzI;+`PcoL[&e3E#');
define('SECURE_AUTH_SALT', 'xMrK?0.C{eUnc=T+Pw~{K(s{Vh1OpKVywi%AppVx.!p#(G?FC,fR{S;aS$*xij5F');
define('LOGGED_IN_SALT',   'I/%wq4nBt$:+XKN*B$,4Q>Q1z*1/5p`A>n0yY5Z^z3:/~#KEp}AzG(P&d~:tAW?P');
define('NONCE_SALT',       'QyTJ5+1z9x>3A~g:Dj%9+cnAe o+v_JSKp[);BalUz4^oT7En)MANpXN8BPFx7D+');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

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
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
