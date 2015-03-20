<?php

class EnqueueTestFunctions extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();

	}

	function test_largo_enqueue_js() {
		global $wp_scripts, $wp_styles;

		// not running on a single page

		largo_enqueue_js();

		// Base Largo stylesheet
		$this->assertTrue(!empty($wp_styles->registered['largo-stylesheet']), "largo-stylesheet");
		// Modernizr script
		$this->assertTrue(!empty($wp_scripts->registered['largo-modernizr']), "largo-modernizr");

		// Largo Plugins and jquery scripts
		$this->assertTrue(!empty($wp_scripts->registered['largoPlugins']), "largoPlugins");
		$this->assertTrue(!empty($wp_scripts->registered['largoCore']), "largoCore");
		
		// Jquery tabs plugin on single pages, which should fail because this is not a post
		$this->assertFalse(!empty($wp_scripts->registered['idTabs']), "idTabs on single pages");
		
		// Load the child theme's style.css if we're actually running a child theme of Largo
		$this->markTestIncomplete('This test has not been implemented yet.');

		// Running on a single page
		$this->go_to('/?p=1');
		largo_enqueue_js();

		$this->assertTrue(!empty($wp_scripts->registered['idTabs']), "idTabs on single pages");
	}

	function test_largo_enqueue_admin_scripts() {
		global $wp_scripts, $wp_styles;

		largo_enqueue_admin_scripts();

		// Styles
		$this->assertTrue(!empty($wp_styles->registered['largo-admin-widgets']));
		// Scripts
		$this->assertTrue(!empty($wp_scripts->registered['largo-admin-widgets']));
	}

	function test_largo_header_js() {
		$banner_image_sm = "sm.gif";
		of_set_option( 'banner_image_', $banner_image_sm );
		$banner_image_med = "med.gif";
		of_set_option( 'banner_image_med', $banner_image_med );
		$banner_image_lg = "lg.gif";
		of_set_option( 'banner_image_lg', $banner_image_lg );

		$this->expectOutputRegex("/$banner_image_sm/");
		$this->expectOutputRegex("/$banner_image_med/");
		$this->expectOutputRegex("/$banner_image_lg/");

		largo_header_js();
	}

	function test_largo_footer_js() {
		$this->expectOutputRegex("/Facebook/");
		$this->expectOutputRegex("/Twitter/");
		$this->expectOutputRegex("/Google Plus/");

		largo_footer_js();
	}

	function largo_google_analytics() {
		$this->expectOutputRegex('/_gaq/');
		$this->expectOutputRegex(bloginfo('name'));
		$this->expectOutputRegex(parse_url( home_url(), PHP_URL_HOST ));

		largo_google_analytics();
	}
}
