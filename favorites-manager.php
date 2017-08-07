<?php
/*
Plugin Name: Favorites Menu Manager
Plugin URI: http://www.webmaster-source.com/wordpress-favorites-menu-manager/
Description: Control what goes into the favorite actions menu in WordPress 2.7+
Author: Matt Harzewski (redwall_hp)
Author URI: http://www.webmaster-source.com
Version: 1.0.0
*/


//***** Hooks *****
add_action('admin_menu', 'wpfm_add_pages'); //Admin pages
add_filter('favorite_actions', 'wpfm_change_favorites', 10000); //Do it!
//***** End Hooks *****




//***** Insert menu *****
function wpfm_add_pages () {
	add_submenu_page("users.php", "Manage Favorites Menu", "Edit Favorites Menu", "manage_categories", __FILE__, "wpfm_adminmenu");
}




//***** Do the Favorite Swap *****
function wpfm_change_favorites ($actions) {
	global $current_user;
	$stored_actions = get_usermeta($current_user->ID, 'wpfm_menufavorites');
	if ( $stored_actions == FALSE ) {
		update_usermeta($current_user->ID, 'wpfm_menufavorites', $actions);
	} else {
		$actions = $stored_actions;
	}
	$current_url = explode("/wp-admin/", $_SERVER["REQUEST_URI"]);	$actions['users.php?page=favorites-menu-manager/favorites-manager.php&action=bookmark&fave_name='.urlencode(get_admin_page_title()).'&fave_link='.urlencode($current_url[1]).'#addfave'] = array('<strong><em>Bookmark This</strong></em>', 'edit_posts');
	return $actions;
}




//***** Menu *****
function wpfm_adminmenu () {
	global $_POST;
	echo '<div class="wrap">';
	screen_icon();
	echo '<h2>Manage Favorites Menu</h2>';
	if ($_GET['action'] != 'edit') {
		if ($_POST['action']=='addfave') { wpfm_adminmenu_addthefave($_POST['fave_name'], $_POST['fave_link']); }
		if ($_POST['action']=='editfave') { wpfm_adminmenu_editfave_save($_POST['fave_name'], $_POST['fave_link']); }
		if ($_GET['action']=="bookmark") { $get_fave_name = urldecode($_GET['fave_name']); urldecode($get_fave_link = $_GET['fave_link']); }
		if ($_GET['action']=="remove") { wpfm_adminmenu_removethefave($_GET['fave']); }
		if ($_GET['action']=="moveup") { wpfm_adminmenu_sortfaves_up($_GET['pos']); }
		if ($_GET['action']=="movedown") { wpfm_adminmenu_sortfaves_down($_GET['pos']); }
		wpfm_adminmenu_showtable();
		wpfm_adminmenu_showform($get_fave_name, $get_fave_link);
		wpfm_adminmenu_footer();
	} else { wpfm_adminmenu_editfave_form($_GET['fave']); }
	echo '</div>';
}


//***** Menu: Table *****
function wpfm_adminmenu_showtable () {
	global $current_user;
	$actions = get_usermeta($current_user->ID, 'wpfm_menufavorites');
	echo '<table class="widefat">
	<thead><tr>
	<th scope="col">Position</th>
	<th scope="col">Menu Item</th>
	<th scope="col" style="width:120px;"></th>
	<th scope="col"></th>
	<th scope="col"></th>
	<th scope="col"></th>
	<th scope="col"></th>
	<th scope="col" style="text-align:right;"><a href="#addfave" class="button rbutton">Add New</a></th>
	</tr></thead>
	<tbody>';
	$counter = 1;
	foreach ($actions as $page => $action) {
		echo '<tr>';
		echo '<td>'.$counter.'</td>';
		echo '<td><strong><a href="'.$page.'">'.$action[0].'</a></strong></td>';
		echo '<td></td>';
		echo '<td><a href="users.php?page=favorites-menu-manager/favorites-manager.php&action=moveup&pos='.$counter.'">Move Up</a></td>';
		echo '<td><a href="users.php?page=favorites-menu-manager/favorites-manager.php&action=movedown&pos='.$counter.'">Move Down</a></td>';
		echo '<td><a href="users.php?page=favorites-menu-manager/favorites-manager.php&action=edit&fave='.$page.'#addfave">Edit</a></td>';
		echo '<td><a href="users.php?page=favorites-menu-manager/favorites-manager.php&action=remove&fave='.$page.'" class="delete">Remove</a></td>';
		echo '<td></td>';
		echo '</tr>';
		$counter++;
	}
	echo '</tbody></table>';
//echo '<br/><br/><pre>'; print_r($actions); echo '</pre>';
}


//***** Menu: Add Form *****
function wpfm_adminmenu_showform ($get_fave_name, $get_fave_link) {
	?>
	<br /><br />
	<div class="form-wrap">
	<a name="addfave"></a>
	<h3>Add Favorite</h3>
	<form name="addfave" id="addfave" method="post" action="users.php?page=favorites-menu-manager/favorites-manager.php">
	<input type="hidden" name="action" value="addfave" />

	<div class="form-field form-required">
	<label for="fave_name"><strong>Favorite Name</strong></label>
	<input name="fave_name" id="fave_name" type="text" value="<?php echo $get_fave_name; ?>" size="40" aria-required="true" />
    <p>The name of the page you want to add to the menu.</p>
	</div>

	<div class="form-field form-required">
	<label for="fave_link"><strong>Favorite Link</strong></label>
	<input name="fave_link" id="fave_link" type="text" value="<?php echo $get_fave_link; ?>" size="40" aria-required="true" />
    <p>The location of the Admin page you wish to add. Use the portion after <strong>wp-admin/</strong>. Examples: <strong>edit.php</strong>, <strong>widgets.php</strong>, <strong>admin.php?page=wp125/wp125.php</strong></p>
	</div>

	<p class="submit"><input type="submit" class="button-primary" name="submit" value="Add Favorite" /></p>
	</form></div>
	<?php
}



function wpfm_adminmenu_addthefave ($favename, $favelink) {
	global $current_user;
	if ($favename != "" AND $favelink != "") {
		$actions = get_usermeta($current_user->ID, 'wpfm_menufavorites');
		$actions[$favelink] = array($favename, 'edit_posts');
		update_usermeta($current_user->ID, 'wpfm_menufavorites', $actions);
	}
}



function wpfm_adminmenu_removethefave ($thefave) {
	global $current_user;
	$actions = get_usermeta($current_user->ID, 'wpfm_menufavorites');
	unset($actions[$thefave]);
	update_usermeta($current_user->ID, 'wpfm_menufavorites', $actions);
}



function wpfm_adminmenu_sortfaves_up ($pos) {
	$pos=$pos-1;
	global $current_user;
	$actions = get_usermeta($current_user->ID, 'wpfm_menufavorites');
	if ($pos-1 < 0) { return; }
	$pagelist = array();
	foreach($actions as $page => $action) {
		$pagelist[] = $page;
	}
	$tmp_plv = $pagelist[$pos-1];
	$pagelist[$pos-1] = $pagelist[$pos];
	$pagelist[$pos] = $tmp_plv;	
	foreach($pagelist as $pagelist) {
		$newactions[$pagelist] = $actions[$pagelist];
	}
	update_usermeta($current_user->ID, 'wpfm_menufavorites', $newactions);
}



function wpfm_adminmenu_sortfaves_down ($pos) {
	$pos=$pos-1;
	global $current_user;
	$actions = get_usermeta($current_user->ID, 'wpfm_menufavorites');
	$numactions = count($actions);
	if ($pos+2 > $numactions) { return; }
	$pagelist = array();
	foreach($actions as $page => $action) {
		$pagelist[] = $page;
	}
	$tmp_plv = $pagelist[$pos+1];
	$pagelist[$pos+1] = $pagelist[$pos];
	$pagelist[$pos] = $tmp_plv;	
	foreach($pagelist as $pagelist) {
		$newactions[$pagelist] = $actions[$pagelist];
	}
	update_usermeta($current_user->ID, 'wpfm_menufavorites', $newactions);
}



function wpfm_adminmenu_editfave_form ($thefave) {
	global $current_user;
	$actions = get_usermeta($current_user->ID, 'wpfm_menufavorites');
	?>
	<div class="form-wrap">
	<h3>Edit Favorite</h3>
	<form name="editfave" id="editfave" method="post" action="users.php?page=favorites-menu-manager/favorites-manager.php">
	<input type="hidden" name="action" value="editfave" />

	<div class="form-field form-required">
	<label for="fave_name"><strong>Favorite Name</strong></label>
	<input name="fave_name" id="fave_name" type="text" value="<?php echo $actions[$thefave][0]; ?>" size="40" aria-required="true" />
    <p>The name of the page you want to add to the menu.</p>
	</div>

	<div class="form-field form-required">
	<label for="fave_link"><strong>Favorite Link</strong></label>
	<input name="fave_link" id="fave_link" type="text" value="<?php echo $thefave; ?>" size="40" aria-required="true" />
    <p>The location of the Admin page you wish to add. Use the portion after <strong>wp-admin/</strong>. Examples: <strong>edit.php</strong>, <strong>widgets.php</strong>, <strong>admin.php?page=wp125/wp125.php</strong></p>
	</div>

	<p class="submit"><input type="submit" class="button-primary" name="submit" value="Edit Favorite" /></p>
	</form></div>
	<?php
	wpfm_adminmenu_footer();
}



function wpfm_adminmenu_editfave_save ($favename, $favelink) {
	global $current_user;
	if ($favename != "" AND $favelink != "") {
		$actions = get_usermeta($current_user->ID, 'wpfm_menufavorites');
		$actions[$favelink] = array($favename, 'edit_posts');
		update_usermeta($current_user->ID, 'wpfm_menufavorites', $actions);
	}
}



function wpfm_adminmenu_footer() {
echo '<div style="margin-top:45px; font-size:0.87em;">';
echo '<div style="float:right;"><a href="http://www.webmaster-source.com/donate/?plugin=wpfmm" title="Why should you donate a few dollars? Click to find out..."><img src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" alt="Donate" /></a></div>';
echo '<div><a href="'.WP_CONTENT_URL.'/plugins/favorites-menu-manager/readme.txt">Documentation</a> | <a href="http://www.webmaster-source.com/wordpress-favorites-menu-manager/">Plugin Homepage</a></div>';
echo '</div>';
}




/*
Copyright 2009 Matt Harzewski

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

?>