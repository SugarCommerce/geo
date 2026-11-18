<?php
/**
 * SugarCommerce Geo
 *
 * The names this library gives its hooks, derived from its own namespace.
 *
 * @package     SugarCommerce\Geo
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-3.0-or-later
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Geo\Utils;

/**
 * Runtime class
 *
 * Strauss rewrites class namespaces and leaves string literals alone. Two
 * plugins each bundling a prefixed copy of this library get distinct
 * classes, but a hook named in a string would be one hook for both: a
 * filter one plugin adds to serve its flags from a CDN would move the
 * other plugin's flags too, to a CDN that may not have them.
 *
 * The prefix comes from the one thing Strauss does rewrite, this file's
 * namespace. In a prefixed build `__NAMESPACE__` begins with the plugin's
 * prefix ("MyPlugin\SugarCommerce\Geo\Utils"), so every hook comes out
 * distinct with nothing to configure.
 *
 * @since 1.0.0
 */
final class Runtime {

	/**
	 * This library's own identifier, used when running unprefixed.
	 */
	private const LIBRARY = 'sugarcommerce_geo';

	/**
	 * The prefix for this build's hooks.
	 *
	 * `sugarcommerce_geo` for a plain Composer install, and
	 * `{prefix}_sugarcommerce_geo` for a prefixed build.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public static function prefix(): string {
		return self::prefix_for( __NAMESPACE__ );
	}

	/**
	 * The prefix a given namespace would produce.
	 *
	 * Split out so the rule can be tested: prefix() reads __NAMESPACE__,
	 * which cannot be changed at runtime, so a test reaches the prefixed
	 * case by asking the rule directly.
	 *
	 * @param string $under The namespace this class is compiled under, prefixed or not.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public static function prefix_for( string $under ): string {
		$root = explode( '\\', ltrim( $under, '\\' ) )[0];

		if ( '' === $root || 'SugarCommerce' === $root ) {
			return self::LIBRARY;
		}

		$slug = strtolower( trim( (string) preg_replace( '/[^A-Za-z0-9]+/', '_', $root ), '_' ) );

		return '' === $slug ? self::LIBRARY : $slug . '_' . self::LIBRARY;
	}

	/**
	 * A hook's full name in this build.
	 *
	 * @param string $name The hook's own name, such as `flags_url`.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public static function hook( string $name ): string {
		return self::prefix() . '_' . $name;
	}
}
