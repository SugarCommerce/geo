<?php
/**
 * PHPUnit bootstrap.
 *
 * @package SugarCommerce\Geo
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Core's HTML escaper, near enough.
	 *
	 * @param string $text Text.
	 *
	 * @return string
	 */
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

/*
 * A site for Flags to find itself in. ABSPATH is the package, and the
 * content directory is its assets folder: the flags are under the content
 * directory, the source is under the WordPress root and not the content
 * directory, and both are true wherever the package is checked out.
 */
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', dirname( __DIR__ ) . '/assets' );
}

/**
 * Registered filters.
 *
 * @var array<string, array<int, callable>>
 */
$GLOBALS['co_filters'] = array();

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Register a filter callback.
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback.
	 *
	 * @return bool
	 */
	function add_filter( string $hook, callable $callback ): bool {
		$GLOBALS['co_filters'][ $hook ][] = $callback;

		return true;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Run a value through the callbacks for a hook.
	 *
	 * @param string $hook    Hook name.
	 * @param mixed  $value   Value.
	 * @param mixed  ...$args Further arguments.
	 *
	 * @return mixed
	 */
	function apply_filters( string $hook, $value, ...$args ) {
		foreach ( $GLOBALS['co_filters'][ $hook ] ?? array() as $callback ) {
			$value = $callback( $value, ...$args );
		}

		return $value;
	}
}

if ( ! function_exists( 'content_url' ) ) {
	/**
	 * The content directory's URL.
	 *
	 * @return string
	 */
	function content_url(): string {
		return 'https://example.test/wp-content';
	}
}

if ( ! function_exists( 'site_url' ) ) {
	/**
	 * The site's URL.
	 *
	 * @param string $path A path under it.
	 *
	 * @return string
	 */
	function site_url( string $path = '' ): string {
		return 'https://example.test/core' . $path;
	}
}

if ( ! function_exists( 'wp_normalize_path' ) ) {
	/**
	 * Core's path normaliser, near enough.
	 *
	 * @param string $path A path.
	 *
	 * @return string
	 */
	function wp_normalize_path( string $path ): string {
		return (string) preg_replace( '#/+#', '/', str_replace( '\\', '/', $path ) );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * Core's attribute escaper, near enough.
	 *
	 * @param string $text Text.
	 *
	 * @return string
	 */
	function esc_attr( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	/**
	 * Core's URL escaper, near enough: nothing that is not http(s).
	 *
	 * @param string $url A URL.
	 *
	 * @return string
	 */
	function esc_url( string $url ): string {
		return 1 === preg_match( '#^https?://#', $url ) ? htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' ) : '';
	}
}

/**
 * Translations a test can install: text domain to original to translated.
 *
 * @var array<string, array<string, string>>
 */
$GLOBALS['co_translations'] = array();

if ( ! function_exists( '__' ) ) {
	/**
	 * Core's translator, reading the map above.
	 *
	 * @param string $text   Original.
	 * @param string $domain Text domain.
	 *
	 * @return string
	 */
	function __( string $text, string $domain = 'default' ): string {
		return $GLOBALS['co_translations'][ $domain ][ $text ] ?? $text;
	}
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
