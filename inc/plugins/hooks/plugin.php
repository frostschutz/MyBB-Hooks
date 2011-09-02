<?php
/**
 * This file is part of Hooks plugin for MyBB.
 * Copyright (C) 2011 Andreas Klauer <Andreas.Klauer@metamorpher.de>
 *
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
 *
 */

// Disallow direct access to this file for security reasons.
if(!defined('IN_MYBB'))
{
    die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

if(!defined("PLUGINLIBRARY"))
{
    define("PLUGINLIBRARY", MYBB_ROOT."inc/plugins/pluginlibrary.php");
}

define('HOOKS_URL', 'index.php?module=config-plugins&amp;action=hooks');

/* --- Hooks: --- */

global $plugins;

$plugins->add_hook('admin_page_output_nav_tabs_start', 'hooks_tabs_start');
$plugins->add_hook('admin_config_plugins_begin', 'hooks_plugins_begin');

/* --- Plugin API: --- */

/**
 * Return information about the patches plugin.
 */
function hooks_info()
{
    global $lang;

    $lang->load('hooks');

    return array(
        'name'          => $lang->hooks,
        'description'   => $lang->hooks_desc,
        'website'       => 'http://mods.mybb.com/view/hooks',
        'author'        => 'Andreas Klauer',
        'authorsite'    => 'mailto:Andreas.Klauer@metamorpher.de',
        'version'       => '1.0',
        'guid'          => '',
        'compatibility' => '16*',
    );
}

/* --- Helpers: --- */

/* --- Hook functions: --- */

/**
 * Add Hooks tab on the plugins page.
 */
function hooks_tabs_start($arguments)
{
    global $mybb, $lang;

    $lang->load('hooks');

    if($mybb->input['module'] == 'config-plugins')
    {
        $arguments['hooks'] = array('title' => $lang->hooks,
                                    'description' => $lang->hooks_tab_desc,
                                    'link' => HOOKS_URL);
    }
}

/**
 * Handle active Patches tab case on the plugins page.
 */
function hooks_plugins_begin()
{
    global $mybb, $lang, $page;

    if($mybb->input['action'] == 'hooks')
    {

    }
}

/* --- End of file. --- */
?>
