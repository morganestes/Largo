<?php

/**
 * Show related tags and subcategories for each main category
 * Used on category.php to display a list of related terms
 *
 * @since 1.0
 */

function largo_get_related_topics_for_category( $obj ) {
    $MAX_RELATED_TOPICS = 5;

    if (!isset($obj->post_type)) {
    	$obj->post_type = 0;
    }

    if ( $obj->post_type ) {
        if ( $obj->post_type == 'nav_menu_item' ) {
            $cat_id = $obj->object_id;
        }

    }else {
    $cat_id = $obj->cat_ID;
    }

    $out = "<ul>";

    // spit out the subcategories
    $cats = _subcategories_for_category( $cat_id );

    foreach ( $cats as $c ) {
        $out .= sprintf( '<li><a href="%s">%s</a></li>',
            get_category_link( $c->term_id ), $c->name
        );
    }

    if ( count( $cats ) < $MAX_RELATED_TOPICS ) {
        $tags = _tags_associated_with_category( $cat_id,
            $MAX_RELATED_TOPICS - count( $cats ) );

        foreach ( $tags as $t ) {
            $out .= sprintf( '<li><a href="%s">%s</a></li>',
                get_tag_link( $t->term_id ), $t->name
            );
        }
    }

    $out .= "</ul>";
    return $out;
}

function _tags_associated_with_category( $cat_id, $max = 5 ) {
    $query = new WP_Query( array(
        'posts_per_page' => -1,
        'cat' => $cat_id,
    ) );

    // Get a list of the tags used in posts in this category.
    $tags = array();
    $tag_objs = array();

    foreach ( $query->posts as $post ) {
        $ptags = get_the_tags( $post->ID );
        if ( $ptags ) {
            foreach ( $ptags as $tag ) {
                if (isset($tags[$tag->term_id])) {
                	$tags[ $tag->term_id ]++;
                } else {
                	$tags[ $tag->term_id ] = 0;
                }
                $tag_objs[ $tag->term_id ] = $tag;
            }
        }
    }

    // Sort the most popular and get the $max results, or all results
    // if max is -1
    arsort( $tags, SORT_NUMERIC );
    if ( $max == -1 ) {
        $tag_keys = array_keys( $tags );
    }
    else {
        $tag_keys = array_splice( array_keys( $tags ), 0, $max );
    }

    // Create an array of the selected tag objects
    $return_tags = array();
    foreach ( $tag_keys as $tk ) {
        array_push( $return_tags, $tag_objs[ $tk ] );
    }

    return $return_tags;
}

function _subcategories_for_category( $cat_id ) {
    // XXX: could also use get_term_children().  not sure which is better.
    $cats = get_categories( array(
        'child_of' => $cat_id,
    ) );

    return $cats;
}

/**
 * Provides topics (categories and tags) related to the current post in The
 * Loop.
 *
 * @param int $max The maximum number of topics to return.
 * @return array of term objects.
 * @since 1.0
 */
function largo_get_post_related_topics( $max = 5 ) {
    $cats = get_the_category();
    $tags = get_the_tags();

    $topics = array();
    if ( $cats ) {
        foreach ( $cats as $cat ) {
            if ( $cat->name == 'Uncategorized' ) {
                continue;
            }
            $posts = largo_get_recent_posts_for_term( $cat, 3, 2 );
            if ( $posts ) {
                $topics[] = $cat;
            }
        }
    }

    if ( $tags ) {
        foreach ( $tags as $tag ) {
            $posts = largo_get_recent_posts_for_term( $tag, 3, 2 );
            if ( $posts ) {
                $topics[] = $tag;
            }
        }
    }

    $topics = apply_filters( 'largo_get_post_related_topics', $topics, $max );

    return array_slice( $topics, 0, $max );
}

/**
 * Provides the recent posts for a term object (category, post_tag, etc).
 * @uses global $post
 * @param object    $term   A term object.
 * @param int       $max    Maximum number of posts to return.
 * @param int       $min    Minimum number of posts. If not met, returns false.
 * @return array|false of post objects.
 * @since 1.0
 */
function largo_get_recent_posts_for_term( $term, $max = 5, $min = 1 ) {
    global $post;

    $query_args = array(
        'showposts' 			=> $max,
        'orderby' 				=> 'date',
        'order' 				=> 'DESC',
        'ignore_sticky_posts' 	=> 1,
    );

    // Exclude the current post if we're inside The Loop
    if ( $post ) {
        $query_args[ 'post__not_in' ] = array( $post->ID );
    }

    if ( $term->taxonomy == 'post_tag' ) {
        // have to use tag__in because tag_id doesn't seem to work.
        $query_args[ 'tag__in' ] = array( $term->term_id );
    }
    elseif ( $term->taxonomy == 'category' ) {
        $query_args[ 'cat' ] = $term->term_id;
    }
    elseif ( $term->taxonomy == 'series' ) {
        $query_args[ 'series' ] = $term->slug;
    }

    $query_args = apply_filters( 'largo_get_recent_posts_for_term_query_args', $query_args, $term, $max, $min, $post );

    $query = new WP_Query( $query_args );

    if ( count( $query->posts ) < $min ) {
        return false;
    }

    return $query->posts;
}

/**
 * Determine if a post has either categories or tags
 *
 * @return bool true is a post has categories or tags
 * @since 1.0
 */
function largo_has_categories_or_tags() {
    if ( get_the_tags() ) {
        return true;
    }

    $cats = get_the_category();
    if ( $cats ) {
        foreach ( $cats as $cat ) {
            if ( $cat->name != 'Uncategorized' ) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Return (or echo) a list of categories and tags
 *
 * @param $max int number of categories and tags to return
 * @param $echo bool echo the output or return it (default: echo)
 * @param $link bool return the tags and category links or just the terms themselves
 * @param $use_icon bool include the tag icon or not (used on single.php)
 * @param $separator string to use as a separator between list items
 * @param $item_wrapper string html tag to use as a wrapper for elements in the output
 * @param $exclude array of term ids to exclude
 * @return array of category and tag links
 * @since 1.0
 * @todo consider prioritizing tags by popularity?
 */
if ( ! function_exists( 'largo_categories_and_tags' ) ) {
	function largo_categories_and_tags( $max = 5, $echo = true, $link = true, $use_icon = false, $separator = ', ', $item_wrapper = 'span', $exclude = array() ) {
	    $cats = get_the_category();
	    $tags = get_the_tags();
	    $icon = '';
	    $output = array();

	    // if $use_icon is true, include the markup for the tag icon
	    if ( $use_icon )
	    	$icon = '<i class="icon-white icon-tag"></i>';

	    if ( $cats ) {
	        foreach ( $cats as $cat ) {

	            // skip uncategorized and any others in the array of terms to exclude
	            if ( $cat->name == 'Uncategorized' || in_array( $cat->term_id, $exclude ) )
	                continue;

	            if ( $link ) {
		            $output[] = sprintf(
		                __('<%1$s class="post-category-link"><a href="%2$s" title="Read %3$s in the %4$s category">%5$s%4$s</a></%1$s>', 'largo'),
			                $item_wrapper,
			                get_category_link( $cat->term_id ),
			                of_get_option( 'posts_term_plural' ),
			                $cat->name,
			                $icon
		            );
		       } else {
			       $output[] = $cat->name;
		       }
	        }
	    }

	    if ( $tags ) {
	        foreach ( $tags as $tag ) {

	        	if ( in_array( $tag->term_id, $exclude ) )
	                continue;

	        	if ( $link ) {
		            $output[] = sprintf(
		                __('<%1$s class="post-tag-link"><a href="%2$s" title="Read %3$s tagged with: %4$s">%5$s%4$s</a></%1$s>', 'largo'),
		                	$item_wrapper,
		                	get_tag_link( $tag->term_id ),
		                	of_get_option( 'posts_term_plural' ),
		                	$tag->name,
		                	$icon
		            );
		         } else {
		         	 $output[] = $tag->name;
		       }
	        }
	    }

	    if ( $echo )
			echo implode( $separator, array_slice( $output, 0, $max ) );

		return $output;
	}
}

/**
 *
 */
function largo_filter_get_post_related_topics( $topics, $max ) {
    $post = get_post();

    if ( $post ) {
        $posts = preg_split( '#\s*,\s*#', get_post_meta( $post->ID, 'largo_custom_related_posts', true ) );

        if ( !empty( $posts ) ) {
            // Add a fake term with the ID of -90
            $top_posts = new stdClass();
            $top_posts->term_id = -90;
            $top_posts->name = __( 'Top Posts', 'largo' );
            array_unshift( $topics, $top_posts );
        }
    }

    return $topics;
}
add_filter( 'largo_get_post_related_topics', 'largo_filter_get_post_related_topics', 10, 2 );


/**
 *
 */
function largo_filter_get_recent_posts_for_term_query_args( $query_args, $term, $max, $min, $post ) {

    if ( $term->term_id == -90 ) {
        $posts = preg_split( '#\s*,\s*#', get_post_meta( $post->ID, 'largo_custom_related_posts', true ) );
        $query_args = array(
            'showposts'             => $max,
            'orderby'               => 'post__in',
            'order'                 => 'ASC',
            'ignore_sticky_posts'   => 1,
            'post__in'              => $posts,
        );
    }

    return $query_args;
}
add_filter( 'largo_get_recent_posts_for_term_query_args', 'largo_filter_get_recent_posts_for_term_query_args', 10, 5 );


/**
 * Get N post IDs related to the current post
 * Orders by: manual > next in series > next in category > next in tag > next recent post from any category
 */
function largo_get_related_posts_for_post( $post_id, $number = 1 ) {

	//see if this post has manually set related posts
	$post_ids = get_post_meta( $post_id, '_largo_custom_related_posts', true );
	if ( ! empty( $post_ids ) ) {
		$post_ids = explode( ",", $post_ids );
		if ( count( $post_ids ) >= $number ) {
			return array_slice( $post_ids, 0, $number );
		}
	} else {
		$post_ids = array();
	}

	//try to get posts by series, if this post is in a series
	$series = get_the_terms( $post_id, 'series' );
	if ( count($series) ) {

		//loop thru all the series this post belongs to
		foreach ( $series as $term ) {

			//start to build our query of posts in this series
			// get the posts in this series, ordered by rank or (if missing?) date
			$args = array(
				'post_type' => 'post',
				'posts_per_page' => 20,	//should usually be enough
				'taxonomy' 			=> 'series',
				'term' => $term->slug,
				'orderby' => 'date',
				'order' => 'DESC',
			);

			// see if there's a post that has the sort order info for this series
			$pq = new WP_Query( array(
				'post_type' => 'cftl-tax-landing',
				'series' => $term->slug,
				'posts_per_page' => 1
			));

			if ( $pq->have_posts() ) {
				$pq->next_post();
				$has_order = get_post_meta( $pq->post->ID, 'post_order', TRUE );
				if ( !empty($has_order) ) {
					switch ( $has_order ) {
						case 'ASC':
							$args['order'] = 'ASC';
							break;
						case 'custom':
							$args['orderby'] = 'series_custom';
							break;
						case 'featured, DESC':
						case 'featured, ASC':
							$args['orderby'] = $opt['post_order'];
							break;
					}
				}
			}

			// build the query with the sort defined
			$series_query = new WP_Query( $args );
			if ( $series_query->have_posts() ) {

				//flip our results
				//$series_query->posts = array_reverse($series_query->posts);
				//$series_query->rewind_posts();
				_largo_related_add_from_query( $series_query, $post_id, $post_ids, $number);

				//are we done yet?
				if ( count($post_ids) == $number ) return $post_ids;
			}
		}
	}

	error_log("on " . __LINE__);

	//we've gone back and forth through all the post's series, now let's try traditional taxonomies
	$taxonomies = get_the_terms( $post_id, array('category', 'post_tag') );

	//loop thru taxonomies, much like series, and get posts
	if ( count($taxonomies) ) {
		//sort by popularity
		usort( $taxonomies, 'largo_sort_terms_by_popularity' );

		foreach ( $taxonomies as $term ) {
			$args = array(
				'post_type' => 'post',
				'posts_per_page' => 20,	//should usually be enough
				'taxonomy' 			=> $term->taxonomy,
				'term' => $term->slug,
				'orderby' => 'date',
				'order' => 'DESC',
			);
		}
		// run the query
		$term_query = new WP_Query( $args );

		if ( $term_query->have_posts() ) {
			_largo_related_add_from_query( $term_query, $post_id, $post_ids, $number);

			//are we done yet?
			if ( count($post_ids) == $number ) return $post_ids;
		}
	}

	error_log("on " . __LINE__);

	// Good heavens, we haven't found anything yet? Criminy!
	// Fine, let's just grab recent posts
	$args = array(
		'post_type' => 'post',
		'posts_per_page' => $number,
		'post__not_in' => array( $post_id ),
	);

	$posts_query = new WP_Query( $args );

	error_log("on " . __LINE__);

	if ( $posts_query->have_posts() ) {
		while ( $posts_query->next_post() ) {
			if ( !in_array($posts_query->post->ID, $post_ids) ) $post_ids[] = $posts_query->post->ID;
		}
	}

	return $post_ids;

}

function _largo_related_add_from_query( $q, $pid, &$post_ids, $max_length, $reversed = FALSE ) {
	// don't pick up anything until we're past our own post
	$found_ours = FALSE;

	while ( $q->have_posts() ) {
		$q->next_post();
		//don't show our post, but record that we've found it
		if ( $q->post->ID == $pid ) {
			$found_ours = TRUE;
			continue;
		} else if ( ! $found_ours ) {
			continue;	// don't add any posts until we're adding posts newer than the one being displayed
		} else if ( ! in_array($q->post->ID, $post_ids ) ) {	// only add it if it wasn't already there
			$post_ids[] = $q->post->ID;
			if ( count($post_ids) == $max_length ) return;
		}
	}

	//still here? reverse and try again
	if ( ! $reversed ) {
		$q->posts = array_reverse($q->posts);
		$q->rewind_posts();
		_largo_related_add_from_query( $q, $pid, $post_ids, $max_length, TRUE );
	}
}

function largo_sort_terms_by_popularity( $a, $b ) {
	if ( $a->count == $b->count ) return 0;
	return ( $a->count < $b->count ) ? -1 : 1;
}