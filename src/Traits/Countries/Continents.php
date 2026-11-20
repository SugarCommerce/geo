<?php
/**
 * SugarCommerce Geo
 *
 * The continents, and which one each country is in.
 *
 * @package     SugarCommerce\Geo
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-3.0-or-later
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Geo\Traits\Countries;

use SugarCommerce\Geo\Utils\Data;

/**
 * Continents trait
 *
 * Countries' continent questions: the codes geo-IP services give, the
 * continent a country is in, and the countries of a continent.
 *
 * Reads Countries' own countries() and normalize().
 *
 * @since 1.0.0
 */
trait Continents {

	/**
	 * Continent codes, as geo-IP services give them (IPInfo, MaxMind).
	 */
	private const CONTINENT_CODES = [
		'AF' => 'Africa',
		'AN' => 'Antarctica',
		'AS' => 'Asia',
		'EU' => 'Europe',
		'NA' => 'North America',
		'OC' => 'Oceania',
		'SA' => 'South America',
	];

	/**
	 * Every continent code.
	 *
	 * @return array<string, string> Continent code => name.
	 * @since 1.0.0
	 */
	public static function get_continent_codes(): array {
		return self::CONTINENT_CODES;
	}

	/**
	 * A continent's code from its name.
	 *
	 * @param string $name Continent name, as get_continent_codes() has it.
	 *
	 * @return string|null The code, or null when the name is not a continent.
	 * @since 1.0.0
	 */
	public static function get_continent_code( string $name ): ?string {
		$code = array_search( $name, self::CONTINENT_CODES, true );

		return false === $code ? null : $code;
	}

	/**
	 * A continent's name from its code.
	 *
	 * @param string $code Continent code (e.g. 'EU', 'NA'), in either case.
	 *
	 * @return string|null The name, or null when the code is not a continent.
	 * @since 1.0.0
	 */
	public static function get_continent_name( string $code ): ?string {
		return self::CONTINENT_CODES[ self::normalize( $code ) ] ?? null;
	}

	/**
	 * The continent a country is in.
	 *
	 * @param string $code Country code, in either case.
	 *
	 * @return string|null The continent's name, or null for a code that is
	 *                     not a country.
	 * @since 1.0.0
	 */
	public static function get_continent( string $code ): ?string {
		return self::continents()[ self::normalize( $code ) ] ?? null;
	}

	/**
	 * The code of the continent a country is in.
	 *
	 * @param string $code Country code, in either case.
	 *
	 * @return string|null The continent code, or null for a code that is not
	 *                     a country.
	 * @since 1.0.0
	 */
	public static function get_country_continent_code( string $code ): ?string {
		$continent = self::get_continent( $code );

		return null === $continent ? null : self::get_continent_code( $continent );
	}

	/**
	 * Whether a country is in a continent.
	 *
	 * @param string $code      Country code, in either case.
	 * @param string $continent Continent name or code.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function is_continent( string $code, string $continent ): bool {
		$continent_name = self::CONTINENT_CODES[ self::normalize( $continent ) ] ?? $continent;

		return self::get_continent( $code ) === $continent_name;
	}

	/**
	 * The countries of a continent.
	 *
	 * @param string $continent Continent name or code.
	 *
	 * @return array<string, string> Code => name, in order of name.
	 * @since 1.0.0
	 */
	public static function get_by_continent( string $continent ): array {
		$continent_name = self::CONTINENT_CODES[ self::normalize( $continent ) ] ?? $continent;

		return array_intersect_key( self::countries(), array_filter( self::continents(), static fn( string $name ): bool => $name === $continent_name ) );
	}

	/**
	 * The continent each country is in.
	 *
	 * @return array<string, string> Code => continent name, from data/continents.php.
	 */
	private static function continents(): array {
		return Data::get( 'continents' );
	}
}
