<?php
/*
Plugin Name: Slidecase
Plugin URI: http://honors-slcholars.osu.edu/studioct/portfolio.html
Deslcription: Cycle through featured posts displaying a related image in the background.
Version: 1.1
Author: Ryan McGowan
Author URI: http://smi.th-ro.in/
License: GPL2
*/


//This call and the following function enqueue the necessary slcript and style for this plugin.
add_action('init', 'setupSlidecase');

//Add actions so ajax works
add_action('wp_ajax_get_slc_image', 'getSlidecase');
add_action('wp_ajax_nopriv_get_slc_image', 'getSlidecase');
add_action('wp_ajax_get_slc_content', 'getSlidecase');
add_action('wp_ajax_nopriv_get_slc_content', 'getSlidecase');


function setupSlidecase()
{
	$slc_vers = '1.1';
	wp_register_script('jquery-easing', WP_PLUGIN_URL.'/slidecase/jquery.easing.1.3.js', array('jquery', 'jquery-ui-core'), $slc_vers);
	wp_enqueue_script('slidecase', WP_PLUGIN_URL.'/slidecase/slidecase.js', array('jquery', 'jquery-ui-core', 'jquery-easing'), $slc_vers);
	wp_enqueue_script('jquery-easing', WP_PLUGIN_URL.'/slidecase/jquery.easing.1.3.js', array('jquery', 'jquery-ui-core'), $slc_vers);
	wp_enqueue_style('slidecase', WP_PLUGIN_URL.'/slidecase/slidecase.css', $slc_vers, 'all');
	
	$modsheet = get_bloginfo('stylesheet_directory').'/css/slidecase-mod.css';
//	if(is_readable($modsheet))
		wp_enqueue_style('slidecase-mod', $modsheet, $slc_vers, 'all');
		
	if(!is_admin())	//If not in the admin page then add ajaxurl javascript variable to page.
		wp_localize_script('slidecase', 'slidecase_stuff', array('ajaxurl' => admin_url('admin-ajax.php')));
	add_image_size('slidecase-thumbnail', 535, 355, true);
	if(!term_exists('showcase', 'category'))
		wp_insert_term('Showcase', 'category', array(
			'description' => 'Posts in this category appear in the header at the top of the home page.',
			));
}


//This function can be placed anywhere to genereate a Slidecase rotator.
function placeSlidecase()
{
	$query1 = new WP_Query(array('post_type' => 'post', 'post_status' => 'publish', 'category_name' => 'Showcase', 'posts_per_page' => 1, 'offset' => 0, 'order' => 'ASC'));
	$query2 = new WP_Query(array('post_type' => 'post', 'post_status' => 'publish', 'category_name' => 'Showcase', 'posts_per_page' => 2, 'offset' => 0, 'order' => 'DESC'));
	
	if($query2->post_count > 0)
	{
		if($query2->post_count == 2)
			$queries = array($query1, $query2);
		else
			$queries = array($query2);
			
		echo '
		<div class="slc">
			<div class="slc-image-container">';
			placeSlidecasePictures($queries, array(), 'INIT');
		echo '
			</div>
			<div class="slc-container">';
		echo '
				<div class="slc-content-container">
					<div class="slc-arrow"></div>
					<div class="slc-content-holder">';
		placeSlidecaseContent($queries, array(), 'INIT');
		echo'
					</div>
					<div class="slc-arrow right"></div>
				</div>';
		echo '
			</div>
		</div>';
	}
}

function placeSlidecasePictures($queries, $displayed = array(), $order)
{
	foreach($queries as $query)
	{
		if($query->have_posts())
		{
			while($query->have_posts())
			{
				$query->the_post();
				if(in_array('slc_post_'.$query->post->ID, $displayed))
				{
					$loc = 0;
					if($order = 'ASC')
						$loc = sizeof($displayed);
					if($displayed[$loc] == 'slc_post_'.$query->post->ID)
						echo "-2";
					else
						echo "-3";
				}
				else
				{
					the_post_thumbnail('slidecase-thumbnail');
					array_push($displayed, 'slc_post_'.$query->post->ID);
				}	
			}
		}
	}
}

function placeSlidecaseContent($queries, $displayed, $order)
{
	//Filters are added (and later removed) to change the output of The Loop function calls.
	add_filter('the_title', 'slc_title');
	add_filter('the_content', 'slc_blerb');
	$qcount = 1;
	foreach($queries as $query)
	{
		if($query->have_posts())
		{
			$count = 0;
			while($query->have_posts())
			{
				$query->the_post();
				global $more;
				$more = 0;
				if(in_array('slc_post_'.$query->post->ID, $displayed))
				{
					$loc = 0;
					if($order = 'ASC')
						$loc = sizeof($displayed);
					if($displayed[$loc] == 'slc_post_'.$query->post->ID)
						echo "-2";
					else
						echo "-3";
				}
				else
				{
					if(($qcount == sizeof($queries)) && ($count == 0) && $order == 'INIT')
						$style = "";
					else
						$style = 'display: none;';

					echo '
					<div id="slc_post_'.$query->post->ID.'" class="slc-content" style="'.$style.'">';
					the_title();
					the_content('<span class="readmore">Read More</div>');
					echo '
					</div>';
					array_push($displayed, 'slc_post_'.$query->post->ID);
				}
				$count++;
			}
		}
		$qcount++;
	}
	
	//The filters are removed.
	remove_filter('the_title', 'slc_title');
	remove_filter('the_content', 'slc_blerb');
}

function slc_title($title)
{
	return '
	<h2>
		'.$title.'
	</h2>';
}

function slc_blerb($content)
{
	return '
	<div class="slc-blerb">
		'.$content.'
	</div>';
}

function getSlidecase()
{
	$placefunc = 'Pictures';
	if($_POST['action'] == "get_slc_content")
		$placefunc = 'Content';
	$placefunc = 'placeSlidecase'.$placefunc;
	$a = shortcode_atts(array(
				'post_type' => 'post',
				'post_status' => 'publish',
				'category_name' => 'Showcase',
				'posts_per_page' => 1,
				'offset' => 0,
				'order' => 'DESC'
				), $_POST);
				
	$query = new WP_Query($a);
	if($query->post_count == 0)
	{
		echo "-2";
	}
	else
		$placefunc(array($query), (array) $_POST['displayed'], $a['order']);
	exit;
}

?>
