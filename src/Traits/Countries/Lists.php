<?php
/**
 * SugarCommerce Geo
 *
 * The group of countries a shop asks about for VAT: the EU. The higher-risk
 * and sanctioned lists are fraud policy, and live in sugarcommerce/security
 * (CountryRisk).
 *
 * @package     SugarCommerce\Geo
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-3.0-or-later
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Geo\Traits\Countries;

/**
 * Lists trait
 *
 * Countries' membership list, a constant and a pair of methods: is this
 * one on it, and which are.
 *
 * Reads Countries' own normalize() and pick().
 *
 * @since 1.0.0
 */
trait Lists {

	/**
	 * European Union member states.
	 *
	 * The list that decides whether VAT rules apply, so it matters that GB
	 * is absent and that the outermost regions are handled deliberately.
	 */
	private const array EU_COUNTRIES = [
		'AT',
		'BE',
		'BG',
		'HR',
		'CY',
		'CZ',
		'DK',
		'EE',
		'FI',
		'FR',
		'DE',
		'GR',
		'HU',
		'IE',
		'IT',
		'LV',
		'LT',
		'LU',
		'MT',
		'NL',
		'PL',
		'PT',
		'RO',
		'SK',
		'SI',
		'ES',
		'SE',
	];

	/**
	 * Whether a country is in the European Union, for VAT.
	 *
	 * @param string $code Country code, in either case.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function is_eu( string $code ): bool {
		return in_array( self::normalize( $code ), self::EU_COUNTRIES, true );
	}

	/**
	 * The member states of the European Union.
	 *
	 * @return array<string, string> Code => name, in order of name.
	 * @since 1.0.0
	 */
	public static function get_eu_countries(): array {
		return self::pick( self::EU_COUNTRIES );
	}
}
