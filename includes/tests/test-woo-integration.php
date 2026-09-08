<?php
/**
 * Tests unitarios para YGB Avisos WooIntegration.
 *
 * @package YGB_Avisos
 */

class YGB_Avisos_Woo_Test extends WP_UnitTestCase {

	/**
	 * Prueba que los transients se establecen y se pueden recuperar.
	 */
	public function test_transient_cache_products() {
		// Mock de wc_get_products para devolver array vacío.
		add_filter( 'pre_transient_ygb_avisos_sale_products_10', '__return_false' );

		$products = YGB_Avisos_Woo::get_sale_products_cached( 10 );

		$this->assertIsArray( $products );
		$this->assertNotEmpty( get_transient( 'ygb_avisos_sale_products_10' ) );
	}

	/**
	 * Prueba que clear_all_product_cache elimina transients relevantes.
	 */
	public function test_clear_all_product_cache_deletes_transients() {
		set_transient( 'ygb_avisos_product_123', 'test', HOUR_IN_SECONDS );
		set_transient( 'ygb_avisos_archive', 'test', HOUR_IN_SECONDS );
		set_transient( 'ygb_avisos_sale_products', 'test', HOUR_IN_SECONDS );

		YGB_Avisos_Woo::clear_all_product_cache( 123 );

		$this->assertFalse( get_transient( 'ygb_avisos_product_123' ) );
		$this->assertFalse( get_transient( 'ygb_avisos_archive' ) );
		$this->assertFalse( get_transient( 'ygb_avisos_sale_products' ) );
	}

	/**
	 * Prueba sanitización de contenido del ticker.
	 */
	public function test_sanitize_ticker_content_removes_style_and_id() {
		$content = '<span style="color:red" id="malicioso">Hola</span><a href="https://example.com" onclick="alert(1)">Enlace</a>';

		// Usar reflection para acceder al método privado sanitize_ticker_content
		$reflection = new ReflectionClass( 'YGB_Avisos_Shortcode' );
		$method = $reflection->getMethod( 'sanitize_ticker_content' );
		$method->setAccessible( true );

		$sanitized = $method->invokeArgs( null, array( $content ) );

		$this->assertStringNotContainsString( 'style=', $sanitized );
		$this->assertStringNotContainsString( 'id=', $sanitized );
		$this->assertStringNotContainsString( 'onclick', $sanitized );
		$this->assertStringContainsString( '<span>Hola</span>', $sanitized );
		$this->assertStringContainsString( 'href="https://example.com"', $sanitized );
	}
}