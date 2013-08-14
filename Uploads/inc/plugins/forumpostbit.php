<?php
/**
 * Forum Post Bit
 * Copyright 2013 Jacque Schrag
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook('showthread_start', 'forumpostbit_force');
$plugins->add_hook('postbit', 'forumpostbit_force');
$plugins->add_hook('postbit_prev', 'forumpostbit_force');
$plugins->add_hook("admin_formcontainer_output_row", "forumpostbit_forum");
$plugins->add_hook("admin_forum_management_add", "forumpostbit_forum_add");
$plugins->add_hook("admin_forum_management_edit_commit", "forumpostbit_forum_commit");
$plugins->add_hook("admin_forum_management_add_commit", "forumpostbit_forum_commit");


function forumpostbit_info()
{
	return array(
		"name"			=> "Forum Post Bit",
		"description"	=> "Allows admins to determine the postbit layout for each forum.",
		"website"		=> "https://github.com/jnschrag/mybb-Forumpostbit.git",
		"author"		=> "Jacque Schrag",
		"authorsite"	=> "http://jacqueschrag.com",
		"version"		=> "1.0",
		"guid" 			=> "",
		"compatibility" => "16*"
	);
}

// This function runs when the plugin is installed.
function forumpostbit_install()
{
	global $db, $cache;
	forumpostbit_uninstall();

	$db->add_column("forums", "postbit_layout", "VARCHAR( 100 ) NOT NULL");
	$cache->update_forums();
}

// Checks to make sure plugin is installed
function forumpostbit_is_installed()
{
	global $db;
	if($db->field_exists("postbit_layout", "forums"))
	{
		return true;
	}
	return false;
}

// This function runs when the plugin is uninstalled.
function forumpostbit_uninstall()
{
	global $db, $cache;
	if($db->field_exists("postbit_layout", "forums"))
	{
		$db->drop_column("forums", "postbit_layout");
	}
	$cache->update_forums();

}

//Add option to forum management page
function forumpostbit_forum($above)
{
	global $mybb, $lang, $form, $forum_data, $form_container;
	
	if($above['title'] == $lang->misc_options && $lang->misc_options)
	{

		
		
		$create_a_options_horizontal = array(
			'id' => 'horizontal'
		);
		
		$create_a_options_classic = array(
			'id' => 'classic'
		);

		$create_a_options_default = array(
			'id' => 'default'
		);
		
		if($forum_data['postbit_layout'] == "horizontal")
		{
			$create_a_options_horizontal['checked'] = true;
		}
		elseif($forum_data['postbit_layout'] == "classic")
		{
			$create_a_options_classic['checked'] = true;
		}
		else {
			$create_a_options_default['checked'] = true;
		}


		$above['content'] .= "<div class=\"forum_settings_bit\">".$form_container->output_row("Postbit Type", "Select which postbit type you want to use for this forum.", $form->generate_radio_button('postbit_layout', 'horizontal', "Horizontal", $create_a_options_horizontal)."<br />\n".$form->generate_radio_button('postbit_layout', 'classic', "Classic", $create_a_options_classic)."<br />\n".$form->generate_radio_button('postbit_layout', 'default', "Default", $create_a_options_default))."</div>";
	}

	return $above;
}

function forumpostbit_forum_add()
{
	global $forum_data;
	$forum_data['postbit_layout'] = "default";
}

function forumpostbit_forum_commit()
{
	global $db, $mybb, $cache, $fid;

		if($mybb->input['postbit_layout'] == 'default') {
			$mybb->input['postbit_layout'] = "";
		}

		$update_array = array(
			"postbit_layout" => $db->escape_string($mybb->input['postbit_layout'])
		);

		$db->update_query("forums", $update_array, "fid='{$fid}'");

		$cache->update_forums();	
}

// Force thread to use the forum's default postbit style if one is set, otherwise use default. Overwrite's user's preference.
function forumpostbit_force($post)
{
	global $mybb, $db, $forum;

	if($forum['postbit_layout'] == "") {
		$forum['postbit_layout'] = $mybb->settings['postlayout'];
	}
	elseif($forum['postbit_layout'] != $mybb->settings['postlayout'] && $forum['postbit_layout'] != "") {
		$mybb->settings['postlayout'] = $forum['postbit_layout'];
	}

	$mybb->user['classicpostbit'] = ($mybb->settings['postlayout'] == 'classic') ? 1 : 0;
}