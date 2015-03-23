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

	function test_largo_google_analytics_as_admin() {
		// When running as Admin (which tests are), this function should output nothing

		ob_start();
		largo_google_analytics();
		$ret = ob_get_clean();
		$this->assertEquals('', $ret);
		unset($ret);
	}

	function test_largo_google_analytics_as_user() {
		$this->markTestSkipped("wp_set_current_user cannot be used to revert to the omnipotent test-running user, so the code here testing largo_google_analytics will actually break several tests following after this. ");
		/*
		// Preserve the admin user
		$admin = wp_get_current_user();

		// Let's create a new user to use
		$test_user = $this->factory->user->create(array( 'user_login' => 'test', 'role' => 'subscriber'));
		$user_id_role = wp_set_current_user($test_user);
		$user_id_role->set_role('subscriber');

		// Perform the actual test
		ob_start();
		largo_google_analytics();
		$ret = ob_get_clean();

		$this->assertEquals(1, preg_match('/' . preg_quote('_gaq') . '/', $ret ), "Unprivileged users should receive GA tags" );
		$this->assertTrue(preg_match('/' . preg_quote(bloginfo('name')) . '/', $ret ), "GA output should have the site name" );
		$this->assertTrue(preg_match('/' . preg_quote(parse_url( home_url(), PHP_URL_HOST )) . '/', $ret), "GA output should have the site URL");

		// Reset to the previous user, based on https://codex.wordpress.org/Function_Reference/wp_set_current_user
		wp_set_current_user($admin->ID, $admin->user_login);
		wp_set_auth_cookie($admin->ID);
		do_action( 'wp_login', $admin->user_login);
		*/
	}
}
