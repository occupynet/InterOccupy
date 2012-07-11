<?php
/*
Plugin Name: Network Latest Posts
Plugin URI: http://en.8elite.com/2012/02/27/network-latest-posts-wordpress-3-plugin/
Description: This plugin allows you to list the latest posts from the blogs in your network and display them in your site using shortcodes or as a widget. Based in the WPMU Recent Posts Widget by Angelo (http://bitfreedom.com/)
Version: 2.0.4
Author: L'Elite
Author URI: https://laelite.info/
*/
/*  Copyright 2012  L'Elite (email : opensource@laelite.info)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/*
Parameter explanations
$how_many: how many recent posts are being displayed
$how_long: time frame to choose recent posts from (in days), set to 0 to disable
$titleOnly: true (only title of post is displayed) OR false (title of post and name of blog are displayed)
$begin_wrap: customise the start html code to adapt to different themes
$end_wrap: customise the end html code to adapt to different themes
$blog_id: allows you to retrieve the data for a specific blog
$thumbnail: allows you to display the post's thumbnail (shortcode only)
$cpt: allows you to display a custom post type (post, page, etc) (shortcode and widget)
$ignore_blog: allows you to ignore one or various blogs using their ID numbers (shortcode and widget)
$cat: allows you to display posts by one or more categories (separated by commas) (null by default)
$tag: allows you to display posts by one or more tags (separated by commas) (null by default)
$paginate: allows you to paginate the results, it will use the number parameter as the number of results to display by page 
 * // If you paginate the widget results, you will have to tweak CSS to display the links correctly because of the nested lists
 * // You've been warned ;)
$excerpt_length: allows you to limit the length of the excerpt string, for example: set it to 200 to display 200 characters (null by default)
$display_root: allows you to display the posts published in the main blog (root) (false by default)
$auto_excerpt: will generate an excerpt for each article listed from the content of the post (false by default)
$full_meta: will display the author's display name, the date and time when the post was published

Sample call: network_latest_posts(5, 30, true, '<li>', '</li>'); >> 5 most recent entries over the past 30 days, displaying titles only

Sample Shortcode: [nlposts title='Latest Posts' number='2' days='30' titleonly=false wrapo='<div>' wrapc='</div>' blogid=null thumbnail=false cpt=post ignore_blog=null cat=null tag=null paginate=false excerpt_length=null display_root=false auto_excerpt=false full_meta=false]
 * title = the section's title null by default
 * number = number of posts to display by blog 10 by default
 * days = time frame to choose recent posts from (in days) 0 by default
 * titleonly = if false it will display the title and the excerpt for each post true by default
 * wrapo = html opening tag to wrap the output (for styling purposes) null by default
 * wrapc = html closing tag to wrap the output (for styling purposes) null by default
 * blogid = the id of the blog for which you want to display the latest posts null by default
 * thumbnail = allows you to display the thumbnail (featured image) for each post it can be true or false (false by default)
 * cpt = custom post type, it allows you to display a specific post type (post, page, etc) (post by default)
 * ignore_blog = this parameter allows you to ignore one or a list of IDs separated by commas (null by default)
 * cat = this parameter allows you to display posts by one or more categories (separated by commas) (null by default)
 * tag = this parameter allows you to display posts by one or more tags (separated by commas) (null by default)
 * paginate = this parameter allows you to paginate the results, it will use the number parameter as the number of results to display by page
 * excerpt_length = this parameter allows you to limit the length of the excerpt string, for example: set it to 200 to display 200 characters (null by default)
 * display_root: allows you to display the posts published in the main blog (root) possible values: true or false (false by default)
 * auto_excerpt: will generate an excerpt for each article listed from the content of the post, possible values true or false (false by default)
 * full_meta: will display the author's display name, the date and time when the post was published, possible values true or false (false by default)
*/
/*
 * cpt & ignore_blog parameters were proposed by John Hawkins (9seeds.com)
 * 
 * Thanks for the patches, I did some tweaks to your code but it's basically the same idea
 * I also could spot some bugs I didn't fix the last time I updated the plugin
 * and improved the Widget lists because the <ul></ul> tags where repeating, now it's finally fixed I think
 * 
 * Pagination feature proposed by Davo
 * 
 * Taxonomy filters (categories and tags) proposed by Jenny Beaumont
 * 
 * Excerpt Length proposed by Tim (trailsherpa.com)
 * 
 */
function network_latest_posts($how_many=10, $how_long=0, $titleOnly=true, $begin_wrap="\n<li>", $end_wrap="</li>", $blog_id='null', $thumbnail=false, $cpt="post", $ignore_blog='null', $cat='null', $tag='null', $paginate=false, $excerpt_length='null', $display_root=false, $auto_excerpt=false, $full_meta=false) {
	global $wpdb;
	global $table_prefix;
	$counter = 0;
        $hack_cont = 0;
        // Custom post type
        $cpt = htmlspecialchars($cpt);
        // Ignore blog or blogs
        // if the user passes one value
        if( !preg_match("/,/",$ignore_blog) ) {
            // Always clean this stuff ;)
            $ignore_blog = htmlspecialchars($ignore_blog);
            // Check if it's numeric
            if( is_numeric($ignore_blog) ) {
                // and put the sql
                $ignore = " AND blog_id != $ignore_blog ";
            }
        // if the user passes more than one value separated by commas
        } else {
            // create an array
            $ignore_arr = explode(",",$ignore_blog);
            // and repeat the sql for each ID found
            for($z=0;$z<count($ignore_arr);$z++){
                $ignore .= " AND blog_id != $ignore_arr[$z]";
            }
        }
	// get a list of blogs in order of most recent update. show only public and nonarchived/spam/mature/deleted
	if ($how_long > 0) {
                // Select by blog id
                if( !empty($blog_id) && $blog_id != 'null' ) {
                    $blog_id = htmlspecialchars($blog_id);
                    $blogs = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs WHERE
                    public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0'
                    AND blog_id = $blog_id $ignore AND last_updated >= DATE_SUB(CURRENT_DATE(), INTERVAL $how_long DAY)
                    ORDER BY last_updated DESC");
                } else {
                    $blogs = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs WHERE
                    public = '1' $ignore AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0'
                    AND last_updated >= DATE_SUB(CURRENT_DATE(), INTERVAL $how_long DAY)
                    ORDER BY last_updated DESC");                    
                }
	} else {
                if( !empty($blog_id) && $blog_id != 'null' ) {
                    $blog_id = htmlspecialchars($blog_id);
                    $blogs = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs WHERE
			public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0' AND blog_id = $blog_id
			$ignore ORDER BY last_updated DESC");
                } else {
                    $blogs = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs WHERE
			public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0'
			$ignore ORDER BY last_updated DESC");                   
                }
	}

	if ($blogs) {
                // Count how many blogs we've found
                $nblogs = count($blogs);
                // Lets dig into each blog
		foreach ($blogs as $blognlp) {
			// we need _posts and _options tables for this to work
                        // Get the options table for each blog
                        if( $display_root == true ) {
                            if( $blognlp == 1 ) {
                                $blogOptionsTable = $wpdb->base_prefix."options";
                                // Get the posts table for each blog
                                $blogPostsTable = $wpdb->base_prefix."posts";
                                // Get the terms relationships table for each blog
                                $blogTermRelationship = $wpdb->base_prefix."term_relationships";
                                // Get the term taxonomy table for each blog
                                $blogTermTaxonomy = $wpdb->base_prefix."term_taxonomy";
                                // Get the terms table for each blog
                                $blogTerms = $wpdb->base_prefix."terms";
                            } else {
                                $blogOptionsTable = $wpdb->base_prefix.$blognlp."_options";
                                // Get the posts table for each blog
                                $blogPostsTable = $wpdb->base_prefix.$blognlp."_posts";
                                // Get the terms relationships table for each blog
                                $blogTermRelationship = $wpdb->base_prefix.$blognlp."_term_relationships";
                                // Get the term taxonomy table for each blog
                                $blogTermTaxonomy = $wpdb->base_prefix.$blognlp."_term_taxonomy";
                                // Get the terms table for each blog
                                $blogTerms = $wpdb->base_prefix.$blognlp."_terms";
                            }
                        } else {
                            $blogOptionsTable = $wpdb->base_prefix.$blognlp."_options";
                            // Get the posts table for each blog
                            $blogPostsTable = $wpdb->base_prefix.$blognlp."_posts";
                            // Get the terms relationships table for each blog
                            $blogTermRelationship = $wpdb->base_prefix.$blognlp."_term_relationships";
                            // Get the term taxonomy table for each blog
                            $blogTermTaxonomy = $wpdb->base_prefix.$blognlp."_term_taxonomy";
                            // Get the terms table for each blog
                            $blogTerms = $wpdb->base_prefix.$blognlp."_terms";
                        }
                        // --- Because the categories and tags are handled the same way by WP
                        // --- I'm hacking the $cat variable so I can use it for both without
                        // --- repeating the code
                        if( !empty($cat) && $cat != 'null' && (empty($tag) || $tag == 'null') ) {         // Categories
                            $cat_hack = $cat;
                            $taxonomy = "taxonomy = 'category'";
                        } elseif( !empty($tag) && $tag != 'null' && (empty($cat) || $cat == 'null') ) {   // Tags
                            $cat_hack = $tag;
                            $taxonomy = "taxonomy = 'post_tag'";
                        } elseif( !empty($cat) && $cat != 'null' && !empty($tag) && $tag != 'null' ) {  // Categories & Tags
                            $cat_hack = $cat.",".$tag;
                            $taxonomy = "(taxonomy = 'category' OR taxonomy = 'post_tag')";
                        }
                        // --- Categories
                        if( !empty($cat_hack) && $cat_hack != 'null' ) {
                            if( !preg_match('/,/',$cat_hack) ) {
                                $cat_hack = htmlspecialchars($cat_hack);
                                // Get the category's ID
                                $catid = $wpdb->get_results("SELECT term_id FROM $blogTerms WHERE slug = '$cat_hack'");
                                $cats{$blognlp} = $catid[0]->term_id;
                            } else {
                                $cat_arr = explode(',',$cat_hack);
                                for($x=0;$x<count($cat_arr);$x++){
                                    $cat_ids = $wpdb->get_results("SELECT term_id FROM $blogTerms WHERE slug = '$cat_arr[$x]' ");
                                    if( !empty($cat_ids[0]->term_id) ) {
                                        // Get the categories' IDs
                                        $catsa{$blognlp}[] = $cat_ids[0]->term_id;
                                    }
                                }
                            }
                        }
                        // Let's find the ID for the category(ies) or tag(s) 
                        if( count($cats{$blognlp}) == 1 ) {
                            $taxo = $wpdb->get_results("SELECT term_taxonomy_id FROM $blogTermTaxonomy WHERE $taxonomy AND term_id = ".$cats{$blognlp});
                            $taxs{$blognlp} = $taxo[0]->term_taxonomy_id;
                        } elseif( count($catsa{$blognlp}) >= 1 ) {
                            for( $y = 0; $y < count($catsa{$blognlp}); $y++ ) {
                                $tax_id = $wpdb->get_results("SELECT term_taxonomy_id FROM $blogTermTaxonomy WHERE $taxonomy AND term_id = ".$catsa{$blognlp}[$y]);
                                if( !empty($tax_id[0]->term_taxonomy_id) ) {
                                    $taxsa{$blognlp}[] = $tax_id[0]->term_taxonomy_id;
                                }
                            }
                        }
                        // Next, let's find how they are related to the posts
                        if( count($taxs{$blognlp}) == 1 ) {
                            $pids = $wpdb->get_results("SELECT object_id FROM $blogTermRelationship WHERE term_taxonomy_id = ".$taxs{$blognlp});
                            for( $w=0;$w<count($pids);$w++ ) {
                                $postids{$blognlp}[] = $pids[$w]->object_id;
                            }
                        } elseif( count($taxsa{$blognlp}) >= 1 ) {
                            for( $w = 0; $w < count($taxsa{$blognlp}); $w++ ) {
                                $p_id = $wpdb->get_results("SELECT object_id FROM $blogTermRelationship WHERE term_taxonomy_id = ".$taxsa{$blognlp}[$w]);
                                for( $q = 0; $q < count($p_id); $q++ ){
                                    $postidsa{$blognlp}[] = $p_id[$q]->object_id;
                                }
                            }
                        }
                        // Finally let's find the posts' IDs
                        if( count($postids{$blognlp}) == 1 ) {
                            $filter_cat = " AND ID = ".$postids{$blognlp};
                            if(!empty($filter_cat)) {
                                if( !preg_match('/\(/',$filter_cat) ) {
                                    $needle = ' AND ';
                                    $replacement = ' AND (';
                                    $filter_cat = str_replace($needle, $replacement, $filter_cat);
                                }
                            }
                        } elseif( count($postids{$blognlp}) > 1 ) {
                            for( $v = 0; $v < count($postids{$blognlp}); $v++ ) {
                                if( $v == 0 && $hack_cont == 0 ) {
                                    $filter_cat .= " AND ID = ".$postids{$blognlp}[$v];
                                    $hack_cont++;
                                } elseif( $hack_cont > 0 ) {
                                    $filter_cat .= " OR ID = ".$postids{$blognlp}[$v];
                                }
                            }
                            if(!empty($filter_cat)) {
                                if( !preg_match('/\(/',$filter_cat) ) {
                                    $needle = ' AND ';
                                    $replacement = ' AND (';
                                    $filter_cat = str_replace($needle, $replacement, $filter_cat);
                                }
                            }
                        } elseif( count($postidsa{$blognlp}) >= 1 ) {
                            for( $v = 0; $v < count($postidsa{$blognlp}); $v++ ) {
                                if( $v == 0 && $hack_cont == 0 ) {
                                    $filter_cat .= " AND ID = ".$postidsa{$blognlp}[$v];
                                    $hack_cont++;
                                } elseif( $hack_cont > 0 ) {
                                    $filter_cat .= " OR ID = ".$postidsa{$blognlp}[$v];
                                }
                            }
                            if(!empty($filter_cat)) {
                                if( !preg_match('/\(/',$filter_cat) ) {
                                    $needle = ' AND ';
                                    $replacement = ' AND (';
                                    $filter_cat = str_replace($needle, $replacement, $filter_cat);
                                }
                            }
                        }
                        // --- Categories\\
                        // Get the saved options
			$options = $wpdb->get_results("SELECT option_value FROM
				$blogOptionsTable WHERE option_name IN ('siteurl','blogname','links_updated_date_format') 
				ORDER BY option_name DESC");
		        // we fetch the title, excerpt and ID for the latest post
			if ($how_long > 0) {
                                if( !empty( $filter_cat ) && !empty($cat_hack) ) {
                                    // Without pagination
                                    if( !$paginate ) {
                                        $thispost = $wpdb->get_results("SELECT ID, post_title, post_excerpt, post_content, post_author, post_date
                                                FROM $blogPostsTable WHERE post_status = 'publish'
                                                $filter_cat )
                                                AND post_type = '$cpt'
                                                AND post_date >= DATE_SUB(CURRENT_DATE(), INTERVAL $how_long DAY)
                                                ORDER BY id DESC LIMIT 0,$how_many");
                                    // Paginated results
                                    } else {
                                        $posts_per_page = $how_many;
                                        $page = isset( $_GET['pnum'] ) ? abs( (int) $_GET['pnum'] ) : 1;
                                        $total_records = $wpdb->get_var("SELECT COUNT(ID)
                                                FROM $blogPostsTable WHERE post_status = 'publish'
                                                $filter_cat )
                                                AND post_type = '$cpt'
                                                AND post_date >= DATE_SUB(CURRENT_DATE(), INTERVAL $how_long DAY)
                                                ORDER BY id DESC");
                                        $total = $total_records;
                                        $thispost = $wpdb->get_results("SELECT ID, post_title, post_excerpt, post_content, post_author, post_date
                                                FROM $blogPostsTable WHERE post_status = 'publish'
                                                $filter_cat )
                                                AND post_type = '$cpt'
                                                AND post_date >= DATE_SUB(CURRENT_DATE(), INTERVAL $how_long DAY)
                                                ORDER BY id DESC LIMIT ".(($page * $posts_per_page) - $posts_per_page) .",$posts_per_page");
                                    }
                                } elseif( empty( $filter_cat ) && empty($cat_hack) ) {
                                    // Without pagination
                                    if( !$paginate ) {
                                        $thispost = $wpdb->get_results("SELECT ID, post_title, post_excerpt, post_content, post_author, post_date
                                            FROM $blogPostsTable WHERE post_status = 'publish'
                                            AND ID > 1
                                            AND post_type = '$cpt'
                                            AND post_date >= DATE_SUB(CURRENT_DATE(), INTERVAL $how_long DAY)
                                            ORDER BY id DESC LIMIT 0,$how_many");
                                    // Paginated results
                                    } else {
                                        $posts_per_page = $how_many;
                                        $page = isset( $_GET['pnum'] ) ? abs( (int) $_GET['pnum'] ) : 1;
                                        $total_records = $wpdb->get_var("SELECT COUNT(ID)
                                                FROM $blogPostsTable WHERE post_status = 'publish'
                                                AND ID > 1
                                                AND post_type = '$cpt'
                                                AND post_date >= DATE_SUB(CURRENT_DATE(), INTERVAL $how_long DAY)
                                                ORDER BY id DESC");
                                        $total = $total_records;
                                        $thispost = $wpdb->get_results("SELECT ID, post_title, post_excerpt, post_content, post_author, post_date
                                                FROM $blogPostsTable WHERE post_status = 'publish'
                                                AND ID > 1
                                                AND post_type = '$cpt'
                                                AND post_date >= DATE_SUB(CURRENT_DATE(), INTERVAL $how_long DAY)
                                                ORDER BY id DESC LIMIT ".(($page * $posts_per_page) - $posts_per_page) .",$posts_per_page");
                                    }
                                }
			} else {
                                if( !empty( $filter_cat ) && !empty($cat_hack) ) {
                                    // Without pagination
                                    if( !$paginate ) {
                                        $thispost = $wpdb->get_results("SELECT ID, post_title, post_excerpt, post_content, post_author, post_date
                                                FROM $blogPostsTable WHERE post_status = 'publish'
                                                $filter_cat )
                                                AND post_type = '$cpt'
                                                ORDER BY id DESC LIMIT 0,$how_many");
                                    // Paginated results
                                    } else {
                                        $posts_per_page = $how_many;
                                        $page = isset( $_GET['pnum'] ) ? abs( (int) $_GET['pnum'] ) : 1;
                                        $total_records = $wpdb->get_var("SELECT COUNT(ID)
                                                FROM $blogPostsTable WHERE post_status = 'publish'
                                                $filter_cat )
                                                AND post_type = '$cpt'
                                                ORDER BY id DESC");
                                        $total = $total_records;
                                        $thispost = $wpdb->get_results("SELECT ID, post_title, post_excerpt, post_content, post_author, post_date
                                                FROM $blogPostsTable WHERE post_status = 'publish'
                                                $filter_cat )
                                                AND post_type = '$cpt'
                                                ORDER BY id DESC LIMIT ".(($page * $posts_per_page) - $posts_per_page) .",$posts_per_page");
                                    }
                                } elseif( empty( $filter_cat ) && empty($cat_hack) ) {
                                    // Without pagination
                                    if( !$paginate ) {
                                        $thispost = $wpdb->get_results("SELECT ID, post_title, post_excerpt, post_content, post_author, post_date
                                                FROM $blogPostsTable WHERE post_status = 'publish'
                                                AND ID > 1
                                                AND post_type = '$cpt'
                                                ORDER BY id DESC LIMIT 0,$how_many");
                                    } else {
                                        $posts_per_page = $how_many;
                                        $page = isset( $_GET['pnum'] ) ? abs( (int) $_GET['pnum'] ) : 1;
                                        $total_records = $wpdb->get_var("SELECT COUNT(ID)
                                                FROM $blogPostsTable WHERE post_status = 'publish'
                                                AND ID > 1
                                                AND post_type = '$cpt'
                                                ORDER BY id DESC");
                                        $total = $total_records;
                                        $thispost = $wpdb->get_results("SELECT ID, post_title, post_excerpt, post_content, post_author, post_date
                                                FROM $blogPostsTable WHERE post_status = 'publish'
                                                AND ID > 1
                                                AND post_type = '$cpt'
                                                ORDER BY id DESC LIMIT ".(($page * $posts_per_page) - $posts_per_page) .",$posts_per_page");
                                    }
                                }
			}
			// if it is found put it to the output
			if($thispost) {
                                // Remember we are doing this for multiple blogs?, well we need to display
                                // the number of posts chosen for each of them
                                for($i=0; $i < count($thispost); $i++) {
                                    // get permalink by ID.  check wp-includes/wpmu-functions.php
                                    $thispermalink = get_blog_permalink($blognlp, $thispost[$i]->ID);
                                    // If we want to show the excerpt, we do this
                                    if ($titleOnly == false || $titleOnly == 'false') {
                                        // Widget list
                                        if( ( !empty($begin_wrap) || $begin_wrap != '' ) && preg_match("/\bli\b/",$begin_wrap) && $thumbnail == false ) { 
                                            echo $begin_wrap.'<div class="network-posts blog-'.$blognlp.'"><h4 class="srp-post-title"><a href="'
                                            .$thispermalink.'">'.$thispost[$i]->post_title.'</a></h4><span class="network-posts-source"> '.__('published in','trans-nlp').' <a href="'
                                            .$options[0]->option_value.'">'
                                            .$options[2]->option_value.'</a>';
                                            // Full metadata
                                            if( !empty($full_meta) && $full_meta == 'true' ){
                                                $format = (string)$options[1]->option_value;
                                                $dateobj = new DateTime(trim($thispost[$i]->post_date));
                                                $datepost = $dateobj->format("$format");
                                                echo ' ' . __('by','trans-nlp'). ' ' . get_the_author_meta( 'display_name' , $thispost[$i]->post_author ) . ' ' . __('on','trans-nlp') . ' ' . $datepost;
                                            }
                                            echo '</span><p class="network-posts-excerpt">';
                                            if( $auto_excerpt == false ) {
                                                echo custom_excerpt($excerpt_length, $thispost[$i]->post_excerpt, $thispermalink);
                                            } else {
                                                $auto_excerpt = auto_excerpt($thispost[$i]->post_content, $excerpt_length, $thispermalink);
                                                if( preg_match( "/\[caption/", $auto_excerpt ) == true ) {
                                                    echo do_shortcode($auto_excerpt);
                                                } else {
                                                    echo custom_excerpt($excerpt_length, $auto_excerpt, $thispermalink);
                                                }
                                            }
                                            echo '</p></div>'.$end_wrap;
                                        // Shortcode
                                        } else {
                                            // Display thumbnail
                                            if( $thumbnail ) {
                                                if( $i == 0 ) {
                                                    echo '<div id="wrapper-'.$blognlp.'">';
                                                }
                                                echo $begin_wrap.'<div class="network-posts blog-'.$blognlp.'"><h4 class="network-posts-title"><a href="'.$thispermalink.'">'.$thispost[$i]->post_title.'</a></h1><span class="network-posts-source"> '.__('published in','trans-nlp').' <a href="'
                                                .$options[0]->option_value.'">'
                                                .$options[2]->option_value.'</a>';
                                                // Full metadata
                                                if( !empty($full_meta) && $full_meta == 'true' ){
                                                    $format = (string)$options[1]->option_value;
                                                    $dateobj = new DateTime(trim($thispost[$i]->post_date));
                                                    $datepost = $dateobj->format("$format");
                                                    echo ' ' . __('by','trans-nlp'). ' ' . get_the_author_meta( 'display_name' , $thispost[$i]->post_author ) . ' ' . __('on','trans-nlp') . ' ' . $datepost;
                                                }
                                                echo '</span><a href="'
                                                .$thispermalink.'">'.the_post_thumbnail_by_blog($blognlp,$thispost[$i]->ID).'</a> <p class="network-posts-excerpt">';
                                                if( $auto_excerpt == false ) {
                                                    echo custom_excerpt($excerpt_length, $thispost[$i]->post_excerpt, $thispermalink);
                                                } else {
                                                    $auto_excerpt = auto_excerpt($thispost[$i]->post_content, $excerpt_length, $thispermalink);
                                                    if( preg_match( "/\[caption/", $auto_excerpt ) == true ) {
                                                        echo do_shortcode($auto_excerpt);
                                                    } else {
                                                        echo custom_excerpt($excerpt_length, $auto_excerpt, $thispermalink);
                                                    }
                                                }
                                                echo '</p>';
                                                if( $i == (count($thispost)-1) && $paginate == true ) {
                                                    echo '<div class="network-posts-pagination">';
                                                    echo paginate_links( array(
                                                        'base' => add_query_arg( 'pnum', '%#%' ),
                                                        'format' => '',
                                                        'prev_text' => __('&laquo;'),
                                                        'next_text' => __('&raquo;'),
                                                        'total' => ceil($total / $posts_per_page),
                                                        'current' => $page,
                                                        'type' => 'list'
                                                    ));
                                                    echo '</div>';
                                                    echo 
                                                    '<script type="text/javascript" charset="utf-8">
                                                        jQuery(document).ready(function(){

                                                                jQuery(".blog-'.$blognlp.' .network-posts-pagination a").live("click", function(e){
                                                                        e.preventDefault();
                                                                        var link = jQuery(this).attr("href");
                                                                        jQuery("#wrapper-'.$blognlp.'").html("<img src=\"'.plugins_url('/img/loader.gif', __FILE__) .'\" />");
                                                                        jQuery("#wrapper-'.$blognlp.'").load(link+" .blog-'.$blognlp.'");

                                                                });

                                                        });
                                                    </script>';
                                                }
                                                echo "</div>".$end_wrap;
                                                if($i == (count($thispost)-1)){
                                                    echo "</div>";
                                                }
                                            // Without thumbnail
                                            } else {
                                                if( $i == 0 ) {
                                                    echo '<div id="wrapper-'.$blognlp.'">';
                                                }
                                                echo $begin_wrap.'<div class="network-posts blog-'.$blognlp.'"><h1 class="network-posts-title"><a href="'.
                                                $thispermalink.'">'.$thispost[$i]->post_title.'</a></h1><span class="network-posts-source"> '.__('published in','trans-nlp').' <a href="'
                                                .$options[0]->option_value.'">'
                                                .$options[2]->option_value.'</a>';
                                                // Full metadata
                                                if( !empty($full_meta) && $full_meta == 'true' ){
                                                    $format = (string)$options[1]->option_value;
                                                    $dateobj = new DateTime(trim($thispost[$i]->post_date));
                                                    $datepost = $dateobj->format("$format");
                                                    echo ' ' . __('by','trans-nlp'). ' ' . get_the_author_meta( 'display_name' , $thispost[$i]->post_author ) . ' ' . __('on','trans-nlp') . ' ' . $datepost;
                                                }
                                                echo '</span><p class="network-posts-excerpt">';
                                                if( $auto_excerpt == false ) {
                                                    echo custom_excerpt($excerpt_length, $thispost[$i]->post_excerpt, $thispermalink);
                                                } else {
                                                    $auto_excerpt = auto_excerpt($thispost[$i]->post_content, $excerpt_length, $thispermalink);
                                                    if( preg_match( "/\[caption/", $auto_excerpt ) == true ) {
                                                        echo do_shortcode($auto_excerpt);
                                                    } else {
                                                        echo custom_excerpt($excerpt_length, $auto_excerpt, $thispermalink);
                                                    }
                                                }
                                                echo '</p>';
                                                if( $i == (count($thispost)-1) && $paginate == true ) {
                                                    echo '<div class="network-posts-pagination">';
                                                    echo paginate_links( array(
                                                        'base' => add_query_arg( 'pnum', '%#%' ),
                                                        'format' => '',
                                                        'prev_text' => __('&laquo;'),
                                                        'next_text' => __('&raquo;'),
                                                        'total' => ceil($total / $posts_per_page),
                                                        'current' => $page,
                                                        'type' => 'list'
                                                    ));
                                                    echo '</div>';
                                                    echo 
                                                    '<script type="text/javascript" charset="utf-8">
                                                        jQuery(document).ready(function(){

                                                                jQuery(".blog-'.$blognlp.' .network-posts-pagination a").live("click", function(e){
                                                                        e.preventDefault();
                                                                        var link = jQuery(this).attr("href");
                                                                        jQuery("#wrapper-'.$blognlp.'").html("<img src=\"'.plugins_url('/img/loader.gif', __FILE__) .'\" />");
                                                                        jQuery("#wrapper-'.$blognlp.'").load(link+" .blog-'.$blognlp.'");

                                                                });

                                                        });
                                                    </script>';
                                                }
                                                echo "</div>".$end_wrap;
                                                if($i == (count($thispost)-1)){
                                                    echo "</div>";
                                                }
                                            }
                                        }
                                    // Otherwise we just show the titles (useful when used as a widget)
                                    } else {
                                        // Widget list
                                        if( $i == 0 ) {
                                            echo '<div id="wrapperw-'.$blognlp.'">';
                                        }
                                        if( preg_match("/\bli\b/",$begin_wrap) ) { 
                                            echo $begin_wrap.'<div class="network-posts blogw-'.$blognlp.'"><a href="'.$thispermalink
                                            .'">'.$thispost[$i]->post_title.'</a>';
                                            if( $i == (count($thispost)-1) && $paginate == true ) {
                                                echo '<div class="network-posts-pagination">';
                                                echo paginate_links( array(
                                                    'base' => add_query_arg( 'pnum', '%#%' ),
                                                    'format' => '',
                                                    'show_all' => false,
                                                    'prev_text' => __('&laquo;'),
                                                    'next_text' => __('&raquo;'),
                                                    'total' => ceil($total / $posts_per_page),
                                                    'current' => $page,
                                                    'type' => 'list'
                                                ));
                                                echo '</div>';
                                                echo 
                                                '<script type="text/javascript" charset="utf-8">
                                                    jQuery(document).ready(function(){

                                                            jQuery(".blogw-'.$blognlp.' .network-posts-pagination a").live("click", function(e){
                                                                    e.preventDefault();
                                                                    var link = jQuery(this).attr("href");
                                                                    jQuery("#wrapperw-'.$blognlp.'").html("<img src=\"'.plugins_url('/img/loader.gif', __FILE__) .'\" />");
                                                                    jQuery("#wrapperw-'.$blognlp.'").load(link+" .blogw-'.$blognlp.'");

                                                            });

                                                    });
                                                </script>';
                                            }
                                            echo '</div>'.$end_wrap;
                                            if($i == (count($thispost)-1)){
                                                echo "</div>";
                                            }
                                        // Shortcode
                                        } else {
                                            if( $i == 0 ) {
                                                echo '<div id="wrapper-'.$blognlp.'">';
                                            }
                                            echo $begin_wrap.'<div class="network-posts blog-'.$blognlp.'"><h1 class="network-posts-title"><a href="'.$thispermalink
                                            .'">'.$thispost[$i]->post_title.'</a></h1>';
                                            if( $i == (count($thispost)-1) && $paginate == true ) {
                                                echo '<div class="network-posts-pagination">';
                                                echo paginate_links( array(
                                                    'base' => add_query_arg( 'pnum', '%#%' ),
                                                    'format' => '',
                                                    'prev_text' => __('&laquo;'),
                                                    'next_text' => __('&raquo;'),
                                                    'total' => ceil($total / $posts_per_page),
                                                    'current' => $page,
                                                    'type' => 'list'
                                                ));
                                                echo '</div>';
                                                echo 
                                                '<script type="text/javascript" charset="utf-8">
                                                    jQuery(document).ready(function(){

                                                            jQuery(".blog-'.$blognlp.' .network-posts-pagination a").live("click", function(e){
                                                                    e.preventDefault();
                                                                    var link = jQuery(this).attr("href");
                                                                    jQuery("#wrapper-'.$blognlp.'").html("<img src=\"'.plugins_url('/img/loader.gif', __FILE__) .'\" />");
                                                                    jQuery("#wrapper-'.$blognlp.'").load(link+" .blog-'.$blognlp.'");

                                                            });

                                                    });
                                                </script>';
                                            }
                                            echo '</div>'.$end_wrap;
                                            if($i == (count($thispost)-1)){
                                                echo "</div>";
                                            }
                                        }
                                    }
                                }
                                // Count only when all posts has been displayed
                                $counter++;
			}
			// don't go over the limit of blogs
			if($counter >= $nblogs) {
                            break; 
			}
		}
	}
}

// Widget options (under the widget's section)
function network_latest_posts_control() {
        // Get the stored options
	$options = get_option('network_latest_posts_widget');
        // If we couldn't find anything, set some default values
	if (!is_array( $options )) {
		$options = array(
			'title' => __('Latest Posts','trans-nlp'),
			'number' => '10',
			'days' => '-1',
                        'titleonly' => true,
                        'blogid' => 'null',
                        'cpt' => 'post',
                        'ignore_blog' => 'null',
                        'cat' => 'null',
                        'tag' => 'null',
                        'paginate' => false,
                        'excerpt_length' => 'null',
                        'display_root' => false,
                        'auto_excerpt' => false,
                        'full_meta' => false
		);
	}
        // Save changes
	if ($_POST['network_latest_posts_submit']) {
		$options['title'] = htmlspecialchars($_POST['network_latest_posts_title']);
		$options['number'] = intval($_POST['network_latest_posts_number']);
		$options['days'] = intval($_POST['network_latest_posts_days']);
                $options['titleonly'] = htmlspecialchars($_POST['network_latest_posts_titleonly']);
                $options['blogid'] = htmlspecialchars($_POST['network_latest_posts_blogid']);
                $options['cpt'] = htmlspecialchars($_POST['network_latest_posts_custompost']);
                $options['ignore_blog'] = htmlspecialchars($_POST['network_latest_posts_ignoreblog']);
                $options['cat'] = htmlspecialchars($_POST['network_latest_posts_cat']);
                $options['tag'] = htmlspecialchars($_POST['network_latest_posts_tag']);
                $options['paginate'] = htmlspecialchars($_POST['network_latest_posts_paginate']);
                $options['excerpt_length'] = htmlspecialchars($_POST['network_latest_posts_excerptlength']);
                $options['display_root'] = htmlspecialchars($_POST['network_latest_posts_displayroot']);
                $options['auto_excerpt'] = htmlspecialchars($_POST['network_latest_posts_auto_excerpt']);
                $options['full_meta'] = htmlspecialchars($_POST['network_latest_posts_full_meta']);
                // Update hook
		update_option("network_latest_posts_widget", $options);
	}

?>

	<p>
            <!-- <?php print_r($options); ?> -->
	<label for="network_latest_posts_title"><?php echo __('Title','trans-nlp'); ?>: </label>
	<br /><input type="text" id="network_latest_posts_title" name="network_latest_posts_title" value="<?php echo $options['title'];?>" />
	<br /><label for="network_latest_posts_number"><?php echo __('Number of posts to show','trans-nlp'); ?>: </label>
	<input type="text" size="3" id="network_latest_posts_number" name="network_latest_posts_number" value="<?php echo $options['number'];?>" />
	<br /><label for="network_latest_posts_days"><?php echo __('Number of days to limit','trans-nlp'); ?>: </label>
	<input type="text" size="3" id="network_latest_posts_days" name="network_latest_posts_days" value="<?php echo $options['days'];?>" />
        <br /><label for="network_latest_posts_titleonly"><?php echo __('Titles Only','trans-nlp'); ?>: </label>
        <select name="network_latest_posts_titleonly" id="network_latest_posts_titleonly">
            <option value="true" <?php if($options['titleonly'] == 'true'){ echo "selected='selected'"; } ?>><?php echo __('True','trans-nlp'); ?></option>
            <option value="false" <?php if($options['titleonly'] == 'false'){ echo "selected='selected'"; } ?>><?php echo __('False','trans-nlp'); ?></option>
        </select>
        <br /><label for="network_latest_posts_blogid"><?php echo __('Blog ID','trans-nlp'); ?>: </label>
        <select name="network_latest_posts_blogid" id="network_latest_posts_blogid">
            <option value="null" <?php if($options['blogid'] == 'null'){ echo "selected='selected'"; } ?>><?php echo __('Display All','trans-nlp'); ?></option>
            <?php
                global $wpdb;
                $bids = $wpdb->get_results("SELECT blog_id, domain FROM $wpdb->blogs WHERE
			public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0'
			ORDER BY last_updated DESC");
                
                foreach ($bids as $tbid) {
            ?>
            <option value="<?php echo $tbid->blog_id; ?>" <?php if($tbid->blog_id == $options['blogid']){ echo "selected='selected'"; } ?>><?php echo $tbid->domain." (ID ".$tbid->blog_id.")"; ?></option>
            <?php
                }
            ?>
        </select>
        <br /><label for="network_latest_posts_custompost"><?php echo __('Custom Post Type','trans-nlp'); ?>: </label>
        <br /><input type="text" id="network_latest_posts_custompost" name="network_latest_posts_custompost" value="<?php echo $options['cpt'];?>" />
        <br /><label for="network_latest_posts_ignoreblog"><?php echo __('Blog(s) ID(s) to Ignore Separate by Commas','trans-nlp'); ?>: </label>
        <br /><input type="text" id="network_latest_posts_ignoreblog" name="network_latest_posts_ignoreblog" value="<?php echo $options['ignore_blog'];?>" />
        <br /><label for="network_latest_posts_cat"><?php echo __('Category(ies) Slug(s) Separate by Commas','trans-nlp'); ?>: </label>
        <br /><input type="text" id="network_latest_posts_cat" name="network_latest_posts_cat" value="<?php echo $options['cat'];?>" />
        <br /><label for="network_latest_posts_tag"><?php echo __('Tag(s) Slug(s) Separate by Commas','trans-nlp'); ?>: </label>
        <br /><input type="text" id="network_latest_posts_tag" name="network_latest_posts_tag" value="<?php echo $options['tag'];?>" />
        <br /><label for="network_latest_posts_paginate"><?php echo __('Paginate Results','trans-nlp'); ?></label>
        <select name="network_latest_posts_paginate" id="network_latest_posts_paginate">
            <option value="false" <?php if($options['paginate'] == 'false'){ echo "selected='selected'"; } ?>><?php echo __('No','trans-nlp'); ?></option>
            <option value="true" <?php if($options['paginate'] == 'true'){ echo "selected='selected'"; } ?>><?php echo __('Yes','trans-nlp'); ?></option>
        </select>
        <br /><label for="network_latest_posts_excerptlength"><?php echo __('Excerpt Length','trans-nlp'); ?>: </label>
	<input type="text" size="3" id="network_latest_posts_excerptlength" name="network_latest_posts_excerptlength" value="<?php echo $options['excerpt_length'];?>" />
        <br /><label for="network_latest_posts_displayroot"><?php echo __('Display Main Blog (Root)','trans-nlp'); ?></label>
        <select name="network_latest_posts_displayroot" id="network_latest_posts_displayroot">
            <option value="false" <?php if($options['display_root'] == 'false'){ echo "selected='selected'"; } ?>><?php echo __('No','trans-nlp'); ?></option>
            <option value="true" <?php if($options['display_root'] == 'true'){ echo "selected='selected'"; } ?>><?php echo __('Yes','trans-nlp'); ?></option>
        </select>
        <br /><label for="network_latest_posts_auto_excerpt"><?php echo __('Auto-Excerpt','trans-nlp'); ?></label>
        <select name="network_latest_posts_auto_excerpt" id="network_latest_posts_auto_excerpt">
            <option value="false" <?php if($options['auto_excerpt'] == 'false'){ echo "selected='selected'"; } ?>><?php echo __('No','trans-nlp'); ?></option>
            <option value="true" <?php if($options['auto_excerpt'] == 'true'){ echo "selected='selected'"; } ?>><?php echo __('Yes','trans-nlp'); ?></option>
        </select>
        <br /><label for="network_latest_posts_full_meta"><?php echo __('Full Metadata','trans-nlp'); ?></label>
        <select name="network_latest_posts_full_meta" id="network_latest_posts_full_meta">
            <option value="false" <?php if($options['full_meta'] == 'false'){ echo "selected='selected'"; } ?>><?php echo __('No','trans-nlp'); ?></option>
            <option value="true" <?php if($options['full_meta'] == 'true'){ echo "selected='selected'"; } ?>><?php echo __('Yes','trans-nlp'); ?></option>
        </select>
	<input type="hidden" id="network_latest_posts_submit" name="network_latest_posts_submit" value="1" />
	</p>

<?php
}

// Widget function
function network_latest_posts_widget($args) {
        // Get the attributes
	extract($args);
        // Look for saved options
	$options = get_option("network_latest_posts_widget");
        // If we couldn't find anything, set some default values
	if (!is_array( $options )) {
		$options = array(
			'title' => __('Latest Posts','trans-nlp'),
			'number' => '10',
			'days' => '-1',
                        'titleonly' => true,
                        'blogid' => 'null',
                        'cpt' => 'post',
                        'ignore_blog' => 'null',
                        'cat' => 'null',
                        'tag' => 'null',
                        'paginate' => false,
                        'excerpt_length' => 'null',
                        'display_root' => false,
                        'auto_excerpt' => false,
                        'full_meta' => false
		);
	}
        // Display the widget
	echo $before_widget;
	echo "$before_title $options[title] $after_title <ul>";
	network_latest_posts($options['number'],$options['days'],$options['titleonly'],"\n<li>","</li>",$options['blogid'],null,$options['cpt'],$options['ignore_blog'],$options['cat'],$options['tag'],$options['paginate'],$options['excerpt_length'],$options['display_root'],$options['auto_excerpt'],$options['full_meta']);
	echo "</ul>".$after_widget;
}

// Init function
function network_latest_posts_init() {
	// Check for the required API functions
	if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;
        // Register functions
	register_sidebar_widget(__("Network Latest Posts"),"network_latest_posts_widget");
	register_widget_control(__("Network Latest Posts"),"network_latest_posts_control");
        register_uninstall_hook(__FILE__, 'network_latest_posts_uninstall');
        wp_enqueue_script( 'jquery' );
        wp_register_style( 'nlpcss', plugins_url('/css/nlp.css', __FILE__) );
        wp_enqueue_style( 'nlpcss' );
        load_plugin_textdomain('trans-nlp', false, basename( dirname( __FILE__ ) ) . '/languages');
}

// Shortcode function
function network_latest_posts_shortcode($atts){
    // Constructor
    extract(shortcode_atts(array(
        'title' => null,
        'number' => '10',
        'days' => '0',
        'titleonly' => true,
        'wrapo' => null,
        'wrapc' => null,
        'blogid' => 'null',
        'thumbnail' => false,
        'cpt' => 'post',
        'ignore_blog' => 'null',
        'cat' => 'null',
        'tag' => 'null',
        'paginate' => false,
        'excerpt_length' => 'null',
        'display_root' => false,
        'auto_excerpt' => false,
        'full_meta' => false
    ), $atts));
    // Avoid direct output to control the display position
    ob_start();
    // Check if we have set a title
    if( !empty( $title ) ) { echo "<div class='network-latest-posts-sectitle'><h3 class=\"widget-title\">".$title."</h1></div>"; }
    // Get the posts
    network_latest_posts($number,$days,$titleonly,$wrapo,$wrapc,$blogid,$thumbnail,$cpt,$ignore_blog,$cat,$tag,$paginate,$excerpt_length,$display_root,$auto_excerpt,$full_meta);
    $output_string=ob_get_contents();;
    ob_end_clean();
    // Put the content where we want
    return $output_string;
}
// Add the shortcode
add_shortcode('nlposts','network_latest_posts_shortcode');

// Uninstall function
function network_latest_posts_uninstall(){
    // Delete widget options
    delete_option('network_latest_posts_widget');
    // Delete the shortcode hook
    remove_shortcode('nlposts');
}
// Execute this stuff
add_action("plugins_loaded","network_latest_posts_init");

// Functions to retrieve the post's thumbnail inside WordPress Multi-site
// This awesome piece of code was written by Curtiss
// Found here: http://www.htmlcenter.com/blog/wordpress-multi-site-get-a-featured-image-from-another-blog/
// I did some tweaks in order to adapt it to this plugin
function get_the_post_thumbnail_by_blog($blog_id=NULL,$post_id=NULL,$size='thumbnail',$attrs=NULL) {
    global $current_blog;
    $sameblog = false;

    if( empty( $blog_id ) || $blog_id == $current_blog->ID ) {
            $blog_id = $current_blog->ID;
            $sameblog = true;
    }
    if( empty( $post_id ) ) {
            global $post;
            $post_id = $post->ID;
    }
    if( $sameblog )
            return get_the_post_thumbnail( $post_id, $size, $attrs );

    if( !has_post_thumbnail_by_blog($blog_id,$post_id) )
            return false;

    global $wpdb;

    switch_to_blog($blog_id);
    $blogdetails = get_blog_details( $blog_id );
    //$thumbcode = str_replace( $current_blog->domain . $current_blog->path, $blogdetails->domain . $blogdetails->path, get_the_post_thumbnail( $post_id, $size, $attrs ) );
    $thumbcode = str_replace( $current_blog->domain /*. $current_blog->path*/, $blogdetails->domain /*. $blogdetails->path*/, get_the_post_thumbnail( $post_id, $size, $attrs ) );
    restore_current_blog();

    return $thumbcode;
}
function has_post_thumbnail_by_blog($blog_id=NULL,$post_id=NULL) {
    if( empty( $blog_id ) ) {
            global $current_blog;
            $blog_id = $current_blog;
    }
    if( empty( $post_id ) ) {
            global $post;
            $post_id = $post->ID;
    }

    global $wpdb;

    switch_to_blog($blog_id);
    $thumbid = has_post_thumbnail( $post_id );
    restore_current_blog();

    return ($thumbid !== false) ? true : false;
}
function the_post_thumbnail_by_blog($blog_id=NULL,$post_id=NULL,$size='thumbnail',$attrs=NULL) {
    return get_the_post_thumbnail_by_blog($blog_id,$post_id,$size,$attrs);
}
// Limit excerpt's length
function custom_excerpt($count,$content,$permalink){
    if($count == 0 || $count == 'null') {
        return $content;
    } else {
        $excerpt = $content;
        $excerpt = strip_tags($excerpt);
        $excerpt = substr($excerpt, 0, $count);
        //$excerpt = $excerpt.'... <a href="'.$permalink.'"><img src="'.plugins_url('/img/plus.png', __FILE__) .'" /></a>';
        $excerpt = $excerpt.'... <a href="'.$permalink.'">'.__('more').'</a>';
        return $excerpt;
    }
}
// Auto excerpt extraction
function auto_excerpt($content,$excerpt_length, $permalink){
    if( $excerpt_length == 'null' || empty($excerpt_length) || $excerpt_length == null ) { $excerpt_length = 150; }
    $words = explode(' ', $content, $excerpt_length + 1);
    if(count($words) > $excerpt_length) {
        array_pop($words);
        array_push($words, '...');
        $content = implode(' ', $words);
    }
    $content = $content . '<a href="'.$permalink.'">'.__('more').'</a>';
    return $content;
}
/*
 * TODO
 * - Listar posts por orden de encontrado
 */
?>