<?php
/**
 * Flag pictures.
 *
 * @package SugarCommerce\Geo
 */

declare( strict_types=1 );

namespace SugarCommerce\Geo\Tests;

use SugarCommerce\Geo\Countries;
use SugarCommerce\Geo\Flags;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The bundled flags: which codes have one, where it is, and that a code
 * cannot be made into a path to anywhere else.
 */
final class FlagsTest extends TestCase {

	/**
	 * Nothing remembered from the test before.
	 */
	protected function setUp(): void {
		Flags::flush();
		$GLOBALS['co_filters']      = [];
		$GLOBALS['wp_plugin_paths'] = [];
	}

	/**
	 * The folder of flags as the stubbed site would serve it.
	 *
	 * @return string
	 */
	private static function base(): string {
		return 'https://example.test/wp-content/flags/';
	}

	/**
	 * A flag is found by its code, in either case, where the library is.
	 */
	public function test_a_flag_has_a_url_under_the_library(): void {
		$this->assertSame( self::base() . 'gb.svg', Flags::url( 'GB' ) );
		$this->assertSame( self::base() . 'gb.svg', Flags::url( ' gb ' ) );
		$this->assertSame( dirname( __DIR__ ) . '/assets/flags/us.svg', Flags::path( 'US' ) );
		$this->assertFileExists( Flags::path( 'us' ) );
		$this->assertTrue( Flags::exists( 'jp' ) );
	}

	/**
	 * @param string $code Something that is not two letters.
	 */
	#[DataProvider( 'not_codes' )]
	public function test_only_two_letters_are_a_code( string $code ): void {
		$this->assertSame( '', Flags::sanitize( $code ) );
		$this->assertSame( '', Flags::url( $code ) );
		$this->assertSame( '', Flags::path( $code ) );
		$this->assertSame( '', Flags::img( $code ) );
		$this->assertFalse( Flags::exists( $code ) );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function not_codes(): array {
		return [
			'empty'             => [ '' ],
			'one letter'        => [ 'g' ],
			'three letters'     => [ 'gbr' ],
			'a way out'         => [ '..' ],
			'a path'            => [ '../../wp-config' ],
			'a slash'           => [ 'g/' ],
			'digits'            => [ '12' ],
			'a letter, a digit' => [ 'g1' ],
			'a line after it'   => [ "gb\n" . 'x' ],
			'a trailing line'   => [ "g\n" ],
			'not ascii'         => [ 'é' ],
			'a null byte'       => [ "g\0" ],
		];
	}

	/**
	 * Two letters that are no country have no file and so no flag.
	 */
	public function test_a_code_with_no_file_has_no_flag(): void {
		$this->assertSame( 'zz', Flags::sanitize( 'ZZ' ) );
		$this->assertSame( '', Flags::url( 'zz' ) );
		$this->assertSame( '', Flags::img( 'zz' ) );
		$this->assertFalse( Flags::exists( 'zz' ) );
	}

	/**
	 * Every country the library names has a flag, and the folder holds
	 * nothing but flags and their licence.
	 */
	public function test_every_country_has_a_flag(): void {
		$missing = [];

		foreach ( array_keys( Countries::all() ) as $code ) {
			if ( ! Flags::exists( (string) $code ) ) {
				$missing[] = $code;
			}
		}

		$this->assertSame( [], $missing );

		$others = array_values( array_filter( (array) scandir( Flags::dir() ), static fn( $file ): bool => 1 !== preg_match( '/^[a-z]{2}\.svg\z/', (string) $file ) ) );

		$this->assertSame( [ '.', '..', 'LICENSE', 'README.md' ], $others );
	}

	/**
	 * What ships in the folder is pictures and nothing that runs.
	 *
	 * The files came from somebody else's package. An SVG can carry a
	 * script, an event handler or a reference to another server, and a
	 * newer release of the set copied over these is the moment one would
	 * arrive.
	 */
	public function test_the_flags_are_only_pictures(): void {
		$files = (array) glob( Flags::dir() . '*.svg' );

		$this->assertCount( 265, $files );

		foreach ( $files as $file ) {
			$svg  = (string) file_get_contents( (string) $file );
			$name = basename( (string) $file );

			$this->assertStringStartsWith( '<svg ', $svg, $name );
			$this->assertDoesNotMatchRegularExpression( '/<script|<foreignObject|<!ENTITY|\son[a-z]+\s*=|href\s*=\s*["\']?\s*(?!#)|url\(\s*["\']?\s*(?!#)|@import/i', $svg, $name );
		}

		$this->assertStringContainsString( 'MIT License', (string) file_get_contents( Flags::dir() . 'LICENSE' ) );
	}

	/**
	 * The image: decoration by default, its size stated.
	 */
	public function test_the_image(): void {
		$this->assertSame(
			'<img class="country-flag" src="' . self::base() . 'fr.svg" width="16" height="16" alt="" loading="lazy" decoding="async" />',
			Flags::img( 'FR' )
		);

		$img = Flags::img(
			'fr',
			24,
			[
				'class' => 'mine"',
				'alt'   => 'France <',
			]
		);

		$this->assertStringContainsString( 'class="mine&quot;"', $img );
		$this->assertStringContainsString( 'alt="France &lt;"', $img );
		$this->assertStringContainsString( 'width="24" height="24"', $img );

		$this->assertStringContainsString( 'width="1"', Flags::img( 'fr', 0 ) );
	}

	/**
	 * A caller that has its own URL for a flag has it drawn the same way,
	 * and one that says there is none gets nothing.
	 */
	public function test_the_image_takes_a_url(): void {
		$this->assertStringContainsString( 'src="https://cdn.test/x.svg"', Flags::img( 'fr', 16, [ 'url' => 'https://cdn.test/x.svg' ] ) );
		$this->assertSame( '', Flags::img( 'fr', 16, [ 'url' => '' ] ) );
	}

	/**
	 * A URL the escaper refuses is no URL: no image, rather than a broken
	 * one with an empty `src`.
	 */
	public function test_the_image_refuses_a_url_that_is_not_one(): void {
		$this->assertSame( '', Flags::img( 'fr', 16, [ 'url' => 'javascript:alert(1)' ] ) );
		$this->assertSame( '', Flags::img( 'fr', 16, [ 'url' => 'data:image/svg+xml,<svg onload=alert(1)>' ] ) );
	}

	/**
	 * The folder's URL can be said from outside.
	 */
	public function test_the_base_is_filterable(): void {
		add_filter( 'sugarcommerce_geo_flags_url', static fn(): string => 'https://cdn.test/flags' );

		$this->assertSame( 'https://cdn.test/flags/de.svg', Flags::url( 'de' ) );
		$this->assertSame( '', Flags::url( 'zz' ), 'A filter does not invent a flag.' );

		$GLOBALS['co_filters'] = [];
		add_filter( 'sugarcommerce_geo_flags_url', static fn(): string => '' );

		$this->assertSame( '', Flags::url( 'de' ) );
	}

	/**
	 * The emoji flag is two letters or nothing.
	 */
	public function test_the_emoji_flag_wants_letters(): void {
		$this->assertSame( '🇬🇧', Countries::get_flag( 'gb' ) );
		$this->assertSame( '🇬🇧', Countries::get_flag( ' GB ' ) );
		$this->assertSame( '', Countries::get_flag( 'G1' ) );
		$this->assertSame( '', Countries::get_flag( '12' ) );
		$this->assertSame( '', Countries::get_flag( '--' ) );
		$this->assertSame( '', Countries::get_flag( 'é' ) );
		$this->assertSame( '', Countries::get_flag( "GB\n" . 'X' ) );
	}
}
