<?php
/**
 * Hook names.
 *
 * @package SugarCommerce\Geo
 */

declare( strict_types=1 );

namespace SugarCommerce\Geo\Tests;

use SugarCommerce\Geo\Utils\Runtime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Each copy of the library names its hooks for itself.
 *
 * Two plugins bundling the library through Strauss must not share a filter,
 * or one plugin's CDN for flags serves the other plugin's flags too.
 */
final class RuntimeTest extends TestCase {

	/**
	 * Unprefixed, the hooks are the library's own.
	 */
	public function test_an_unprefixed_build_uses_the_library_name(): void {
		$this->assertSame( 'sugarcommerce_geo', Runtime::prefix() );
		$this->assertSame( 'sugarcommerce_geo_flags_url', Runtime::hook( 'flags_url' ) );
	}

	/**
	 * A prefixed build puts the plugin's prefix in front.
	 *
	 * @param string $under    The namespace the class is compiled under.
	 * @param string $expected The prefix it gives.
	 */
	#[DataProvider( 'namespaces' )]
	public function test_a_prefixed_build_names_hooks_for_itself( string $under, string $expected ): void {
		$this->assertSame( $expected, Runtime::prefix_for( $under ) );
	}

	/**
	 * Namespaces and the prefix each gives.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function namespaces(): array {
		return [
			'unprefixed'          => [ 'SugarCommerce\\Geo\\Utils', 'sugarcommerce_geo' ],
			'a plugin prefix'     => [ 'SugarCart\\Vendor\\SugarCommerce\\Geo\\Utils', 'sugarcart_sugarcommerce_geo' ],
			'a leading slash'     => [ '\\MyShop\\SugarCommerce\\Geo\\Utils', 'myshop_sugarcommerce_geo' ],
			'a mixed-case prefix' => [ 'My_Shop2\\SugarCommerce\\Geo\\Utils', 'my_shop2_sugarcommerce_geo' ],
			'nothing at all'      => [ '', 'sugarcommerce_geo' ],
		];
	}

	/**
	 * A plugin of the family prefixes with a namespace that also starts
	 * "SugarCommerce", and its build is still its own.
	 */
	public function test_a_family_plugin_build_is_not_taken_for_the_library(): void {
		$playground = Runtime::prefix_for( 'SugarCommerce\\Playground\\Vendor\\SugarCommerce\\Geo\\Utils' );

		$this->assertSame( 'sugarcommerce_playground_vendor_sugarcommerce_geo', $playground );
		$this->assertNotSame(
			$playground,
			Runtime::prefix_for( 'SugarCommerce\\Kitchen\\Vendor\\SugarCommerce\\Geo\\Utils' ),
			'Two plugins of the family share a name.'
		);
		$this->assertSame( 'sugarcart_sugarcommerce_geo', Runtime::prefix_for( 'SugarCart\\Vendor\\SugarCommerce\\Geo\\Utils' ), 'A plugin outside the family keeps its name.' );
		$this->assertSame( 'sugarcommerce_geo', Runtime::prefix_for( '\\SugarCommerce\\Geo\\Utils\\' ) );
	}
}
