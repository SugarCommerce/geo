<?php
/**
 * SugarCommerce Geo
 *
 * The library's tables, read from data/ once a request.
 *
 * @package     SugarCommerce\Geo
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-3.0-or-later
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Geo\Utils;

use InvalidArgumentException;

/**
 * Data class
 *
 * The country list, the continents, the regions and the address formats
 * are thousands of lines of data and no code, so they live in data/ as PHP
 * files that return an array, and the classes that answer questions about
 * them stay short enough to read. A file is read the first time it is
 * asked for and kept for the rest of the request; OPcache keeps the array
 * itself between requests, as it would a constant.
 *
 * @internal
 * @since 1.0.0
 */
final class Data {

	/**
	 * The tables read so far, by name.
	 *
	 * @var array<string, array<array-key, mixed>>
	 */
	private static array $tables = [];

	/**
	 * A table, by the name of its file in data/.
	 *
	 * @param string $name The file's name without `.php`: lower-case letters
	 *                     and hyphens, so a name is never a path.
	 *
	 * @return array<array-key, mixed>
	 * @throws InvalidArgumentException For a name that is not a table's.
	 * @since 1.0.0
	 */
	public static function get( string $name ): array {
		return self::$tables[ $name ] ??= self::read( $name );
	}

	/**
	 * Read a table's file.
	 *
	 * @param string $name The file's name without `.php`.
	 *
	 * @return array<array-key, mixed>
	 * @throws InvalidArgumentException For a name that is not a table's.
	 */
	private static function read( string $name ): array {
		$file = dirname( __DIR__, 2 ) . '/data/' . $name . '.php';

		if ( 1 !== preg_match( '/^[a-z]+(?:-[a-z]+)*\z/', $name ) || ! is_file( $file ) ) {
			throw new InvalidArgumentException( 'No such table: ' . $name );
		}

		$table = require $file;

		return is_array( $table ) ? $table : [];
	}
}
