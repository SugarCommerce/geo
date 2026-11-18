<?php
/**
 * Country data integrity tests.
 *
 * @package SugarCommerce\Geo
 */

declare( strict_types=1 );

namespace SugarCommerce\Geo\Tests;

use SugarCommerce\Geo\Countries;
use SugarCommerce\Geo\Regions;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * This library is six hand-maintained tables, one generated one, and a few
 * accessors over them.
 *
 * Which means the accessors are the easy part. The risk is the data: 250
 * countries, a continent for each, and four membership lists that all refer
 * back to the country codes. A code that is in one table and not another
 * produces no error anywhere -- get_continent() returns null, is_eu() returns
 * false, and the only symptom is a country quietly behaving as though it were
 * somewhere else.
 *
 * These read the tables directly and check they agree with each other. An
 * added country with no continent fails here rather than in production.
 *
 * The regions table is generated from WooCommerce's list rather than typed,
 * which rules out a typo and nothing else: it still has to name countries the
 * country table knows, and hold codes the accessors can match.
 */
final class DataTest extends TestCase {

	/**
	 * A private constant, read by reflection.
	 *
	 * Reaching past the accessors on purpose: what is being checked is whether
	 * the tables agree, and going through the accessors would only prove they
	 * agree with themselves.
	 *
	 * @param string $name Constant name.
	 *
	 * @return array<array-key, mixed>
	 */
	private function table( string $name ): array {
		return (array) ( new ReflectionClass( Countries::class ) )->getConstant( $name );
	}

	/**
	 * Every country code is two upper-case letters.
	 *
	 * They are used as array keys, compared with strtoupper() on the way in,
	 * and turned into flag emoji by arithmetic on their two characters. A
	 * lower-case or three-letter key fails all three quietly.
	 */
	public function test_every_country_code_is_alpha_two(): void {
		foreach ( array_keys( $this->table( 'COUNTRIES' ) ) as $code ) {
			$this->assertMatchesRegularExpression( '/^[A-Z]{2}$/', (string) $code );
		}
	}

	/**
	 * Every country has a name, and no two share one.
	 *
	 * get_code() is a reverse lookup by name and returns the first match, so a
	 * duplicated name makes one of the two unreachable.
	 */
	public function test_every_country_has_its_own_name(): void {
		$names = $this->table( 'COUNTRIES' );

		foreach ( $names as $code => $name ) {
			$this->assertNotSame( '', trim( (string) $name ), sprintf( '%s has no name.', $code ) );
		}

		$this->assertSame(
			[],
			array_diff_assoc( $names, array_unique( $names ) ),
			'Two countries share a name, so get_code() cannot reach one of them.'
		);
	}

	/**
	 * Every country is on a continent.
	 *
	 * Four were not: the British Indian Ocean Territory, Christmas Island, the
	 * Cocos Islands and the United States Minor Outlying Islands. Nothing
	 * reported it -- get_continent() returned null and get_by_continent()
	 * simply never listed them.
	 */
	public function test_every_country_is_on_a_continent(): void {
		$missing = array_diff(
			array_keys( $this->table( 'COUNTRIES' ) ),
			array_keys( $this->table( 'CONTINENTS' ) )
		);

		$this->assertSame( [], array_values( $missing ), 'These countries have no continent.' );
	}

	/**
	 * And nothing is on a continent without being a country.
	 */
	public function test_the_continent_table_holds_no_strangers(): void {
		$extra = array_diff(
			array_keys( $this->table( 'CONTINENTS' ) ),
			array_keys( $this->table( 'COUNTRIES' ) )
		);

		$this->assertSame( [], array_values( $extra ) );
	}

	/**
	 * Every continent named is one of the seven.
	 *
	 * The table stores names rather than codes, so a typo is a new continent
	 * with one country on it.
	 */
	public function test_every_continent_named_is_a_real_one(): void {
		$named = array_unique( array_values( $this->table( 'CONTINENTS' ) ) );
		$known = array_values( $this->table( 'CONTINENT_CODES' ) );

		$this->assertSame( [], array_values( array_diff( $named, $known ) ) );
	}

	/**
	 * Each membership list holds real country codes, once each.
	 *
	 * @param string $constant The table.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider( 'membershipProvider' )]
	public function test_a_membership_list_holds_real_countries( string $constant ): void {
		$list      = $this->table( $constant );
		$countries = array_keys( $this->table( 'COUNTRIES' ) );

		$this->assertNotEmpty( $list );

		$this->assertSame(
			[],
			array_values( array_diff( $list, $countries ) ),
			sprintf( '%s lists something that is not a country code.', $constant )
		);

		$this->assertSame(
			[],
			array_values( array_diff_assoc( $list, array_unique( $list ) ) ),
			sprintf( '%s lists the same country twice.', $constant )
		);
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function membershipProvider(): array {
		return [
			'the EU'                => [ 'EU_COUNTRIES' ],
			'the high-risk list'    => [ 'HIGH_RISK_COUNTRIES' ],
			'the sanctioned list'   => [ 'SANCTIONED_COUNTRIES' ],
		];
	}

	/**
	 * The EU has twenty-seven members.
	 *
	 * A fixed, checkable number, and the one list here that a reader is most
	 * likely to trust for something that matters -- VAT, or where the GDPR
	 * applies. The United Kingdom leaving is exactly the kind of edit that
	 * gets made to one list and not another.
	 */
	public function test_the_eu_has_twenty_seven_members(): void {
		$eu = $this->table( 'EU_COUNTRIES' );

		$this->assertCount( 27, $eu );
		$this->assertNotContains( 'GB', $eu, 'The United Kingdom left in 2020.' );

		// The six founders, still in.
		foreach ( [ 'BE', 'DE', 'FR', 'IT', 'LU', 'NL' ] as $founder ) {
			$this->assertContains( $founder, $eu );
		}

		// Croatia, the most recent to join.
		$this->assertContains( 'HR', $eu );

		// Switzerland and Norway are in Europe and not in the EU, which is the
		// distinction the list exists to make.
		$this->assertNotContains( 'CH', $eu );
		$this->assertNotContains( 'NO', $eu );
	}

	/**
	 * Every EU member is in Europe, except the one that is not.
	 *
	 * Cyprus is in Western Asia and in the European Union, and this library
	 * files it geographically. That is the defensible reading -- the UN puts
	 * it in Western Asia too -- but it means `get_by_continent( 'Europe' )`
	 * and `is_eu()` disagree about one country, and a consumer filtering
	 * European countries for VAT would miss it.
	 *
	 * So the exception is pinned rather than smoothed over. Anything else
	 * drifting out of Europe is a mistake; this one is a decision, and a
	 * reader who hits it can find out why here.
	 */
	public function test_every_eu_member_is_in_europe_bar_cyprus(): void {
		$continents = $this->table( 'CONTINENTS' );

		$elsewhere = [];

		foreach ( $this->table( 'EU_COUNTRIES' ) as $code ) {
			if ( 'Europe' !== ( $continents[ $code ] ?? null ) ) {
				$elsewhere[ $code ] = $continents[ $code ] ?? 'nothing';
			}
		}

		$this->assertSame( [ 'CY' => 'Asia' ], $elsewhere );
	}

	/**
	 * The seven continents are the seven continents.
	 */
	public function test_there_are_seven_continents(): void {
		$this->assertSame(
			[
				'AF' => 'Africa',
				'AN' => 'Antarctica',
				'AS' => 'Asia',
				'EU' => 'Europe',
				'NA' => 'North America',
				'OC' => 'Oceania',
				'SA' => 'South America',
			],
			$this->table( 'CONTINENT_CODES' )
		);
	}

	/**
	 * Every continent has at least one country on it.
	 */
	public function test_no_continent_is_empty(): void {
		$used = array_unique( array_values( $this->table( 'CONTINENTS' ) ) );

		foreach ( $this->table( 'CONTINENT_CODES' ) as $code => $name ) {
			$this->assertContains( $name, $used, sprintf( '%s has no countries on it.', $name ) );
		}
	}

	/**
	 * A few countries are where they should be.
	 *
	 * A spot check against the sort of mistake the cross-table tests cannot
	 * see, because a wrong-but-consistent entry agrees with itself.
	 */
	public function test_a_sample_of_countries_are_where_they_belong(): void {
		$this->assertSame( 'Europe', Countries::get_continent( 'GB' ) );
		$this->assertSame( 'Asia', Countries::get_continent( 'JP' ) );
		$this->assertSame( 'Africa', Countries::get_continent( 'ZA' ) );
		$this->assertSame( 'North America', Countries::get_continent( 'MX' ) );
		$this->assertSame( 'South America', Countries::get_continent( 'BR' ) );
		$this->assertSame( 'Oceania', Countries::get_continent( 'NZ' ) );
		$this->assertSame( 'Antarctica', Countries::get_continent( 'AQ' ) );

		// Russia and Turkey straddle two; the table has to pick one and be
		// consistent about it.
		$this->assertContains( Countries::get_continent( 'RU' ), [ 'Europe', 'Asia' ] );
		$this->assertContains( Countries::get_continent( 'TR' ), [ 'Europe', 'Asia' ] );
	}

	/**
	 * The regions table, read by reflection for the same reason.
	 *
	 * @return array<string, array<array-key, string>>
	 */
	private function regions(): array {
		return (array) ( new ReflectionClass( Regions::class ) )->getConstant( 'REGIONS' );
	}

	/**
	 * Every country with regions is a country.
	 *
	 * for() sanitises the country code through Countries first, so a region
	 * list filed under a code Countries does not know is unreachable: has()
	 * says no, for() returns nothing, and the entry is dead weight.
	 */
	public function test_every_region_list_belongs_to_a_country(): void {
		$strangers = array_diff( array_keys( $this->regions() ), array_keys( $this->table( 'COUNTRIES' ) ) );

		$this->assertSame( [], array_values( $strangers ), 'These region lists are filed under something that is not a country.' );
	}

	/**
	 * The regions table is sorted by country, and no list is empty.
	 *
	 * It is generated sorted, and an empty list would make has() and
	 * countries() disagree with for().
	 */
	public function test_the_region_table_is_sorted_and_nothing_is_empty(): void {
		$regions = $this->regions();
		$sorted  = array_keys( $regions );
		sort( $sorted );

		$this->assertSame( $sorted, array_keys( $regions ) );

		foreach ( $regions as $country => $list ) {
			$this->assertNotEmpty( $list, sprintf( '%s has an empty region list.', $country ) );
		}
	}

	/**
	 * Every region code is usable as one, once per country.
	 *
	 * A code is matched whatever case it arrives in, so two codes in one
	 * country differing only by case would be one code with two names. And
	 * it is trimmed on the way in, so a padded key could never be matched.
	 */
	public function test_every_region_code_is_usable(): void {
		foreach ( $this->regions() as $country => $list ) {
			$seen = [];

			foreach ( array_keys( $list ) as $code ) {
				$code = (string) $code;

				$this->assertNotSame( '', $code, sprintf( '%s has a region with no code.', $country ) );
				$this->assertSame( trim( $code ), $code, sprintf( '%s/%s is padded.', $country, $code ) );

				$upper = strtoupper( $code );

				$this->assertArrayNotHasKey( $upper, $seen, sprintf( '%s has %s twice, differing only by case.', $country, $code ) );

				$seen[ $upper ] = true;
			}
		}
	}

	/**
	 * Every region has a name, and it is text rather than markup.
	 *
	 * The names go into option tags and order screens through esc_html(),
	 * which would print any markup as literal angle brackets.
	 */
	public function test_every_region_has_a_plain_name(): void {
		foreach ( $this->regions() as $country => $list ) {
			foreach ( $list as $code => $name ) {
				$this->assertNotSame( '', trim( $name ), sprintf( '%s/%s has no name.', $country, $code ) );
				$this->assertSame( trim( $name ), $name, sprintf( '%s/%s is padded.', $country, $code ) );
				$this->assertSame( strip_tags( $name ), $name, sprintf( '%s/%s contains markup.', $country, $code ) );
				$this->assertSame( html_entity_decode( $name, ENT_QUOTES, 'UTF-8' ), $name, sprintf( '%s/%s contains an entity.', $country, $code ) );
			}
		}
	}
}
