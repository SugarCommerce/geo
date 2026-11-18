<?php
/**
 * SugarCommerce Geo
 *
 * Countries: their codes and names, and the groups a shop asks about.
 *
 * @package     SugarCommerce\Geo
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-3.0-or-later
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Geo;

/**
 * Countries class
 *
 * ISO 3166-1 alpha-2 codes and their English names, and the questions a
 * shop keeps asking of them: is this in the EU for VAT, which continent is
 * it, is it on the list treated with more caution, is it sanctioned.
 *
 * Returns raw values, except render(), which is for HTML and says so.
 * Escaping is otherwise the caller's job, because this data reaches HTML,
 * JSON, CSV and PDF, and a library that pre-escapes for one of those
 * corrupts it for the other three.
 *
 * @since 1.0.0
 */
class Countries {

	/**
	 * ISO 3166-1 alpha-2 codes and names, in order of name.
	 *
	 * In order so that all() is ready for a dropdown as it is. Accented
	 * letters sort as their plain ones: Åland Islands comes after
	 * Afghanistan, not after Zimbabwe.
	 */
	private const COUNTRIES = [
		'AF' => 'Afghanistan',
		'AX' => 'Åland Islands',
		'AL' => 'Albania',
		'DZ' => 'Algeria',
		'AS' => 'American Samoa',
		'AD' => 'Andorra',
		'AO' => 'Angola',
		'AI' => 'Anguilla',
		'AQ' => 'Antarctica',
		'AG' => 'Antigua and Barbuda',
		'AR' => 'Argentina',
		'AM' => 'Armenia',
		'AW' => 'Aruba',
		'AU' => 'Australia',
		'AT' => 'Austria',
		'AZ' => 'Azerbaijan',
		'BS' => 'Bahamas',
		'BH' => 'Bahrain',
		'BD' => 'Bangladesh',
		'BB' => 'Barbados',
		'BY' => 'Belarus',
		'PW' => 'Belau',
		'BE' => 'Belgium',
		'BZ' => 'Belize',
		'BJ' => 'Benin',
		'BM' => 'Bermuda',
		'BT' => 'Bhutan',
		'BO' => 'Bolivia',
		'BQ' => 'Bonaire, Saint Eustatius and Saba',
		'BA' => 'Bosnia and Herzegovina',
		'BW' => 'Botswana',
		'BV' => 'Bouvet Island',
		'BR' => 'Brazil',
		'IO' => 'British Indian Ocean Territory',
		'BN' => 'Brunei',
		'BG' => 'Bulgaria',
		'BF' => 'Burkina Faso',
		'BI' => 'Burundi',
		'KH' => 'Cambodia',
		'CM' => 'Cameroon',
		'CA' => 'Canada',
		'CV' => 'Cape Verde',
		'KY' => 'Cayman Islands',
		'CF' => 'Central African Republic',
		'TD' => 'Chad',
		'CL' => 'Chile',
		'CN' => 'China',
		'CX' => 'Christmas Island',
		'CC' => 'Cocos (Keeling) Islands',
		'CO' => 'Colombia',
		'KM' => 'Comoros',
		'CG' => 'Congo (Brazzaville)',
		'CD' => 'Congo (Kinshasa)',
		'CK' => 'Cook Islands',
		'CR' => 'Costa Rica',
		'HR' => 'Croatia',
		'CU' => 'Cuba',
		'CW' => 'Curaçao',
		'CY' => 'Cyprus',
		'CZ' => 'Czech Republic',
		'DK' => 'Denmark',
		'DJ' => 'Djibouti',
		'DM' => 'Dominica',
		'DO' => 'Dominican Republic',
		'EC' => 'Ecuador',
		'EG' => 'Egypt',
		'SV' => 'El Salvador',
		'GQ' => 'Equatorial Guinea',
		'ER' => 'Eritrea',
		'EE' => 'Estonia',
		'SZ' => 'Eswatini',
		'ET' => 'Ethiopia',
		'FK' => 'Falkland Islands',
		'FO' => 'Faroe Islands',
		'FJ' => 'Fiji',
		'FI' => 'Finland',
		'FR' => 'France',
		'GF' => 'French Guiana',
		'PF' => 'French Polynesia',
		'TF' => 'French Southern Territories',
		'GA' => 'Gabon',
		'GM' => 'Gambia',
		'GE' => 'Georgia',
		'DE' => 'Germany',
		'GH' => 'Ghana',
		'GI' => 'Gibraltar',
		'GR' => 'Greece',
		'GL' => 'Greenland',
		'GD' => 'Grenada',
		'GP' => 'Guadeloupe',
		'GU' => 'Guam',
		'GT' => 'Guatemala',
		'GG' => 'Guernsey',
		'GN' => 'Guinea',
		'GW' => 'Guinea-Bissau',
		'GY' => 'Guyana',
		'HT' => 'Haiti',
		'HM' => 'Heard Island and McDonald Islands',
		'HN' => 'Honduras',
		'HK' => 'Hong Kong',
		'HU' => 'Hungary',
		'IS' => 'Iceland',
		'IN' => 'India',
		'ID' => 'Indonesia',
		'IR' => 'Iran',
		'IQ' => 'Iraq',
		'IE' => 'Ireland',
		'IM' => 'Isle of Man',
		'IL' => 'Israel',
		'IT' => 'Italy',
		'CI' => 'Ivory Coast',
		'JM' => 'Jamaica',
		'JP' => 'Japan',
		'JE' => 'Jersey',
		'JO' => 'Jordan',
		'KZ' => 'Kazakhstan',
		'KE' => 'Kenya',
		'KI' => 'Kiribati',
		'XK' => 'Kosovo',
		'KW' => 'Kuwait',
		'KG' => 'Kyrgyzstan',
		'LA' => 'Laos',
		'LV' => 'Latvia',
		'LB' => 'Lebanon',
		'LS' => 'Lesotho',
		'LR' => 'Liberia',
		'LY' => 'Libya',
		'LI' => 'Liechtenstein',
		'LT' => 'Lithuania',
		'LU' => 'Luxembourg',
		'MO' => 'Macao',
		'MG' => 'Madagascar',
		'MW' => 'Malawi',
		'MY' => 'Malaysia',
		'MV' => 'Maldives',
		'ML' => 'Mali',
		'MT' => 'Malta',
		'MH' => 'Marshall Islands',
		'MQ' => 'Martinique',
		'MR' => 'Mauritania',
		'MU' => 'Mauritius',
		'YT' => 'Mayotte',
		'MX' => 'Mexico',
		'FM' => 'Micronesia',
		'MD' => 'Moldova',
		'MC' => 'Monaco',
		'MN' => 'Mongolia',
		'ME' => 'Montenegro',
		'MS' => 'Montserrat',
		'MA' => 'Morocco',
		'MZ' => 'Mozambique',
		'MM' => 'Myanmar',
		'NA' => 'Namibia',
		'NR' => 'Nauru',
		'NP' => 'Nepal',
		'NL' => 'Netherlands',
		'NC' => 'New Caledonia',
		'NZ' => 'New Zealand',
		'NI' => 'Nicaragua',
		'NE' => 'Niger',
		'NG' => 'Nigeria',
		'NU' => 'Niue',
		'NF' => 'Norfolk Island',
		'KP' => 'North Korea',
		'MK' => 'North Macedonia',
		'MP' => 'Northern Mariana Islands',
		'NO' => 'Norway',
		'OM' => 'Oman',
		'PK' => 'Pakistan',
		'PS' => 'Palestinian Territory',
		'PA' => 'Panama',
		'PG' => 'Papua New Guinea',
		'PY' => 'Paraguay',
		'PE' => 'Peru',
		'PH' => 'Philippines',
		'PN' => 'Pitcairn',
		'PL' => 'Poland',
		'PT' => 'Portugal',
		'PR' => 'Puerto Rico',
		'QA' => 'Qatar',
		'RE' => 'Reunion',
		'RO' => 'Romania',
		'RU' => 'Russia',
		'RW' => 'Rwanda',
		'BL' => 'Saint Barthélemy',
		'SH' => 'Saint Helena',
		'KN' => 'Saint Kitts and Nevis',
		'LC' => 'Saint Lucia',
		'SX' => 'Saint Martin (Dutch part)',
		'MF' => 'Saint Martin (French part)',
		'PM' => 'Saint Pierre and Miquelon',
		'VC' => 'Saint Vincent and the Grenadines',
		'WS' => 'Samoa',
		'SM' => 'San Marino',
		'SA' => 'Saudi Arabia',
		'SN' => 'Senegal',
		'RS' => 'Serbia',
		'SC' => 'Seychelles',
		'SL' => 'Sierra Leone',
		'SG' => 'Singapore',
		'SK' => 'Slovakia',
		'SI' => 'Slovenia',
		'SB' => 'Solomon Islands',
		'SO' => 'Somalia',
		'ZA' => 'South Africa',
		'GS' => 'South Georgia/Sandwich Islands',
		'KR' => 'South Korea',
		'SS' => 'South Sudan',
		'ES' => 'Spain',
		'LK' => 'Sri Lanka',
		'SD' => 'Sudan',
		'SR' => 'Suriname',
		'SJ' => 'Svalbard and Jan Mayen',
		'SE' => 'Sweden',
		'CH' => 'Switzerland',
		'SY' => 'Syria',
		'ST' => 'São Tomé and Príncipe',
		'TW' => 'Taiwan',
		'TJ' => 'Tajikistan',
		'TZ' => 'Tanzania',
		'TH' => 'Thailand',
		'TL' => 'Timor-Leste',
		'TG' => 'Togo',
		'TK' => 'Tokelau',
		'TO' => 'Tonga',
		'TT' => 'Trinidad and Tobago',
		'TN' => 'Tunisia',
		'TR' => 'Turkey',
		'TM' => 'Turkmenistan',
		'TC' => 'Turks and Caicos Islands',
		'TV' => 'Tuvalu',
		'UG' => 'Uganda',
		'UA' => 'Ukraine',
		'AE' => 'United Arab Emirates',
		'GB' => 'United Kingdom',
		'US' => 'United States',
		'UM' => 'United States Minor Outlying Islands',
		'UY' => 'Uruguay',
		'UZ' => 'Uzbekistan',
		'VU' => 'Vanuatu',
		'VA' => 'Vatican',
		'VE' => 'Venezuela',
		'VN' => 'Vietnam',
		'VG' => 'Virgin Islands (British)',
		'VI' => 'Virgin Islands (US)',
		'WF' => 'Wallis and Futuna',
		'EH' => 'Western Sahara',
		'YE' => 'Yemen',
		'ZM' => 'Zambia',
		'ZW' => 'Zimbabwe',
	];

	/**
	 * Country to continent mapping.
	 */
	private const CONTINENTS = [
		// Africa
		'DZ' => 'Africa',
		'AO' => 'Africa',
		'BJ' => 'Africa',
		'BW' => 'Africa',
		'BF' => 'Africa',
		'BI' => 'Africa',
		'CV' => 'Africa',
		'CM' => 'Africa',
		'CF' => 'Africa',
		'TD' => 'Africa',
		'KM' => 'Africa',
		'CG' => 'Africa',
		'CD' => 'Africa',
		'CI' => 'Africa',
		'DJ' => 'Africa',
		'EG' => 'Africa',
		'GQ' => 'Africa',
		'ER' => 'Africa',
		'SZ' => 'Africa',
		'ET' => 'Africa',
		'GA' => 'Africa',
		'GM' => 'Africa',
		'GH' => 'Africa',
		'GN' => 'Africa',
		'GW' => 'Africa',
		'KE' => 'Africa',
		'LS' => 'Africa',
		'LR' => 'Africa',
		'LY' => 'Africa',
		'MG' => 'Africa',
		'MW' => 'Africa',
		'ML' => 'Africa',
		'MR' => 'Africa',
		'MU' => 'Africa',
		'YT' => 'Africa',
		'MA' => 'Africa',
		'MZ' => 'Africa',
		'NA' => 'Africa',
		'NE' => 'Africa',
		'NG' => 'Africa',
		'RE' => 'Africa',
		'RW' => 'Africa',
		'SH' => 'Africa',
		'ST' => 'Africa',
		'SN' => 'Africa',
		'SC' => 'Africa',
		'SL' => 'Africa',
		'SO' => 'Africa',
		'ZA' => 'Africa',
		'SS' => 'Africa',
		'SD' => 'Africa',
		'TZ' => 'Africa',
		'TG' => 'Africa',
		'TN' => 'Africa',
		'UG' => 'Africa',
		'EH' => 'Africa',
		'ZM' => 'Africa',
		'ZW' => 'Africa',

		// Asia
		'AF' => 'Asia',
		'AM' => 'Asia',
		'AZ' => 'Asia',
		'BH' => 'Asia',
		'BD' => 'Asia',
		'BT' => 'Asia',
		'BN' => 'Asia',
		'KH' => 'Asia',
		'CN' => 'Asia',
		'CY' => 'Asia',
		'GE' => 'Asia',
		'HK' => 'Asia',
		'IN' => 'Asia',
		'ID' => 'Asia',
		'IR' => 'Asia',
		'IQ' => 'Asia',
		'IL' => 'Asia',
		'JP' => 'Asia',
		'JO' => 'Asia',
		'KZ' => 'Asia',
		'KW' => 'Asia',
		'KG' => 'Asia',
		'LA' => 'Asia',
		'LB' => 'Asia',
		'MO' => 'Asia',
		'MY' => 'Asia',
		'MV' => 'Asia',
		'IO' => 'Asia',
		'MN' => 'Asia',
		'MM' => 'Asia',
		'NP' => 'Asia',
		'KP' => 'Asia',
		'OM' => 'Asia',
		'PK' => 'Asia',
		'PS' => 'Asia',
		'PH' => 'Asia',
		'QA' => 'Asia',
		'SA' => 'Asia',
		'SG' => 'Asia',
		'KR' => 'Asia',
		'LK' => 'Asia',
		'SY' => 'Asia',
		'TW' => 'Asia',
		'TJ' => 'Asia',
		'TH' => 'Asia',
		'TL' => 'Asia',
		'TR' => 'Asia',
		'TM' => 'Asia',
		'AE' => 'Asia',
		'UZ' => 'Asia',
		'VN' => 'Asia',
		'YE' => 'Asia',

		// Europe
		'AX' => 'Europe',
		'AL' => 'Europe',
		'AD' => 'Europe',
		'AT' => 'Europe',
		'BY' => 'Europe',
		'BE' => 'Europe',
		'BA' => 'Europe',
		'BG' => 'Europe',
		'HR' => 'Europe',
		'CZ' => 'Europe',
		'DK' => 'Europe',
		'EE' => 'Europe',
		'FO' => 'Europe',
		'FI' => 'Europe',
		'FR' => 'Europe',
		'DE' => 'Europe',
		'GI' => 'Europe',
		'GR' => 'Europe',
		'GG' => 'Europe',
		'HU' => 'Europe',
		'IS' => 'Europe',
		'IE' => 'Europe',
		'IM' => 'Europe',
		'IT' => 'Europe',
		'JE' => 'Europe',
		'XK' => 'Europe',
		'LV' => 'Europe',
		'LI' => 'Europe',
		'LT' => 'Europe',
		'LU' => 'Europe',
		'MT' => 'Europe',
		'MD' => 'Europe',
		'MC' => 'Europe',
		'ME' => 'Europe',
		'NL' => 'Europe',
		'MK' => 'Europe',
		'NO' => 'Europe',
		'PL' => 'Europe',
		'PT' => 'Europe',
		'RO' => 'Europe',
		'RU' => 'Europe',
		'SM' => 'Europe',
		'RS' => 'Europe',
		'SK' => 'Europe',
		'SI' => 'Europe',
		'ES' => 'Europe',
		'SJ' => 'Europe',
		'SE' => 'Europe',
		'CH' => 'Europe',
		'UA' => 'Europe',
		'GB' => 'Europe',
		'VA' => 'Europe',

		// North America
		'AI' => 'North America',
		'AG' => 'North America',
		'AW' => 'North America',
		'BS' => 'North America',
		'BB' => 'North America',
		'BZ' => 'North America',
		'BM' => 'North America',
		'BQ' => 'North America',
		'VG' => 'North America',
		'CA' => 'North America',
		'KY' => 'North America',
		'CR' => 'North America',
		'CU' => 'North America',
		'CW' => 'North America',
		'DM' => 'North America',
		'DO' => 'North America',
		'SV' => 'North America',
		'GL' => 'North America',
		'GD' => 'North America',
		'GP' => 'North America',
		'GT' => 'North America',
		'HT' => 'North America',
		'HN' => 'North America',
		'JM' => 'North America',
		'MQ' => 'North America',
		'MX' => 'North America',
		'MS' => 'North America',
		'NI' => 'North America',
		'PA' => 'North America',
		'PR' => 'North America',
		'BL' => 'North America',
		'KN' => 'North America',
		'LC' => 'North America',
		'MF' => 'North America',
		'PM' => 'North America',
		'VC' => 'North America',
		'SX' => 'North America',
		'TT' => 'North America',
		'TC' => 'North America',
		'US' => 'North America',
		'VI' => 'North America',

		// South America
		'AR' => 'South America',
		'BO' => 'South America',
		'BR' => 'South America',
		'CL' => 'South America',
		'CO' => 'South America',
		'EC' => 'South America',
		'FK' => 'South America',
		'GF' => 'South America',
		'GY' => 'South America',
		'PY' => 'South America',
		'PE' => 'South America',
		'SR' => 'South America',
		'UY' => 'South America',
		'VE' => 'South America',

		// Oceania
		'AS' => 'Oceania',
		'AU' => 'Oceania',
		'CK' => 'Oceania',
		'FJ' => 'Oceania',
		'PF' => 'Oceania',
		'GU' => 'Oceania',
		'KI' => 'Oceania',
		'MH' => 'Oceania',
		'FM' => 'Oceania',
		'NR' => 'Oceania',
		'NC' => 'Oceania',
		'NZ' => 'Oceania',
		'NU' => 'Oceania',
		'NF' => 'Oceania',
		'CX' => 'Oceania',
		'CC' => 'Oceania',
		'UM' => 'Oceania',
		'MP' => 'Oceania',
		'PW' => 'Oceania',
		'PG' => 'Oceania',
		'PN' => 'Oceania',
		'WS' => 'Oceania',
		'SB' => 'Oceania',
		'TK' => 'Oceania',
		'TO' => 'Oceania',
		'TV' => 'Oceania',
		'VU' => 'Oceania',
		'WF' => 'Oceania',

		// Antarctica
		'AQ' => 'Antarctica',
		'BV' => 'Antarctica',
		'TF' => 'Antarctica',
		'HM' => 'Antarctica',
		'GS' => 'Antarctica',
	];

	/**
	 * What people actually type, against the code it means.
	 *
	 * The table holds one official name per country, and almost nobody uses
	 * it. An address form gets "UK", a CSV import gets "USA", and a support
	 * ticket says "Holland" -- none of which match "United Kingdom", "United
	 * States" or "Netherlands", so a strict lookup returns nothing and the
	 * order lands with no country at all.
	 *
	 * The four British nations are here on purpose. They are not countries in
	 * ISO 3166-1 and people write them constantly. Keyed in lower case; the
	 * lookup lowers the needle rather than the table.
	 *
	 * @var array<string, string>
	 */
	private const ALIASES = [
		'usa'                      => 'US',
		'u.s.a.'                   => 'US',
		'u.s.'                     => 'US',
		'us'                       => 'US',
		'america'                  => 'US',
		'united states of america' => 'US',
		'uk'                       => 'GB',
		'u.k.'                     => 'GB',
		'great britain'            => 'GB',
		'britain'                  => 'GB',
		'england'                  => 'GB',
		'scotland'                 => 'GB',
		'wales'                    => 'GB',
		'northern ireland'         => 'GB',
		'holland'                  => 'NL',
		'the netherlands'          => 'NL',
		'south korea'              => 'KR',
		'north korea'              => 'KP',
		'russia'                   => 'RU',
		'uae'                      => 'AE',
		'ivory coast'              => 'CI',
		'burma'                    => 'MM',
		'czechia'                  => 'CZ',
		'swaziland'                => 'SZ',
		'macedonia'                => 'MK',
		'turkey'                   => 'TR',
		'vatican'                  => 'VA',
		'palestine'                => 'PS',
	];

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
	 * European Union member states.
	 *
	 * The list that decides whether VAT rules apply, so it matters that GB
	 * is absent and that the outermost regions are handled deliberately.
	 */
	private const EU_COUNTRIES = [
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
	private const HIGH_RISK_COUNTRIES = [
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
	private const SANCTIONED_COUNTRIES = [
		'CU',
		'IR',
		'KP',
		'RU',
		'SY',
		'VE',
	];

	/* ========================================================================
	 * COUNTRY DATA
	 * ======================================================================== */

	/**
	 * Every country, in order of name.
	 *
	 * @return array<string, string> Code => name.
	 * @since 1.0.0
	 */
	public static function all(): array {
		return self::COUNTRIES;
	}

	/**
	 * Every country code, in order of the country's name.
	 *
	 * @return list<string>
	 * @since 1.0.0
	 */
	public static function get_codes(): array {
		return array_keys( self::COUNTRIES );
	}

	/**
	 * A country's name.
	 *
	 * @param string $code Country code, in either case.
	 *
	 * @return string The name, or the code as given when it is not a
	 *                country: an order stored with a code this list does
	 *                not have still shows something.
	 * @since 1.0.0
	 */
	public static function get_name( string $code ): string {
		return self::COUNTRIES[ self::normalize( $code ) ] ?? $code;
	}

	/**
	 * Whether a code is a country.
	 *
	 * @param string $code Country code, in either case.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function exists( string $code ): bool {
		return isset( self::COUNTRIES[ self::normalize( $code ) ] );
	}

	/**
	 * The code for a country's name.
	 *
	 * In any case, accented capitals included ("ÅLAND ISLANDS"), and the
	 * names people actually write -- "USA", "UK", "Holland" -- as well as
	 * the official one.
	 *
	 * @param string $name A country's name, or its code.
	 *
	 * @return string|null The code, or null when the name is not known.
	 * @since 1.0.0
	 */
	public static function get_code( string $name ): ?string {
		$name = mb_strtolower( trim( $name ), 'UTF-8' );

		if ( '' === $name ) {
			return null;
		}

		// A code, given where a name was expected. Worth accepting: this is
		// the method a CSV import reaches for, and a column headed "country"
		// carries both.
		if ( isset( self::COUNTRIES[ strtoupper( $name ) ] ) ) {
			return strtoupper( $name );
		}

		if ( isset( self::ALIASES[ $name ] ) ) {
			return self::ALIASES[ $name ];
		}

		foreach ( self::COUNTRIES as $code => $country_name ) {
			if ( mb_strtolower( $country_name, 'UTF-8' ) === $name ) {
				return $code;
			}
		}

		return null;
	}

	/**
	 * Countries whose name or code contains some text, for an autocomplete.
	 *
	 * In any case, accented letters included: "åland" finds "Åland Islands".
	 *
	 * @param string $search What was typed.
	 * @param int    $limit  The most to return. 0 for all of them.
	 *
	 * @return array<string, string> Code => name, in order of name.
	 * @since 1.0.0
	 */
	public static function search( string $search, int $limit = 0 ): array {
		$search = mb_strtolower( trim( $search ), 'UTF-8' );

		if ( '' === $search ) {
			return [];
		}

		$matches = [];

		foreach ( self::COUNTRIES as $code => $name ) {
			if ( str_contains( mb_strtolower( $name, 'UTF-8' ), $search ) || str_contains( strtolower( $code ), $search ) ) {
				$matches[ $code ] = $name;

				if ( $limit > 0 && count( $matches ) >= $limit ) {
					break;
				}
			}
		}

		return $matches;
	}

	/**
	 * A code checked and tidied, or nothing.
	 *
	 * @param string $code What was given. Trimmed and upper-cased.
	 *
	 * @return string|null The code, or null when it is not a country.
	 * @since 1.0.0
	 */
	public static function sanitize( string $code ): ?string {
		$code = self::normalize( $code );

		return isset( self::COUNTRIES[ $code ] ) ? $code : null;
	}

	/**
	 * A list of codes checked and tidied.
	 *
	 * For a stored or submitted set -- countries a seller excludes, ships
	 * to, charges VAT in. Each entry is trimmed and upper-cased, anything
	 * that is not a country is dropped, duplicates go, and the rest is
	 * sorted: so two saves of the same set store the same list, and a
	 * change in stored settings means a real change rather than a reorder.
	 *
	 * Takes an array, or a string separated by commas or spaces, because a
	 * typed field and most APIs send the second. Anything else -- a number,
	 * null, a nested array -- contributes nothing.
	 *
	 * @param mixed $codes A list of codes, or "GB, ie de".
	 *
	 * @return list<string> Upper-case codes, each once, in order of code.
	 * @since 1.0.0
	 */
	public static function sanitize_list( mixed $codes ): array {
		if ( is_string( $codes ) ) {
			$codes = preg_split( '/[\s,]+/', $codes, -1, PREG_SPLIT_NO_EMPTY ) ?: [];
		}

		if ( ! is_array( $codes ) ) {
			return [];
		}

		$clean = [];

		foreach ( $codes as $code ) {
			$code = is_string( $code ) ? self::sanitize( $code ) : null;

			if ( null !== $code ) {
				$clean[ $code ] = true;
			}
		}

		ksort( $clean, SORT_STRING );

		return array_map( 'strval', array_keys( $clean ) );
	}

	/* ========================================================================
	 * FORMATTING & RENDERING
	 * ======================================================================== */

	/**
	 * A country as text, with its flag or its code if wanted.
	 *
	 * @param string $code         Country code, in either case.
	 * @param bool   $include_flag Put the emoji flag first.
	 * @param bool   $include_code Put the code after, in brackets.
	 *
	 * @return string "🇬🇧 United Kingdom (GB)", or the code as given, upper
	 *                case, when it is not a country.
	 * @since 1.0.0
	 */
	public static function format( string $code, bool $include_flag = false, bool $include_code = false ): string {
		$code = self::normalize( $code );

		if ( ! self::exists( $code ) ) {
			return $code;
		}

		$parts = [];

		if ( $include_flag ) {
			$parts[] = self::get_flag( $code );
		}

		$parts[] = self::get_name( $code );

		if ( $include_code ) {
			$parts[] = '(' . $code . ')';
		}

		return implode( ' ', $parts );
	}

	/**
	 * A country for a page: its emoji flag and its name, escaped.
	 *
	 * @param string $code         Country code (ISO 3166-1 alpha-2, e.g. 'US', 'GB').
	 * @param bool   $include_flag Include the emoji flag.
	 * @param bool   $include_name Include the country's name.
	 *
	 * @return string|null Escaped HTML; the code alone, escaped, when it is
	 *                     not a country; null when it is empty.
	 * @since 1.0.0
	 */
	public static function render( string $code, bool $include_flag = true, bool $include_name = true ): ?string {
		$code = self::normalize( $code );

		if ( '' === $code ) {
			return null;
		}

		if ( ! self::exists( $code ) ) {
			return esc_html( $code );
		}

		$parts = [];

		if ( $include_flag ) {
			$parts[] = self::get_flag( $code );
		}

		if ( $include_name ) {
			$parts[] = self::get_name( $code );
		}

		return esc_html( implode( ' ', $parts ) );
	}

	/**
	 * A country's emoji flag.
	 *
	 * The flag is the code spelt in regional indicator letters, so only two
	 * letters make one: `G1`, `--` or two bytes of anything else used to
	 * come back as two stray symbols. Any two letters do, a country or not
	 * -- `EU` has a flag too. Drawn by whatever the reader's machine has,
	 * which on Windows is only the two letters; for a flag that looks the
	 * same everywhere, see {@see Flags}.
	 *
	 * @param string $code Country code, in either case.
	 *
	 * @return string The emoji, or an empty string for anything that is not
	 *                two letters.
	 * @since 1.0.0
	 */
	public static function get_flag( string $code ): string {
		$code = self::normalize( $code );

		if ( 1 !== preg_match( '/^[A-Z]{2}\z/', $code ) ) {
			return '';
		}

		// 'A' is U+1F1E6, the first regional indicator letter.
		return mb_chr( 0x1F1E6 + ord( $code[0] ) - 65, 'UTF-8' )
			. mb_chr( 0x1F1E6 + ord( $code[1] ) - 65, 'UTF-8' );
	}

	/* ========================================================================
	 * CONTINENTS
	 * ======================================================================== */

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
		return self::CONTINENTS[ self::normalize( $code ) ] ?? null;
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

		return array_intersect_key( self::COUNTRIES, array_filter( self::CONTINENTS, static fn( string $name ): bool => $name === $continent_name ) );
	}

	/* ========================================================================
	 * EUROPEAN UNION
	 * ======================================================================== */

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

	/* ========================================================================
	 * RISK CATEGORIES
	 * ======================================================================== */

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

	/* ========================================================================
	 * INTERNAL
	 * ======================================================================== */

	/**
	 * Some countries, with their names, in order of name.
	 *
	 * @param array<int, string> $codes Country codes.
	 *
	 * @return array<string, string>
	 */
	private static function pick( array $codes ): array {
		return array_intersect_key( self::COUNTRIES, array_flip( $codes ) );
	}

	/**
	 * A code trimmed and upper-cased.
	 *
	 * @param string $code Raw code.
	 *
	 * @return string
	 */
	private static function normalize( string $code ): string {
		return strtoupper( trim( $code ) );
	}
}
