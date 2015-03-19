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
				'series' => 'test'
			)
		));

		// Let's create some featured posts in a category
		$this->cat1 = $this->factory->term->create(array(
			'taxonomy' => 'category'
		));
		$this->factory->post->create_many(2, array(
			'post_category' => array($this->cat1),
			'tax_input' => array(
				'prominence' => 'taxonomy-featured'
			)
		));
		$this->factory->post->create_many(2, array(
			'post_category' => array($this->cat1),
			'tax_input' => array(
				'prominence' => 'taxonomy-secondary-featured'
			)
		));
		$this->cat1_no_prom = $this->factory->post->create_many(10, array(
			'post_category' => array($this->cat1)
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
		unset($ret, $a, $b);
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
		unset($ret);
	}

	function test_largo_scrub_sticky_posts() {
		// If a post is marked as sticky, this unsticks any other posts on the blog so that there is only one sticky post at a time.
		// create some posts
		// run posts through this filter
		// count return, should be one
		$this->markTestIncomplete('This test has not been implemented yet.');
		// This should not ever break, and if it does, #blamenacin
	}

	function test_largo_have_featured_posts() {
		// This is on a page that is not category, taxonomy or tag
		$ret = largo_have_featured_posts();
		$this->assertFalse($ret);

		// This is on the "cat1" category
		$this->go_to('?cat=' . $this->cat1);
		$ret = get_posts(array('category' => $this->cat1));
#		$ret = largo_have_featured_posts();
		var_log($ret); // it's not returning the posts I expect it to, but it's in the correct category.
		var_log("count: " . count($ret)); // This should be 14, it's actually 5.


		$this->assertTrue(is_category(), "This test is not currently running as a category page, which means that it is useless");

		// but posts should not be in $this->cat1_no_prom

		$this->markTestIncomplete('This test has not been implemented yet.');

		// Cleanup
		unset($ret);
	}

	function test_largo_have_homepage_featured_posts() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}
}
