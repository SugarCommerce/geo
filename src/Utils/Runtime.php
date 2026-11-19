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

use SugarCommerce\Plugin\Build;

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
	 * How many segments this class's own namespace has --
	 * SugarCommerce\Geo\Utils -- before any prefix. Counted rather than
	 * written out: Strauss rewrites a namespace it finds in a string, and
	 * would prefix the very thing being compared against.
	 */
	private const OWN_SEGMENTS = 3;

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
		$build = self::build_for( $under );

		return '' === $build ? self::LIBRARY : str_replace( '-', '_', $build ) . '_' . self::LIBRARY;
	}

	/**
	 * The name of the build a namespace belongs to, as a hyphenated slug.
	 *
	 * Empty when the namespace is this package's own, unprefixed. Otherwise
	 * what the build put around it, in one of two shapes:
	 *
	 * - A prefix in front of the whole namespace,
	 *   "MyShop\SugarCommerce\…\Utils": the prefix's first segment, as it
	 *   always was ("SugarCart\Vendor" is "sugarcart"), or all of it when it
	 *   starts "SugarCommerce" too -- a family plugin's
	 *   "SugarCommerce\Playground\Vendor" would otherwise be taken for the
	 *   library itself.
	 * - A plugin's own namespace with the vendor segment dropped,
	 *   "ArrayPress\EDD\FraudFilter\Lib\…\Utils": every segment before "Lib".
	 *   The first alone is the vendor every such plugin shares, and two of
	 *   them would answer each other's routes and load each other's scripts.
	 *
	 * @param string $under The namespace this class is compiled under, prefixed or not.
	 *
	 * @return string
	 */
	public static function build_for( string $under ): string {
		return Build::name( $under, self::OWN_SEGMENTS );
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
