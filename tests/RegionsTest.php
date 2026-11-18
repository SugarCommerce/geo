<?php
/**
 * Region accessor tests.
 *
 * @package SugarCommerce\Geo
 */

declare( strict_types=1 );

namespace SugarCommerce\Geo\Tests;

use SugarCommerce\Geo\Countries;
use SugarCommerce\Geo\Regions;
use PHPUnit\Framework\TestCase;

/**
 * The accessors over the regions table.
 *
 * A region code only means something against its country -- 'ON' is Ontario
 * in Canada, Oshana in Namibia and Ondo in Nigeria -- so every accessor takes
 * both, and both arrive from the same untrusted places a country code does.
 */
final class RegionsTest extends TestCase {

	/**
	 * A few regions everybody knows are where they should be.
	 */
	public function test_a_sample_of_regions_are_where_they_belong(): void {
		$this->assertSame( 'California', Regions::get_name( 'US', 'CA' ) );
		$this->assertSame( 'Ontario', Regions::get_name( 'CA', 'ON' ) );
		$this->assertSame( 'New South Wales', Regions::get_name( 'AU', 'NSW' ) );
		$this->assertSame( 'Berlin', Regions::get_name( 'DE', 'DE-BE' ) );
	}

	/**
	 * The United States lists its fifty states, the District and the forces.
	 *
	 * That is what WooCommerce ships: no territories, because Puerto Rico,
	 * Guam and the rest are countries of their own in ISO 3166-1, and so in
	 * this library's table.
	 */
	public function test_the_united_states_lists_its_states(): void {
		$states = Regions::for( 'US' );

		$this->assertCount( 54, $states );
		$this->assertSame( 'California', $states['CA'] );
		$this->assertSame( 'District of Columbia', $states['DC'] );

		foreach ( [ 'AA', 'AE', 'AP' ] as $forces ) {
			$this->assertArrayHasKey( $forces, $states );
		}

		$this->assertArrayNotHasKey( 'PR', $states );
	}

	/**
	 * A country without regions returns nothing, and so does a non-country.
	 *
	 * WooCommerce ships no list for the United Kingdom, so a British address
	 * form has no county dropdown to fill. An empty array rather than null,
	 * so the caller can loop over the answer without checking it first.
	 */
	public function test_a_country_without_regions_returns_nothing(): void {
		$this->assertSame( [], Regions::for( 'GB' ) );
		$this->assertFalse( Regions::has( 'GB' ) );

		$this->assertSame( [], Regions::for( 'ZZ' ) );
		$this->assertFalse( Regions::has( 'ZZ' ) );
		$this->assertSame( [], Regions::for( '' ) );
	}

	/**
	 * The country code is matched whatever case it arrives in.
	 *
	 * It goes through Countries::sanitize(), so it is trimmed as well.
	 */
	public function test_the_country_is_matched_whatever_its_case(): void {
		$this->assertSame( Regions::for( 'US' ), Regions::for( 'us' ) );
		$this->assertTrue( Regions::has( ' ca ' ) );
		$this->assertSame( 'Ontario', Regions::get_name( 'ca', 'ON' ) );
	}

	/**
	 * And so is the region code.
	 */
	public function test_the_region_is_matched_whatever_its_case(): void {
		$this->assertSame( 'California', Regions::get_name( 'US', 'ca' ) );
		$this->assertTrue( Regions::exists( 'CA', 'on' ) );
		$this->assertSame( 'ON', Regions::sanitize( 'ca', ' on ' ) );
	}

	/**
	 * Sanitising accepts a real code for that country and refuses the rest.
	 *
	 * Including a real code for a different country: 'ON' is not a state of
	 * the United States, however many other countries have one.
	 */
	public function test_sanitising_accepts_only_a_real_code_for_that_country(): void {
		$this->assertSame( 'CA', Regions::sanitize( 'US', 'ca' ) );

		foreach ( [ '', ' ', 'ZZ', 'ON', 'California', '<b>', 'CA CA', 'US-CA' ] as $bad ) {
			$this->assertNull( Regions::sanitize( 'US', $bad ), sprintf( '"%s" was accepted.', $bad ) );
		}

		$this->assertNull( Regions::sanitize( 'GB', 'CA' ) );
		$this->assertNull( Regions::sanitize( 'ZZ', 'CA' ) );
	}

	/**
	 * A code comes back as the table has it, not merely upper-cased.
	 *
	 * The codes are kept exactly as WooCommerce has them because they are
	 * what stores already hold, and three countries are not two upper-case
	 * letters: Morocco's are lower case, Hong Kong's are district names, and
	 * the Minor Outlying Islands' are numbers, which PHP has quietly turned
	 * into integer keys.
	 */
	public function test_a_code_comes_back_as_the_table_has_it(): void {
		$this->assertSame( 'macas', Regions::sanitize( 'MA', 'MACAS' ) );
		$this->assertSame( 'macas', Regions::sanitize( 'MA', 'macas' ) );
		$this->assertSame( 'Casablanca', Regions::get_name( 'MA', 'MACAS' ) );

		$this->assertSame( 'HONG KONG', Regions::sanitize( 'HK', 'hong kong' ) );

		$this->assertSame( '81', Regions::sanitize( 'UM', '81' ) );
		$this->assertSame( 'Baker Island', Regions::get_name( 'UM', '81' ) );
	}

	/**
	 * An unknown region has no name, rather than its code echoed back.
	 *
	 * The opposite of Countries::get_name(), on purpose: a region code
	 * without its country is not legible on its own, so there is nothing
	 * useful to echo.
	 */
	public function test_an_unknown_region_has_no_name(): void {
		$this->assertSame( '', Regions::get_name( 'US', 'ZZ' ) );
		$this->assertSame( '', Regions::get_name( 'GB', 'ENG' ) );
		$this->assertSame( '', Regions::get_name( 'ZZ', 'CA' ) );
		$this->assertSame( '', Regions::get_name( 'US', '' ) );
		$this->assertFalse( Regions::exists( 'US', 'ZZ' ) );
	}

	/**
	 * The countries listed are exactly the ones has() says yes to.
	 */
	public function test_the_countries_listed_are_the_ones_with_regions(): void {
		$countries = Regions::countries();

		$this->assertCount( 69, $countries );
		$this->assertContains( 'US', $countries );
		$this->assertContains( 'CA', $countries );
		$this->assertNotContains( 'GB', $countries );

		foreach ( $countries as $country ) {
			$this->assertTrue( Regions::has( $country ), sprintf( '%s is listed but has() says no.', $country ) );
			$this->assertTrue( Countries::exists( $country ), sprintf( '%s is not a country.', $country ) );
		}
	}

	/**
	 * The whole table comes back as country against its regions.
	 */
	public function test_all_returns_the_whole_table(): void {
		$all = Regions::all();

		$this->assertSame( Regions::countries(), array_keys( $all ) );
		$this->assertSame( 'California', $all['US']['CA'] );
		$this->assertSame( Regions::for( 'CA' ), $all['CA'] );
	}
}
