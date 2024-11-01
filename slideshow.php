<?php
/*
	Plugin Name: Slideshow Manager
	Description: Slideshow Manager for Wordpress
	Author: Rasmus Johanson
	Version: 2.1.3
	License: GPLv2

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


global $table_name;
global $wpdb;

// Define table name for plugin (with prefix)
$table_name = $wpdb->prefix . 'slideshow_plugin';

// Get's called when plugin is installed or reinstalled
register_activation_hook(__FILE__, 'plugin_activation');

function plugin_activation() {
	global $wpdb, $table_name;

	// Create a table to database
	$sql = "CREATE TABLE .$table_name (
		id int(11) NOT NULL AUTO_INCREMENT,
		thumbnail_url varchar(1024) NOT NULL,
		url varchar(1024) NOT NULL,
		slideshow_name varchar(1024) DEFAULT 'default',
		link varchar(1024) NOT NULL DEFAULT 'http://',
		description varchar(5120) NOT NULL,
		position int(11) NOT NULL,
		UNIQUE KEY id (id)
	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);

	// delete_option('slideshow_option');

	// Create default settings
	$default_settings = array(
		"fx" => "rain",
		"delay" => "3000",
		"navigation" => "true",
		"hoverPause" => "true",
		"width" => "640",
		"height" => "480",
		"jsmanually" => "false",
		"cssmanually" => "false",
		"spw" => "7",
		"sph" => "5",
		"sDelay" => "30",
		"opacity" => "1",
		"titleSpeed" => "500",
		"mobiles_allowed" => "false");

	update_option('slideshow_option', $default_settings);
}

// Create Admin menu
add_action('admin_menu', 'slideshow_plugin_menu');

function slideshow_plugin_menu() {
	add_menu_page('Slideshow Options', 'Slideshow', 'manage_options', 'slideshow-manager', 'slideshow_manager_options', plugin_dir_url( __FILE__ ).'icon.png', 3);
}


add_action('admin_init', 'settings_init');

// Register settings, internationalization
function settings_init() {
	register_setting('slideshow_plugin_options', 'slideshow_option', 'slideshow_settings_validate');
	// Tnternationalization
	load_plugin_textdomain('slideshow', false, dirname(plugin_basename(__FILE__)) . '/localization/' );
}

// Validate checkboxes, trust the rest
function slideshow_settings_validate($input) {
	$input['jsmanually'] = ($input['jsmanually'] == 'true' ? 'true' : 'false');
	$input['cssmanually'] = ($input['cssmanually'] == 'true' ? 'true' : 'false');
	$input['navigation'] = ($input['navigation'] == 'true' ? 'true' : 'false');
	$input['hoverPause'] = ($input['hoverPause'] == 'true' ? 'true' : 'false');
	return $input;
}

add_action('admin_init', 'register_scripts_backend');

// Enqueue backend scripts
function register_scripts_backend() {
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-sortable');
}

add_action('wp_enqueue_scripts', 'register_nivo');

// Enqueue Coin-Slider JavaScript and CSS
function register_nivo() {
	$options = get_option('slideshow_option', 'slideshow');
	wp_enqueue_script('jquery');
	if ($options['jsmanually'] != "true") {
		wp_register_script('coin-slider', plugins_url('coin-slider/coin-slider.min.js', __FILE__) );
		wp_enqueue_script('coin-slider');
	}
	if ($options['cssmanually'] != "true") {
		wp_register_style('coin-css', plugins_url('coin-slider/coin-slider-styles.css', __FILE__) );
		wp_enqueue_style('coin-css');
	}
}


add_action('admin_head', 'backend_javascript');

// Add JavaScript to backend
function backend_javascript() { ?>
<script type="text/javascript">
jQuery(document).ready(function($) {

	jQuery(".sortableTable").sortable({
		handle : '.slideshow_thumb',
		update: function() {
			jQuery(".slideshow_thumb").hide();
			var data = {
				action: 'sort',
				pages: jQuery('.sortableTable').sortable('serialize')
			};
			jQuery.post(ajaxurl, data, function(data) {
				jQuery(".slideshow_thumb").show();
				console.log(data);
			});
		}
	});

	jQuery("#deleteTab").click(function () {
			var ask = confirm('<?php _e('Are you sure you want to delete this gallery tab and all of the contents?', 'slideshow'); ?>');
			if (ask == false) {
				return false;
			}	
	});

	jQuery("#create_new_tab").click(function (e) {
		e.preventDefault();
		var name = prompt("<?php _e('Please enter the name for a new gallery tab', 'slideshow'); ?>", "<?php _e('New tab', 'slideshow'); ?>");
		if (name != null) {
			window.location = "?page=slideshow-manager&action=create_gallery&gallery_id=" + name + "&tab=" + name + "";
		}
	});

	jQuery(".delete_button").click(function (e) {
			e.preventDefault();
			var ask = confirm('<?php _e('Are you sure you want to delete this picture?', 'slideshow'); ?>');
			if (ask == false) {
				return false;
			}

			var id = jQuery(this).attr("id").substring(7);

			var data = {
				action: 'delete',
				id: id
			};
			jQuery.post(ajaxurl, data, function(data) {
				console.log(data);
			});

			jQuery(this).closest('tr').hide();
	});

	jQuery(".update_button").click(function (e) {
			e.preventDefault();

			var id = jQuery(this).attr("id").substring(7);
			var description = jQuery("#description_"+id).val();
			var link = jQuery("#link_"+id).val();
			jQuery("#description_"+id+", #link_"+id).hide();

			var data = {
				action: 'update',
				id: [id, description, link]
			};
			jQuery.post(ajaxurl, data, function(data) {
				console.log(data);
				jQuery("#description_"+id+", #link_"+id).show();
			});

	});

	jQuery("#advanced-settings-toggle").click(function(e) {
		e.preventDefault();
		jQuery("#advanced-settings-panel").toggle();
	});

	jQuery("#howto-settings-toggle").click(function(e) {
		e.preventDefault();
		jQuery("#howto-settings-panel").toggle();
	});

});
</script>
<?php
}

add_action('wp_ajax_sort', 'sort_callback');
add_action('wp_ajax_delete', 'delete_callback');
add_action('wp_ajax_update', 'update_callback');

function sort_callback() {
	global $wpdb, $table_name;
	parse_str($_POST['pages'], $pageOrder);
	foreach ($pageOrder['page'] as $key => $value) {
		$wpdb->update($table_name, array('position' => $key), array('id' => $value));
	}
	die(); // Required to return a proper result (http://codex.wordpress.org/AJAX_in_Plugins)
}

function delete_callback() {
	global $wpdb, $table_name;
	$id = $_POST['id'];
	$wpdb->query("DELETE FROM $table_name WHERE id = $id");
	die();
}

function update_callback() {
	global $wpdb, $table_name;
	$data = $_POST['id'];
	$wpdb->update($table_name, array('description' => stripslashes($data[1]), 'link' => $data[2]), array('id' => $data[0]));
	print_r($data);
	die();
}

function slideshow_upload($arg) {
	global $wpdb, $table_name;
	$options = get_option('slideshow_option', 'slideshow');

	$upload = wp_handle_upload($_FILES['async-upload'], 0);

	$url = $upload['url'];
	$file = $upload['file'];
	$type = $upload['type'];

	// If there is an error with upload, display it to user
	if(isset($upload['error'])) {
    	echo '<div class="error" style="margin: 0px!important; border: 1px solid #ccc!important; border-radius: 0px;"><p>';
		echo $upload['error'];
		echo '</p></div>';
		return;
	}

	// Get upload dir
	$upload_dir = str_replace(basename($file), '', $url);

	if(!($type == "image/gif" || $type == "image/jpeg" || $type == "image/png")) {
		echo '<div class="error" style="margin: 0px!important; border: 1px solid #ccc!important; border-radius: 0px;"><p>';
		_e('Wrong file type!', 'slideshow');
		echo '</p></div>';
		return;
	}

	list($width, $height) = getimagesize($file);
	// Check width/height requirements
	if($width < $options['width'] || $height < $options['height']) {
		echo '<div class="error" style="margin: 0px!important; border: 1px solid #ccc!important; border-radius: 0px;"><p>';
		_e('The picture you uploaded is too small!', 'slideshow');
		echo '</p></div>';
		return;
	}

	// If image is too big, scale it down
	if($width < $options['width'] || $height > $options['height']) {
		// Resize the image, image proposition comes from settings
		$resized = image_resize($file, $options['width'], $options['height'], true);
		$resized_url = $upload_dir . basename($resized);
		unlink($file);
		// wtf?!
		$url = $resized_url;
		$file = $resized;

	}

	if(isset($upload['file'])) {
		// Create thumbnail width="100" height="75"
		$thumbnail = image_resize($file, 100, 75, true, 'thumb');
		$thumbnail_url = $upload_dir . basename($thumbnail);
	}

	$wpdb->insert($table_name, array('url' => $url, 'slideshow_name' => $arg, 'thumbnail_url' => $thumbnail_url, 'description' => '', 'position' => '1000'));
	echo '<div class="updated" style="margin: 0px!important; border: 1px solid #ccc!important; border-radius: 0px;"><p>';
	_e('Image uploaded successfully', 'slideshow');

	echo '</p></div>';
}


function slideshow_manager_options() {

global $wpdb, $table_name;

// Navigate between tabs
if(isset($_GET['tab'])) {
	$active_tab = $_GET['tab'];
} else {
	$active_tab = 'default';
}

// Delete tab (gallery)
if(($_GET['action']) == "delete_gallery") {

	global $wpdb, $table_name;
	$id = $_GET[gallery_id];
	$wpdb->query("DELETE FROM $table_name WHERE slideshow_name = '".$id."'");

	echo '<div class="updated" style="margin: 0px!important; border: 1px solid #ccc!important; border-radius: 0px;"><p>';
	_e('Gallery successfully deleted!', 'slideshow');
	echo '</p></div>';
}

// Upload new file
if($_REQUEST['action'] == 'wp_handle_upload') {
	slideshow_upload($arg = $active_tab); 
}

?>

<div class="wrap">
<h2 class="nav-tab-wrapper">

	<?php
	$stack = array("default");
	$myrows = $wpdb->get_results("SELECT * FROM $table_name");

	foreach ($myrows as $row) {
		array_push($stack, $row->slideshow_name);
	}

	if(($_GET['action']) == "create_gallery") {
		array_push($stack, $active_tab);
	}

	$result = array_unique($stack);

	foreach($result as $value) {
	?>

	<a href="?page=slideshow-manager&tab=<?php echo $value; ?>" <?php if($value == "default") { echo 'style="text-transform:capitalize;"';} ?> class="nav-tab <?php echo $active_tab == $value ? 'nav-tab-active' : ''; ?>"><?php echo $value; ?></a>
	
	<?php
	}
	?>

	<a href="#" id="create_new_tab" class="nav-tab">+</a>

</h2>
<div style="border: 1px solid #CCC; padding: 10px; border-top: none!important;">

<table class="widefat" id="slideshowtable">
<thead>
	<tr>
		<th style="width: 100px;"><?php _e('Thumbnail', 'slideshow'); ?></th>
		<th><?php _e('Caption and link address', 'slideshow'); ?></th>
		<th style="width: 65px;"></th>
		<th style="width: 65px;"></th>
	</tr>
</thead>
<tfoot>
<form enctype="multipart/form-data" action="?page=slideshow-manager&tab=<?php echo $active_tab; ?>" method="post">
<input type="hidden" name="action" id="action" value="wp_handle_upload" />
	<tr>
		<th colspan="3">
			<input type="file" name="async-upload" id="async-upload" style="width: 100%;">
		</th>
		<th style="width: 65px; text-align: center;">
			<input type="submit" name="html-upload" id="html-upload" class="button" value="<?php _e('Upload', 'slideshow'); ?>" style="font-family: sans-serif; font-size: 12px; line-height: 15px; padding: 3px 10px;">
		</th>
	</tr>
</form>

</tfoot>
<tbody class="sortableTable">

<?php

$myrows = $wpdb->get_results("SELECT * FROM $table_name WHERE slideshow_name = '$active_tab' ORDER BY position ASC"); // order by
foreach ($myrows as $row) {
?>
	<tr id="page_<?php echo $row->id; ?>">
		<td style="background: url(<?php echo get_admin_url(); ?>images/loading.gif) center no-repeat;">
			<img src="<?php echo $row->thumbnail_url; ?>" width="100" height="75" alt="thumbnail" class="slideshow_thumb" style="cursor: move;">
		</td>
		<td style="background: url(<?php echo get_admin_url(); ?>images/loading.gif) center no-repeat;">
			<textarea id="description_<?php echo $row->id; ?>" style="width: 100%; height: 45px;"><?php echo $row->description; ?></textarea>
			<input type="text" id="link_<?php echo $row->id; ?>" style="width: 100%; height: 25px;" value="<?php echo $row->link; ?>">
		</td>
		<td style="vertical-align: middle; text-align: center;">
			<a class="button-primary update_button" id="update_<?php echo $row->id; ?>" href="#"><?php _e('Update', 'slideshow'); ?></a>
		</td>
		<td style="vertical-align: middle; text-align: center;">
			<a class="button delete_button" id="delete_<?php echo $row->id; ?>" href="#"><?php _e('Delete', 'slideshow'); ?></a>
		</td>
	</tr>

<?php
}
?>

</tbody>
</table>

<h3><?php _e('Slideshow Settings', 'slideshow'); ?></h3>
<div id="slideshow-settings-panel" style="background: #F9F9F9; border: 1px solid #DFDFDF; padding-bottom: 8px;">
	<form method="post" action="options.php">
		<?php settings_fields('slideshow_plugin_options', 'slideshow'); ?>
		<?php $options = get_option('slideshow_option', 'slideshow'); ?>
		<table class="form-table">
			<tr valign="top" style="border-bottom: 1px solid #DFDFDF;">
			<th scope="row"><?php _e('Slide transition effect', 'slideshow'); ?></th>
			<td>
				<select name="slideshow_option[fx]">
					<option <?php selected('swirl', $options['fx']); ?>>swirl</option>
					<option <?php selected('rain', $options['fx']); ?>>rain</option>
					<option <?php selected('straight', $options['fx']); ?>>straight</option>
					<option <?php selected('random', $options['fx']); ?>>random</option>
				</select>
			</td>
			</tr>

			<tr valign="top" style="border-bottom: 1px solid #DFDFDF;">
				<th scope="row"><?php _e('Delay between slides', 'slideshow'); ?></th>
				<td><input type="number" name="slideshow_option[delay]" value="<?php echo $options['delay']; ?>"><span class="description" style="margin-left: 10px;"><?php _e('In milliseconds', 'slideshow'); ?></span></td>
			</tr>

			<tr valign="top" style="border-bottom: 1px solid #DFDFDF;">
				<th scope="row"><?php _e('Enable navigation', 'slideshow'); ?></th>
				<td>
					<input name="slideshow_option[navigation]" type="checkbox" value="true" <?php checked('true', $options['navigation']); ?>> 
				</td>
			</tr>

			<tr valign="top" style="border-bottom: 1px solid #DFDFDF;">
				<th scope="row"><?php _e('Pause slideshow on hover', 'slideshow'); ?></th>
				<td>
					<input name="slideshow_option[hoverPause]" type="checkbox" value="true" <?php checked('true', $options['hoverPause']); ?>> 
				</td>
			</tr>

		</table>
</div>

<h3><?php _e('Advanced Settings', 'slideshow'); ?> (<a href="#" id="advanced-settings-toggle" style="text-decoration: none;"><?php _e('Show', 'slideshow'); ?></a>)</h3>
<div class="hidden" id="advanced-settings-panel" style="background: #F9F9F9; border: 1px solid #DFDFDF; padding-bottom: 8px;">
	<table class="form-table">
		<tr valign="top" style="border-bottom: 1px solid #DFDFDF;">
			<th scope="row"><?php _e('Slideshow width', 'slideshow'); ?></th>
			<td><input type="number" name="slideshow_option[width]" value="<?php echo $options['width']; ?>"><span class="description" style="margin-left: 10px;"><?php _e('In pixels', 'slideshow'); ?></span></td>
		</tr>
		
		<tr valign="top" style="border-bottom: 1px solid #DFDFDF;">
			<th scope="row"><?php _e('Slideshow height', 'slideshow'); ?></th>
			<td><input type="number" name="slideshow_option[height]" value="<?php echo $options['height']; ?>"><span class="description" style="margin-left: 10px;"><?php _e('In pixels', 'slideshow'); ?></span></td>
		</tr>

		<tr valign="top" style="border-bottom: 1px solid #DFDFDF;">
			<th scope="row"><?php _e('I want to add slider.js manually', 'slideshow'); ?></th>
			<td>
				<input name="slideshow_option[jsmanually]" type="checkbox" value="true" <?php checked('true', $options['jsmanually']); ?>> 
			</td>
		</tr>

		<tr valign="top" style="border-bottom: 1px solid #DFDFDF;">
			<th scope="row"><?php _e('I want to add slider.css manually', 'slideshow'); ?></th>
			<td>
				<input name="slideshow_option[cssmanually]" type="checkbox" value="true" <?php checked('true', $options['cssmanually']); ?>> 
			</td>
		</tr>

		<tr valign="top" style="border-bottom: 1px solid #DFDFDF;">
			<th scope="row"><?php _e('Disable on handheld devices', 'slideshow'); ?></th>
			<td>
				<input name="slideshow_option[mobiles_allowed]" type="checkbox" value="true" <?php checked('true', $options['mobiles_allowed']); ?>> <span class="description" style="margin-left: 10px;"><?php _e('Disable slideshow on handheld devices such as mobiles and tablets', 'slideshow'); ?></span>
			</td>
		</tr>

		<tr valign="top" style="border-bottom: 1px solid #DFDFDF;">
			<th scope="row">
				<?php _e('Squares per width', 'slideshow'); ?>
			</th>
			<td>
				<input type="number" name="slideshow_option[spw]" value="<?php echo $options['spw']; ?>"> <span class="description"><?php _e('Setting these too high might affect the performance on older devices', 'slideshow'); ?></span>
			</td>
		</tr>

		<tr valign="top" style="border-bottom: 1px solid #DFDFDF;">
			<th scope="row">
				<?php _e('Squares per height', 'slideshow'); ?>
			</th>
			<td>
				<input type="number" name="slideshow_option[sph]" value="<?php echo $options['sph']; ?>">
			</td>
		</tr>

		<tr valign="top" style="border-bottom: 1px solid #DFDFDF;">
			<th scope="row">
				<?php _e('Delay beetwen squares', 'slideshow'); ?>
			</th>
			<td>
				<input type="number" name="slideshow_option[sDelay]" value="<?php echo $options['sDelay']; ?>"> <span class="description"><?php _e('In milliseconds', 'slideshow'); ?></span>
			</td>
		</tr>

		<tr valign="top" style="border-bottom: 1px solid #DFDFDF;">
			<th scope="row">
				<?php _e("Title's opacity", 'slideshow'); ?>
			</th>
			<td>
				<input type="number" name="slideshow_option[opacity]" max="1" min="0" step="0.1" value="<?php echo $options['opacity']; ?>"> <span class="description"><?php _e('Minimum 0 (transparent), maximum 1.0', 'slideshow'); ?></span>
			</td>
		</tr>

		<tr valign="top" style="border-bottom: 1px solid #DFDFDF;">
			<th scope="row">
				<?php _e('Speed of title appereance', 'slideshow'); ?>
			</th>
			<td>
				<input type="number" name="slideshow_option[titleSpeed]" value="<?php echo $options['titleSpeed']; ?>"> <span class="description"><?php _e('In milliseconds', 'slideshow'); ?></span>
			</td>
		</tr>

		<?php if ($active_tab !== 'default') { /* Default gallery cannot be deleted */ ?>
		<tr valign="top" style="border-bottom: 1px solid #DFDFDF;">
			<th scope="row">
				<a href="?page=slideshow-manager&action=delete_gallery&gallery_id=<?php echo $active_tab; ?>&tab=default" id="deleteTab"><?php _e('Delete active gallery tab', 'slideshow'); ?></a>
			</th>
			<td>
				<span class="description"><?php _e('This will delete currently active gallery tab and all of its contents', 'slideshow'); ?></span>
			</td>
		</tr>
		<?php } ?>
	</table>
</div>

<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Settings', 'slideshow'); ?>" /></p>
</form>

<h3><?php _e('How to use', 'slideshow'); ?> (<a href="#" id="howto-settings-toggle" style="text-decoration: none;"><?php _e('Show', 'slideshow'); ?></a>)</h3>
<div class="hidden" id="howto-settings-panel" style="background: #F9F9F9; border: 1px solid #DFDFDF; padding-bottom: 8px;">
	<table class="form-table">
		<tr>
			<td>
				<p><?php _e('Use as a shortcode:', 'slideshow'); ?></p>
				<p style="border: 1px solid #DFDFDF; padding: 10px; margin-bottom: 10px; background: #fff;">
					[slideshow id="<?php echo $active_tab; ?>"]
				</p>

				<p><?php _e('Or as a function:', 'slideshow'); ?></p>
				<p style="border: 1px solid #DFDFDF; padding: 10px; margin-bottom: 10px; background: #fff;">
					&lt;?php <br> 
					&nbsp;&nbsp;&nbsp;&nbsp;if (function_exists('slideshow')) { <br>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;slideshow('<?php echo $active_tab; ?>', true); <br>
					&nbsp;&nbsp;&nbsp;&nbsp;} <br> 
					?&gt;
				</p>

			</td>
		</tr>
	</table>
</div>

</div>
</div>
<?php
}

add_shortcode('slideshow', 'slideshow');

function slideshow($arg, $isFunction = false) {

	global $wpdb, $table_name;
	$options = get_option('slideshow_option');

	if ($isFunction == false) {
		$arg = $arg[id];
	}

?>

<script type="text/javascript">
jQuery(document).ready(function($) {

<?php if ($options['mobiles_allowed'] == true) { ?>
	if (/Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent)) {
		return false;
	}
<?php } ?>

		$('#coin-slider').coinslider({ 
			effect: '<?php echo $options['fx']; ?>',
			width: <?php echo $options['width']; ?>,
			height: <?php echo $options['height']; ?>,
			spw: <?php echo $options['spw']; ?>,
			sph: <?php echo $options['sph']; ?>,
			delay: <?php echo $options['delay']; ?>,
			sDelay: <?php echo $options['sDelay']; ?>,
			opacity: <?php echo $options['opacity']; ?>,
			titleSpeed: <?php echo $options['titleSpeed']; ?>,
			navigation: <?php echo $options['navigation']; ?>,
			hoverPause: <?php echo $options['hoverPause']; ?>

		});

});
</script>

<div id="coin-slider" style="width: <?php echo $options['width']; ?>px; height: <?php echo $options['height']; ?>px;">
<?php

$myrows = $wpdb->get_results("SELECT * FROM $table_name WHERE slideshow_name = '".$arg."' ORDER BY position ASC"); // order by
foreach ($myrows as $row) {
	echo url('start', $row->link);
	$search  = array('"', "'");
	$replace = array("", "");
	echo '<img src="'.$row->url.'" alt="'.str_replace($search, $replace, $row->description).'" />';
	echo "\n";
	echo '<span>'.$row->description.'</span>';
	echo "\n";
	echo url('end', '');
}
?>
</div>
<?php
}

function url($var1, $var2){
	if ($var2 == 'http://' || $var2 == '') {
		$output = '<a href="javascript:void(0);">'. 
		"\n";
	}
	if ($var1 == 'start') {
		if($var2 == 'http://' || $var2 == '') {
			$output = '<a href="javascript:void(0);">'. 
			"\n";
		} else {
			$output = '<a href="'.$var2.'">'. 
			"\n";
		}
	}
	if ($var1 == 'end') {
		$output = '</a>'. 
		"\n";
	}
	return $output;
}

/* Uninstall */
if ( function_exists('register_uninstall_hook')) {
	register_uninstall_hook(__FILE__, 'delete_slideshow_database');
}

// Delete leftovers in database
function delete_slideshow_database() {
	global $wpdb, $table_name;
	//build our query to delete our custom table
	$sql = "DROP TABLE " . $table_name . ";";
	//execute the query deleting the table
	$wpdb->query($sql);
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}

?>