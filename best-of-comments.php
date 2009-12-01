<?php
/*
Plugin Name: Best-Of Comments
Plugin URI: http://www.davidjmiller.org/2009/best-of-comments/
Description: This plugin will allow you to select comments to be identified and then randomly displayed or listed.
Author: David Miller
Version: 0.9
*/ 

/*
Usage:  Call best_of_comments(); in order to return the featured comments.  You have the option of setting a limit on the amount of posts returned or a return limiter on the admin page. The admin page also allows you to specify an output template.

Usage:  Call best_of_comment_get_list(); in order to return a list of links to the featured comments.
You have the option of setting a limit on the number of comments returned by calling featured_comment_get_list(5); which will return a list of 5 random featured comments.
*/

load_plugin_textdomain('best_of_comments', 'wp-content/plugins/best-of-comments'); 

/* You can change the name of the database column here */
$cfg = 'featured';

/* Upon activation run the install_best_of_comments function */
register_activation_hook(__FILE__, 'install_best_of_comments');

/* This adds the CSS to the WP Header */
add_filter('wp_head', 'best_of_comments_css');

/* This adds Best-Of Comments metabox to the Edit Comment page */
add_action('admin_menu', 'best_of_comments_metabox');
add_action('edit_comment', 'best_of_comments_update');

/* This in the installation script */
function install_best_of_comments()
{
	/* Import globals */
	global $wpdb, $cfg;
	
	/* Check to see if column exists in the 'posts' table */
	if ($wpdb->get_var("SHOW_COLUMNS FROM $wpdb->comments LIKE 'featured'") != $cfg)
	{
		/* If not then alter the table to add the column */
		$wpdb->query("ALTER TABLE $wpdb->comments ADD featured VARCHAR(1) NOT NULL DEFAULT 'n'");
	}
}

function best_of_comments_metabox() {
	add_meta_box('best_of_comment', 'Best-Of Comments', 'best_of_comments_function', 'comment','normal');
}

/*This function populates the metabox*/
function best_of_comments_function() {
	/* Import globals */
	global $wpdb, $cfg;

	$status = $wpdb->get_var("SELECT ".$cfg." FROM $wpdb->comments WHERE comment_id='".$_GET["c"]."'");
	?>
	
		<strong<?php if ($status == 'y') echo ' style="color:#0b4"'; ?>><?php _e('This is', 'best_of_comments') ?> <?php if ($status == 'n') _e('not ', 'best_of_comments');  _e('currently a featured comment', 'best_of_comments') ?>:</strong><br/>
		<input type="radio" name="feature" id="feature" value="y"<?php if ($status == "y") echo ' checked'; ?>><?php _e('Feature this comment', 'best_of_comments') ?></input>&nbsp;
		<input type="radio" name="feature" id="feature" value="n"<?php if ($status == "n") echo ' checked'; ?>><?php _e('Do not feature this comment', 'best_of_comments') ?></input>
	<?php
}

/* Do this when editing a comment */
function best_of_comments_update($id) {
	
	global $wpdb, $cfg;
	$wpdb->query("UPDATE $wpdb->comments SET ".$cfg." = '".$_POST['feature']."' WHERE comment_ID='".$id."'");
	
}

/* This is where we get/return the featured comments */
function best_of_comments()
{
	/* Import globals */
	global $wpdb, $cfg;
	
	$options = get_option(basename(__FILE__, ".php"));
	$limit = $options['limit'];
	$limitContent = $options['length'];
	$useMoreText = $options['extend'];
	$none_text = stripslashes($options['none_text']);
	$prefix = stripslashes($options['prefix']);
	$suffix = stripslashes($options['suffix']);
	$format = stripslashes($options['format']);
	$output_template = stripslashes($options['output_template']);
	// an empty output_template makes no sense so we fall back to the default
	if ($output_template == '') $output_template = '<li>{author}<br/>{comment}</li>';

	/* Start the SQL string */
	$sql = "SELECT * FROM $wpdb->comments WHERE ".$cfg." = 'y' ORDER BY RAND()";
	
	/* Check to see if there will be a limit */
	if ($limit > 0)
		$sql .= " LIMIT $limit";
	
	/* Get the posts */
	$results = $wpdb->get_results($sql);
	
	/* Check to see if there are any posts returned */
	if ($results)
	{
		$output = $prefix;
		/* Loop through the row and display the URL */
		foreach($results as $res)
		{
			$author = stripslashes($res->comment_author);
			$comment = apply_filters('the_content', $res->comment_content);
			$link = get_permalink($res->comment_post_ID).'#comment-'.$res->comment_ID;
			/* This will handle the content that is displayed */
			$new_post_cont = "";
			
			/* Check to see if the user wants to limit the amount that is displayed */
			if ($limitContent > 0)
			{
				$expPost = explode(' ', $comment);
				
				if (count($expPost) > $limitContent)
				{
					$cut_at = $limitContent;
					$useMore = 1;
				}
				else
				{
					$cut_at = count($expPost);
					$useMore = 0;
				}
				
				for ($i = 0; $i <= $cut_at; $i++)
				{
					$new_post_cont .= $expPost[$i].' ';
				}
				$new_post_cont .= ($useMore) ? '[...] <p><a href="'.$link.'" title="Read more">'.$useMoreText.'</a>' : '';
				$new_post_cont = str_replace(']]>', ']]&gt;', $new_post_cont);
			} else {
				$new_post_cont = $comment;
			}
			
			/* Echo the returned results like a comment */
			$impression = str_replace("{author}",'<a href="'.$link.'">'.$author.'</a>',str_replace("{comment}",$new_post_cont,$output_template));
			$output .= $impression;
		}
		$output .= $suffix;
	} else {
		$output = $none_text;
	}
	echo $output;
}

/* This is where we get/return the list of featured comments */
function best_of_comment_get_list($limit = 0)
{
	/* Import globals */
	global $wpdb, $cfg;
	
	$options = get_option(basename(__FILE__, ".php"));
	$limit = $options['limit'];
	$limitContent = $options['length'];
	$useMoreText = $options['extend'];
	$none_text = stripslashes($options['none_text']);
	$prefix = stripslashes($options['prefix']);
	$suffix = stripslashes($options['suffix']);
	$format = stripslashes($options['format']);
	$output_template = stripslashes($options['output_template']);
	// an empty output_template makes no sense so we fall back to the default
	if ($output_template == '') $output_template = '<li>{author}<br/>{comment}</li>';

	/* Start the SQL string */
	$sql = "SELECT * FROM $wpdb->comments WHERE ".$cfg." = 'y' ORDER BY RAND()";
	
	/* Check to see if there will be a limit */
	if ($limit > 0)
		$sql .= " LIMIT $limit";
	
	/* Get the posts */
	$results = $wpdb->get_results($sql);
	
	/* Check to see if there are any comments returned */
	if ($results)
	{
		$output = $prefix;
		/* Loop through the row and display the URL */
		foreach($results as $res)
		{
			$author = stripslashes($res->comment_author);
			$comment = apply_filters('the_content', $res->comment_content);
			$link = get_permalink($res->comment_post_ID).'#comment-'.$res->comment_ID;
			
			/* Echo the returned results like a comment */
			$impression = str_replace("{author}",'<a href="'.$link.'">'.$author.'</a>',str_replace("{comment}",$new_post_cont,$output_template));
			$output .= $impression;
		}
		$output .= $suffix;
	} else {
		$output = $none_text;
	}
	echo $output;
}

function best_of_comments_css()
{
	/* Insert any CSS here.  Above is the default display for a post with no custom CSS */
	echo('');
}

/*
	Define the options menu
*/

function best_of_comments_option_menu() {
	if (function_exists('current_user_can')) {
		if (!current_user_can('manage_options')) return;
	} else {
		global $user_level;
		get_currentuserinfo();
		if ($user_level < 8) return;
	}
	if (function_exists('add_options_page')) {
		add_options_page(__('Best-Of Comments Options', 'best_of_comments'), __('Best-Of Comments', 'best_of_comments'), 1, __FILE__, 'best_of_comments_options_page');
	}
}

// Install the options page
add_action('admin_menu', 'best_of_comments_option_menu');

// Prepare the default set of options
$default_options['limit'] = 1;
$default_options['length'] = 50;
$default_options['extend'] = 'More';
$default_options['none_text'] = '';
$default_options['prefix'] = '<ul>';
$default_options['suffix'] = '</ul>';
$default_options['format'] = 'value';
$default_options['output_template'] = '<li><h2>{author} said:</h2> {comment}</li></li>';
// the plugin options are stored in the options table under the name of the plugin file sans extension
add_option(basename(__FILE__, ".php"), $default_options, 'options for the Best-Of Comments plugin');

// This method displays, stores and updates all the options
function best_of_comments_options_page(){
	global $wpdb;
	// This bit stores any updated values when the Update button has been pressed
	if (isset($_POST['update_options'])) {
		// Fill up the options array as necessary
		$options['limit'] = $_POST['limit'];
		$options['length'] = $_POST['length'];
		$options['extend'] = $_POST['extend'];
		$options['none_text'] = $_POST['none_text'];
		$options['prefix'] = $_POST['prefix'];
		$options['suffix'] = $_POST['suffix'];
		$options['format'] = $_POST['format'];
		$options['output_template'] = $_POST['output_template'];

		// store the option values under the plugin filename
		update_option(basename(__FILE__, ".php"), $options);
		
		// Show a message to say we've done something
		echo '<div class="updated"><p>' . __('Options saved', 'best_of_comments') . '</p></div>';
	} else {
		// If we are just displaying the page we first load up the options array
		$options = get_option(basename(__FILE__, ".php"));
	}
	//now we drop into html to display the option page form
	?>
		<div class="wrap">
		<h2><?php echo ucwords(str_replace('-', ' ', basename(__FILE__, ".php"). __(' Options', 'best_of_comments'))); ?></h2>
		<h3><a href="http://www.davidjmiller.org/2009/best-of-comments/"><?php _e('Help and Instructions', 'best_of_comments') ?></a></h3>
		<form method="post" action="">
		<fieldset class="options">
		<table class="optiontable">
			<tr valign="top">
				<th scope="row" align="right"><?php _e('Number of comments to show', 'best_of_comments') ?>:</th>
				<td colspan="2"><input name="limit" type="text" id="limit" value="<?php echo $options['limit']; ?>" size="2" /></td>
			</tr>
			<tr valign="top">
				<th scope="row" align="right"><?php _e('Max words to show', 'best_of_comments') ?>:</th>
				<td colspan="2"><input name="length" type="text" id="length" value="<?php echo $options['length']; ?>" size="3" /></td>
			</tr>
			<tr valign="top">

				<th scope="row" align="right"><?php _e('More Text', 'best_of_comments') ?>:</th>
				<td colspan="2"><input name="extend" type="text" id="extend" value="<?php echo htmlspecialchars(stripslashes($options['extend'])); ?>" size="40" /><?php _e('to link back to comments longer than the limit', 'best_of_comments') ?></td>
			</tr>
			<tr valign="top">

				<th scope="row" align="right"><?php _e('Default display if no matches', 'best_of_comments') ?>:</th>
				<td colspan="2"><input name="none_text" type="text" id="none_text" value="<?php echo htmlspecialchars(stripslashes($options['none_text'])); ?>" size="40" /></td>
			</tr>
			<tr valign="top">
				<th scope="row" align="right"><?php _e('Text and codes before the list', 'best_of_comments') ?>:</th>
				<td><input name="prefix" type="text" id="prefix" value="<?php echo htmlspecialchars(stripslashes($options['prefix'])); ?>" size="40" /></td>
			</tr>
			<tr valign="top">
				<th scope="row" align="right"><?php _e('Text and codes after the list', 'best_of_comments') ?>:</th>
				<td><input name="suffix" type="text" id="suffix" value="<?php echo htmlspecialchars(stripslashes($options['suffix'])); ?>" size="40" /></td>
			</tr>
			<tr valign="top">
				<th scope="row" align="right"><?php _e('Output template', 'best_of_comments') ?>:</th>
				<td><textarea name="output_template" id="output_template" rows="4" cols="32"><?php echo htmlspecialchars(stripslashes($options['output_template'])); ?></textarea></td>
			</tr>
			<tr valign="top">
				<th scope="row" align="right"></th>
				<td align="center"><?php _e('Valid template tags', 'best_of_comments') ?>:{author}, {comment}</td>
			</tr>
		</table>
		</fieldset>
		<div class="submit">
			<input type="submit" name="update_options" value="<?php _e('Update', 'best_of_comments') ?>"  style="font-weight:bold;" />
		</div>
		</form>    		
	</div>
	<?php	
}
?>