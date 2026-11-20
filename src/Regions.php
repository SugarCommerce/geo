<?php
/**
 * SugarCommerce Geo
 *
 * The states, provinces and other first-level subdivisions of a country,
 * keyed by country code.
 *
 * The table itself is data/regions.php, derived from WooCommerce's
 * i18n/states.php and GPL-3.0-or-later; its own comment says how the codes
 * are kept and why.
 *
 * @package     SugarCommerce\Geo
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL-3.0-or-later
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Geo;

use SugarCommerce\Geo\Utils\Data;

/**
 * Regions class
 *
 * Provides static methods for working with the states, provinces and other
 * first-level subdivisions of a country.
 *
 * @since 1.0.0
 */
class Regions {

	/**
	 * Get every country's regions.
	 *
	 * @return array Array of country code => array of region code => region name pairs.
	 */
	public static function all(): array {
		return self::regions();
	}

	/**
	 * Get the country codes that have regions.
	 *
	 * @return array Array of country codes.
	 */
	public static function countries(): array {
		return array_keys( self::regions() );
	}

	/**
	 * Get the regions of a country.
	 *
	 * @param string $country Country code (case-insensitive).
	 *
	 * @return array Array of region code => region name pairs, empty if the country has none.
	 */
	public static function for( string $country ): array {
		$country = Countries::sanitize( $country );

		if ( null === $country ) {
			return [];
		}

		return self::regions()[ $country ] ?? [];
	}

	/**
	 * Check if a country has regions.
	 *
	 * @param string $country Country code (case-insensitive).
	 *
	 * @return bool True if the country has at least one region.
	 */
	public static function has( string $country ): bool {
		return [] !== self::for( $country );
	}

	/**
	 * Get region name by code.
	 *
	 * @param string $country Country code (case-insensitive).
	 * @param string $region  Region code (case-insensitive).
	 *
	 * @return string Region name or empty string if not found.
	 */
	public static function get_name( string $country, string $region ): string {
		$code = self::sanitize( $country, $region );

		if ( null === $code ) {
			return '';
		}

		return self::for( $country )[ $code ];
	}

	/**
	 * Check if a region code exists for a country.
	 *
	 * @param string $country Country code (case-insensitive).
	 * @param string $region  Region code (case-insensitive).
	 *
	 * @return bool True if the country has that region.
	 */
	public static function exists( string $country, string $region ): bool {
		return null !== self::sanitize( $country, $region );
	}

	/**
	 * Validate and sanitize a region code.
	 *
	 * Matched whatever case it arrives in, and returned as the table has it:
	 * upper-cased for all but Morocco, whose codes WooCommerce keeps in lower
	 * case, so that what goes into storage is what other stores already hold.
	 *
	 * @param string $country Country code (case-insensitive).
	 * @param string $region  Region code to validate.
	 *
	 * @return string|null Sanitized region code or null if invalid.
	 */
	public static function sanitize( string $country, string $region ): ?string {
		$regions = self::for( $country );
		$region  = trim( $region );

		if ( [] === $regions || '' === $region ) {
			return null;
		}

		$upper = strtoupper( $region );

		// Nearly every code is upper case, so this is the whole lookup for
		// all but a handful of countries.
		if ( isset( $regions[ $upper ] ) ) {
			return $upper;
		}

		foreach ( array_keys( $regions ) as $code ) {
			// Cast, because PHP has already turned a numeric code such as
			// the Minor Outlying Islands' '81' into an integer key.
			if ( strtoupper( (string) $code ) === $upper ) {
				return (string) $code;
			}
		}

		return null;
	}

	/**
	 * Subdivision codes and names, by country.
	 *
	 * @return array<string, array<array-key, string>> From data/regions.php.
	 */
	private static function regions(): array {
		return Data::get( 'regions' );
	}
}
