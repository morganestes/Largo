<?php

class FeaturedContentTestFunctions extends WP_UnitTestCase {
	function setUp() {
		parent::setUp();

		// The generic post
		$this->post = $this->factory->post->create_and_get(array(
		));

		// We need a post in a series
		$this->post_in_series = $this->factory->post->create_and_get(array(
			'tax_input' => array(
#				'taxonomy' => 'tag',
				'series' => 'test'
			)
		));
	}

	function test_largo_get_featured_posts() {
		// Testing this would be the equivalent to a functional test of WordPress' core
		// WP_Query, wp_parse_args, and wp_reset_postdata
		$this->assertTrue(function_exists('largo_get_featured_posts'));
		global $post;
		$post = $this->post;
		$a = $post;
		$ret = largo_get_featured_posts();
		$b = $post;
		$this->assertEquals($a, $b); // Check that it resets the post data, because it uses a new WP_Query
		$this->assertInstanceOf('WP_Query', $ret);

		// Cleanup
		$post = null;
	}

	function test_largo_get_the_main_feature() {
		global $post;
		$post = $this->post;
		$post_backup = $this->post;

		// Test using global $post and no series
		$ret = largo_get_the_main_feature();
		$this->assertFalse($ret);

		// Now with a post object that has a series
		$ret = largo_get_the_main_feature($this->post_in_series);
		$this->assertEquals('series', $ret->taxonomy);

		// Cleanup
		$post = $post_backup;
	}

	function test_largo_scrub_sticky_posts() {
		// If a post is marked as sticky, this unsticks any other posts on the blog so that there is only one sticky post at a time.
		$this->markTestIncomplete('This test has not been implemented yet.');
		// This should not ever break, and if it does, #blamenacin
	}

	function test_largo_have_featured_posts() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_largo_have_homepage_featured_posts() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_largo_scrub_sticky_posts() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}
}
