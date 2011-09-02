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
 * Return information about the Hooks plugin.
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
 * Handle active Hooks tab case on the plugins page.
 */
function hooks_plugins_begin()
{
    global $mybb, $lang, $page;

    if($mybb->input['action'] == 'hooks')
    {
        $lang->load('hooks');

        $page->add_breadcrumb_item($lang->hooks, HOOKS_URL);

        switch($mybb->input['mode'])
        {
            case 'activate':
                hooks_action_activate();
                break;

            case 'deactivate':
                hooks_action_deactivate();
                break;

            case 'delete':
                hooks_action_delete();
                break;

            case 'edit':
                hooks_page_edit();
                break;

            case 'import':
                hooks_page_import();
                break;

            case 'export':
                hooks_page_export();
                break;

            default:
                hooks_page();
                break;
        }
    }
}

/* --- Output functions: --- */

/**
 * Output tabs with Hooks as active.
 */
function hooks_output_tabs()
{
    global $page, $lang;

    $sub_tabs['plugins'] = array(
        'title' => $lang->plugins,
        'link' => 'index.php?module=config-plugins',
        'description' => $lang->plugins_desc
        );

    $sub_tabs['update_plugins'] = array(
        'title' => $lang->plugin_updates,
        'link' => 'index.php?module=config-plugins&amp;action=check',
        'description' => $lang->plugin_updates_desc
        );

    $sub_tabs['browse_plugins'] = array(
        'title' => $lang->browse_plugins,
        'link' => "index.php?module=config-plugins&amp;action=browse",
        'description' => $lang->browse_plugins_desc
        );


    // The missing Hooks tab will be added in the tab_start hook.

    $page->output_nav_tabs($sub_tabs, 'hooks');
}

/**
 * Output header.
 */
function hooks_output_header()
{
    global $page;

    $page->output_header('Hooks');
}

/* --- Actions: --- */

/* --- Pages: --- */

/**
 * The hooks main page.
 */
function hooks_page()
{
    global $mybb, $db, $lang, $page, $PL;
    $PL or require_once PLUGINLIBRARY;

    hooks_output_header();
    hooks_output_tabs();

    $exportids = array();

    $table = new Table;
    $table->construct_header($lang->hooks_hook);
    $table->construct_header($lang->hooks_controls,
                             array('colspan' => 3,
                                   'class' => 'align_center',
                                   'width' => '30%'));

    $createurl = $PL->url_append(HOOKS_URL, array('mode' => 'edit'));
    $importurl = $PL->url_append(HOOKS_URL, array('mode' => 'import'));
    $exporturl = $PL->url_append(HOOKS_URL, array('mode' => 'export',
                                                  'hook' => implode(",", $exportids)));

    $table->construct_cell("<img src=\"styles/{$page->style}/images/icons/custom.gif\" /> <a href=\"{$createurl}\">{$lang->hooks_new}</a> ",
                           array('class' => 'align_center'));
    $table->construct_cell("<img src=\"styles/{$page->style}/images/icons/increase.gif\" /> <a href=\"{$importurl}\">{$lang->hooks_import}</a> ",
                           array('class' => 'align_center'));
    $table->construct_cell("<img src=\"styles/{$page->style}/images/icons/decrease.gif\" /> <a href=\"{$exporturl}\">{$lang->hooks_export}</a>",
                           array('class' => 'align_center'));

    $table->construct_row();

    $table->output($lang->hooks);

    // legend
    echo "
<ul class=\"smalltext\">
    <li style=\"list-style-image: url(styles/{$page->style}/images/icons/tick.gif)\" />
        {$lang->hooks_legend_tick}
    </li>
    <li style=\"list-style-image: url(styles/{$page->style}/images/icons/cross.gif)\" />
        {$lang->hooks_legend_cross}
    </li>
</ul>
";

    $page->output_footer();
}

/* --- End of file. --- */
?>
