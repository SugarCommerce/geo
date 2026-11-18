<?php
/**
 * SugarCommerce Geo
 *
 * Country flags as pictures: the files in assets/flags, and where they are.
 *
 * The pictures are the circle-flags set by HatScripts, MIT licensed; the
 * licence and the version are beside them in assets/flags.
 *
 * @package     SugarCommerce\Geo
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-3.0-or-later
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Geo;

use SugarCommerce\Geo\Utils\Runtime;

/**
 * Flags class
 *
 * An emoji flag ({@see Countries::get_flag()}) is drawn by whatever the
 * reader's machine has: a different picture on a Mac, an Android phone and
 * a Windows desktop, where it is no picture at all, only the two letters.
 * These are files, one to a code, that look the same everywhere.
 *
 * They ship with the library and are served from wherever it was
 * installed, so nothing is fetched from anybody else and a shop's admin
 * tells no third party which countries it sells to.
 *
 * @since 1.0.0
 */
class Flags {

	/**
	 * Where the folder of flags is on the web, once it has been worked out.
	 *
	 * False for "not asked yet"; null for "asked, and it cannot be said".
	 *
	 * @var string|null|false
	 */
	private static $base = false;

	/**
	 * Which codes have a file, as they are asked about.
	 *
	 * A list of orders asks once a row, and the answer for a code does not
	 * change during a request.
	 *
	 * @var array<string, bool>
	 */
	private static array $found = [];

	/**
	 * A country code as a file is named: two lower-case letters, or nothing.
	 *
	 * Two letters and nothing else, which is also what keeps a code from
	 * being a path to somewhere other than the folder of flags.
	 *
	 * @param string $code The two-letter code, in either case.
	 *
	 * @return string Empty for anything else.
	 * @since 1.0.0
	 */
	public static function sanitize( string $code ): string {
		$code = strtolower( trim( $code ) );

		return 1 === preg_match( '/^[a-z]{2}\z/', $code ) ? $code : '';
	}

	/**
	 * The folder the flags are in, on disk.
	 *
	 * @return string With a trailing slash.
	 * @since 1.0.0
	 */
	public static function dir(): string {
		return dirname( __DIR__ ) . '/assets/flags/';
	}

	/**
	 * Where a country's flag is on disk.
	 *
	 * @param string $code The two-letter code, in either case.
	 *
	 * @return string The path, or nothing when there is no flag for the code.
	 * @since 1.0.0
	 */
	public static function path( string $code ): string {
		$code = self::sanitize( $code );

		return '' !== $code && self::has( $code ) ? self::dir() . $code . '.svg' : '';
	}

	/**
	 * Whether there is a flag for a code.
	 *
	 * @param string $code The two-letter code, in either case.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function exists( string $code ): bool {
		$code = self::sanitize( $code );

		return '' !== $code && self::has( $code );
	}

	/**
	 * Where a country's flag is, as a URL.
	 *
	 * @param string $code The two-letter code, in either case.
	 *
	 * @return string The URL, or nothing when there is no flag for the code
	 *                or the library is somewhere a URL cannot be worked out
	 *                for.
	 * @since 1.0.0
	 */
	public static function url( string $code ): string {
		$code = self::sanitize( $code );
		$base = '' !== $code && self::has( $code ) ? self::base_url() : '';

		return '' === $base ? '' : $base . $code . '.svg';
	}

	/**
	 * A country's flag, as an image.
	 *
	 * Decoration unless told otherwise: a flag is nearly always beside the
	 * country's name, so the alternative text is empty and a screen reader
	 * says "United Kingdom" once, not twice. The width and the height are
	 * stated so that the row is the right shape before the picture arrives
	 * and nothing moves when it does.
	 *
	 * @param string               $code The two-letter code, in either case.
	 * @param int                  $size How wide and tall, in pixels. The
	 *                                   flags are circles.
	 * @param array<string, mixed> $args {
	 *     Optional.
	 *
	 *     @type string $class The image's class. Default `country-flag`.
	 *     @type string $alt   Alternative text. Default empty.
	 *     @type string $url   A URL to use in place of the library's own,
	 *                         for a caller that has filtered it.
	 * }
	 *
	 * @return string The image, escaped, or nothing when there is no flag.
	 * @since 1.0.0
	 */
	public static function img( string $code, int $size = 16, array $args = [] ): string {
		$url = (string) ( $args['url'] ?? self::url( $code ) );

		if ( '' === $url ) {
			return '';
		}

		return sprintf(
			'<img class="%1$s" src="%2$s" width="%3$d" height="%3$d" alt="%4$s" loading="lazy" decoding="async" />',
			esc_attr( (string) ( $args['class'] ?? 'country-flag' ) ),
			esc_url( $url ),
			max( 1, $size ),
			esc_attr( (string) ( $args['alt'] ?? '' ) )
		);
	}

	/**
	 * Where the folder of flags is on the web.
	 *
	 * Worked out from where this file is, because the library has no home
	 * of its own: it is in a plugin's `vendor/`, or copied under another
	 * name by Strauss (`libraries/sugarcommerce/geo/`), or beside the
	 * plugins as a checkout. Wherever that is, it is under the content
	 * directory or the WordPress root on nearly every site, and a symlinked
	 * plugin is found through the map core keeps for plugins_url().
	 *
	 * @return string With a trailing slash. Empty when it cannot be said --
	 *                the library is outside the web root, say -- and the
	 *                filter gave nothing either.
	 * @since 1.0.0
	 */
	public static function base_url(): string {
		if ( false === self::$base ) {
			self::$base = self::dir_url( self::dir() );
		}

		/**
		 * Filters where the folder of country flags is on the web.
		 *
		 * The place to serve them from a CDN, or to say where they are when
		 * the library sits somewhere it could not be worked out. Named for
		 * this build: `sugarcommerce_geo_flags_url` unprefixed, and
		 * `{prefix}_sugarcommerce_geo_flags_url` in a plugin that bundles
		 * the library with Strauss, so one plugin's answer does not move
		 * another's flags. {@see Runtime::hook()}.
		 *
		 * @param string $url The folder's URL with a trailing slash, or ''.
		 * @param string $dir The folder on disk.
		 *
		 * @since 1.0.0
		 */
		$url = (string) apply_filters( Runtime::hook( 'flags_url' ), (string) self::$base, self::dir() );

		return '' === $url ? '' : rtrim( $url, '/' ) . '/';
	}

	/**
	 * The URL of a directory on disk.
	 *
	 * Public so that it can be tested with directories this file is not in.
	 * Nothing outside the library should need it.
	 *
	 * @param string $dir An absolute path.
	 *
	 * @return string|null With a trailing slash, or null when the directory
	 *                     is under nothing with a known URL.
	 * @since 1.0.0
	 */
	public static function dir_url( string $dir ): ?string {
		$dir = rtrim( wp_normalize_path( $dir ), '/' );

		$bases = [];

		if ( defined( 'WP_CONTENT_DIR' ) ) {
			$bases[] = [ WP_CONTENT_DIR, content_url() ];
		}

		$bases[] = [ ABSPATH, site_url( '/' ) ];

		foreach ( $bases as [ $base, $base_url ] ) {
			$base = rtrim( wp_normalize_path( (string) $base ), '/' );

			// A prefix, and a whole one: /srv/www is not the start of
			// /srv/www2/flags.
			if ( '' !== $base && str_starts_with( $dir . '/', $base . '/' ) ) {
				return rtrim( (string) $base_url, '/' ) . substr( $dir, strlen( $base ) ) . '/';
			}
		}

		// __DIR__ resolves symlinks, so a symlinked plugin's real path can
		// be anywhere on disk. Core maps each one back to its path under
		// the plugins directory.
		foreach ( (array) ( $GLOBALS['wp_plugin_paths'] ?? [] ) as $plugin_path => $real_path ) {
			$real_path = rtrim( wp_normalize_path( (string) $real_path ), '/' );
			$mapped    = rtrim( wp_normalize_path( (string) $plugin_path ), '/' ) . substr( $dir, strlen( $real_path ) );

			if ( '' !== $real_path && str_starts_with( $dir . '/', $real_path . '/' ) && $mapped !== $dir ) {
				return self::dir_url( $mapped );
			}
		}

		return null;
	}

	/**
	 * Forget what has been worked out.
	 *
	 * For a test, or for code that moves the content directory mid-request.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public static function flush(): void {
		self::$base  = false;
		self::$found = [];
	}

	/**
	 * Whether a clean code has a file.
	 *
	 * @param string $code Two lower-case letters.
	 *
	 * @return bool
	 */
	private static function has( string $code ): bool {
		return self::$found[ $code ] ??= is_file( self::dir() . $code . '.svg' );
	}
}
