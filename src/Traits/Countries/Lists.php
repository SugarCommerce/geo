<?php
/**
 * SugarCommerce Geo
 *
 * The groups of countries a shop asks about: the EU, the higher-risk list and
 * the sanctioned list.
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
 * Countries' membership lists, each a constant and a pair of methods: is
 * this one on it, and which are.
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
	 * Territories with elevated payment-fraud rates.
	 *
	 * A signal to weigh, never a reason to block on its own -- plenty of
	 * genuine customers live in every one of them. Not exhaustive, and worth
	 * tuning per business.
	 */
	private const array HIGH_RISK_COUNTRIES = [
		'BY',
		'BD',
		'BR',
		'CN',
		'CO',
		'EG',
		'GH',
		'ID',
		'IN',
		'MX',
		'MY',
		'NG',
		'PH',
		'PK',
		'RO',
		'RU',
		'TH',
		'UA',
		'VE',
		'VN',
	];

	/**
	 * Territories under comprehensive sanctions (US Treasury OFAC).
	 *
	 * Indicative only. Sanctions change without notice and vary by
	 * jurisdiction; a business relying on this for compliance verifies it
	 * against its own legal advice rather than a constant in a library. A
	 * coarse first gate for "should this checkout go ahead at all", not a
	 * screening product: it knows nothing of names, sectors or entities.
	 */
	private const array SANCTIONED_COUNTRIES = [
		'CU',
		'IR',
		'KP',
		'RU',
		'SY',
		'VE',
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

	/**
	 * Whether a country has elevated payment-fraud rates.
	 *
	 * One signal among several. Blocking on this alone refuses a lot of
	 * genuine customers.
	 *
	 * @param string $code Country code, in either case.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function is_high_risk( string $code ): bool {
		return in_array( self::normalize( $code ), self::HIGH_RISK_COUNTRIES, true );
	}

	/**
	 * The countries with elevated payment-fraud rates.
	 *
	 * @return array<string, string> Code => name, in order of name.
	 * @since 1.0.0
	 */
	public static function get_high_risk_countries(): array {
		return self::pick( self::HIGH_RISK_COUNTRIES );
	}

	/**
	 * Whether a country is under comprehensive sanctions.
	 *
	 * Indicative only -- see the list's own comment.
	 *
	 * @param string $code Country code, in either case.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function is_sanctioned( string $code ): bool {
		return in_array( self::normalize( $code ), self::SANCTIONED_COUNTRIES, true );
	}

	/**
	 * The countries under comprehensive sanctions.
	 *
	 * @return array<string, string> Code => name, in order of name.
	 * @since 1.0.0
	 */
	public static function get_sanctioned_countries(): array {
		return self::pick( self::SANCTIONED_COUNTRIES );
	}
}
