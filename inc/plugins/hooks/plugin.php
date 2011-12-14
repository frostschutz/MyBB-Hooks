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

/**
 * Check if the plugin is installed.
 */
function hooks_is_installed()
{
    global $db;

    return $db->table_exists('hooks');
}

/**
 * Install the plugin.
 */
function hooks_install()
{
    global $db;

    hooks_depend();

    if(!$db->table_exists('hooks'))
    {
        $collation = $db->build_create_table_collation();
        $prefix = TABLE_PREFIX;

        switch($db->type)
        {
            case 'sqlite':
                $quote = '"';
                $primary = 'INTEGER NOT NULL';
                break;

            case 'postgres':
                $quote = '"';
                $primary = 'SERIAL NOT NULL';
                break;

            default:
                // Assume MySQL
                $quote = '`';
                $primary = 'INTEGER NOT NULL AUTO_INCREMENT';
        }

        $db->write_query("
            CREATE TABLE {$quote}{$prefix}hooks{$quote}
            (
                hid {$primary},
                hactive TINYINT UNSIGNED NOT NULL,
                hpriority BIGINT NOT NULL,
                hhook VARCHAR(150) NOT NULL,
                htitle VARCHAR(100) NOT NULL,
                hdescription VARCHAR(200),
                hcode TEXT NOT NULL,
                PRIMARY KEY (hid)
            ) {$collation}");

        $db->write_query("CREATE INDEX hactivehookpriority
                          ON {$quote}{$prefix}hooks{$quote}
                          (hactive,hhook,hpriority)");
    }
}

/**
 * Uninstall the plugin.
 */
function hooks_uninstall()
{
    global $db;

    if($db->table_exists('hooks'))
    {
        $db->drop_table('hooks');
    }
}


/**
 * Activate the plugin.
 */
function hooks_activate()
{
    hooks_depend();
}

/**
 * Deactivate the plugin.
 */
function hooks_deactivate()
{
    // do nothing
}


/* --- Helpers: --- */

/**
 * Plugin Dependencies
 */
function hooks_depend()
{
    global $lang, $PL;

    $lang->load('hooks');

    if(!file_exists(PLUGINLIBRARY))
    {
        flash_message($lang->hooks_PL, 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    $PL or require_once PLUGINLIBRARY;

    if($PL->version < 5)
    {
        flash_message($lang->hooks_PL_old, 'error');
        admin_redirect("index.php?module=config-plugins");
    }
}

/* --- Hook functions: --- */

/**
 * Add Hooks tab on the plugins page.
 */
function hooks_tabs_start(&$arguments)
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

/**
 * Output preview.
 */
function hooks_output_preview()
{
    global $mybb, $lang;

    require_once MYBB_ROOT."inc/class_parser.php";
    $parser = new postParser;

    $code = str_replace("\n", "\n    ", "\n{$mybb->input['hcode']}");
    $code = substr($code, 1);
    $code = $parser->mycode_parse_php(
        "function hooks_{$mybb->input['hhook']}(&\$arg)\n{\n{$code}\n}",
        true);

    $table = new Table;
    $table->construct_cell($code);
    $table->construct_row();
    $table->output($lang->hooks_preview_output);
}

/* --- Actions: --- */

function hooks_action_activate()
{
    global $mybb, $db, $lang;

    if(!verify_post_check($mybb->input['my_post_key']))
    {
        flash_message($lang->hooks_error_key, 'error');
        admin_redirect(HOOKS_URL);
    }

    $hook = intval($mybb->input['hook']);

    if($hook)
    {
        $db->update_query('hooks', array('hactive' => '1'), "hid={$hook}");
        flash_message($lang->hooks_activated, 'success');
    }

    admin_redirect(HOOKS_URL);
}

function hooks_action_deactivate()
{
    global $mybb, $db, $lang;

    if(!verify_post_check($mybb->input['my_post_key']))
    {
        flash_message($lang->hooks_error_key, 'error');
        admin_redirect(HOOKS_URL);
    }

    $hook = intval($mybb->input['hook']);

    if($hook)
    {
        $db->update_query('hooks', array('hactive' => '0'), "hid={$hook}");
        flash_message($lang->hooks_deactivated, 'success');
    }

    admin_redirect(HOOKS_URL);
}

function hooks_action_delete()
{
    global $mybb, $db, $lang;

    if(!verify_post_check($mybb->input['my_post_key']))
    {
        flash_message($lang->hooks_error_key, 'error');
        admin_redirect(HOOKS_URL);
    }

    $hook = intval($mybb->input['hook']);

    if($hook)
    {
        $db->delete_query('hooks', "hid={$hook}");
        flash_message($lang->hooks_deleted, 'success');
    }

    admin_redirect(HOOKS_URL);
}

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

    $query = $db->simple_select('hooks',
                                'hid,hpriority,hhook,htitle,hdescription,hactive',
                                '',
                                array('order_by' => 'hhook,hpriority,htitle,hid'));

    $hook = '';

    while($row = $db->fetch_array($query))
    {
        if($row['hhook'] != $hook)
        {
            $hook = $row['hhook'];

            $table->construct_cell('<strong>'.htmlspecialchars($row['hhook']).'</strong>');
            $table->construct_cell('', array('class' => 'align_center'));
            $table->construct_cell('', array('class' => 'align_center',
                                             'width' => '15%'));
            $table->construct_row();
        }

        $editurl = $PL->url_append(HOOKS_URL,
                                   array('mode' => 'edit',
                                         'hook' => $row['hid']));

        $delete = '';


        if(!$row['hactive'])
        {
            $deleteurl = $PL->url_append(HOOKS_URL,
                                         array('mode' => 'delete',
                                               'hook' => $row['hid'],
                                               'my_post_key' => $mybb->post_code));
            $delete = " <a href=\"{$deleteurl}\"><img src=\"styles/{$page->style}/images/icons/delete.gif\" alt=\"{$lang->hooks_delete}\" title=\"{$lang->hooks_delete}\" /></a>";
        }

        $table->construct_cell("<div style=\"padding-left: 40px;\"><a href=\"{$editurl}\">"
                               .htmlspecialchars($row['htitle'])
                               .'</a>'
                               .$delete
                               .'<br />'
                               .htmlspecialchars($row['hdescription'])
                               .'</div>');

        if(!$row['hactive'])
        {
            $activateurl = $PL->url_append(HOOKS_URL,
                                           array('mode' => 'activate',
                                                 'hook' => $row['hid'],
                                                 'my_post_key' => $mybb->post_code));

            $table->construct_cell("<a href=\"{$activateurl}\">{$lang->hooks_activate}</a>",
                                   array('class' => 'align_center',
                                         'width' => '15%'));
        }

        else
        {
            $deactivateurl = $PL->url_append(HOOKS_URL,
                                             array('mode' => 'deactivate',
                                                   'hook' => $row['hid'],
                                                   'my_post_key' => $mybb->post_code));

            $table->construct_cell("<a href=\"{$deactivateurl}\">{$lang->hooks_deactivate}</a>",
                                   array('class' => 'align_center',
                                         'width' => '15%'));
        }

        if($row['hactive'])
        {
            $table->construct_cell("<img src=\"styles/{$page->style}/images/icons/tick.gif\" alt=\"{$lang->hooks_tick}\" />",
                                   array('class' => 'align_center'));

            $exportids[] = $row['hid'];
        }

        else
        {
            $table->construct_cell("<img src=\"styles/{$page->style}/images/icons/cross.gif\" alt=\"{$lang->hooks_cross}\" />",
                                   array('class' => 'align_center'));
        }

        $table->construct_row();
    }

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

/**
 * Hooks edit page.
 */
function hooks_page_edit()
{
    global $mybb, $db, $lang, $page, $PL;
    $PL or require_once PLUGINLIBRARY;

    $lang->load('hooks');

    $hid = intval($mybb->input['hook']);

    if($mybb->request_method == 'post')
    {
        if($mybb->input['cancel'])
        {
            admin_redirect(HOOKS_URL);
        }

        // validate input

        $errors = array();

        $hook = trim($mybb->input['hhook']);

        if(!strlen($hook))
        {
            $errors[] = $lang->hooks_error_hook;
        }

        $title = trim($mybb->input['htitle']);

        if(!$title)
        {
            $errors[] = $lang->hooks_error_title;
        }

        $description = trim($mybb->input['hdescription']);
        // description is optional

        $priority = intval($mybb->input['hpriority']);
        // priority is optional

        if(trim($mybb->input['hpriority']) !== strval($priority))
        {
            // default priority
            $priority = 10;
        }

        $code = $mybb->input['hcode'];

        if(!trim($code))
        {
            $errors[] = $lang->hooks_error_code;
        }

        if(!$errors && !$mybb->input['preview'])
        {
            $data = array(
                'htitle' => $db->escape_string($title),
                'hdescription' => $db->escape_string($description),
                'hhook' => $db->escape_string($hook),
                'hpriority' => $priority,
                'hcode' => $db->escape_string($code),
                );

            if($hid)
            {
                $update = $db->update_query('hooks',
                                            $data,
                                            "hid={$hid}");
            }

            if(!$update)
            {
                $db->insert_query('hooks', $data);
            }

            flash_message($lang->hooks_saved, 'success');
            admin_redirect(HOOKS_URL);
        }

        // Show a preview
        $preview = true;
    }

    else if($hid > 0)
    {
        // fetch info of existing hook
        $query = $db->simple_select('hooks',
                                    'htitle,hdescription,hhook,hpriority,hcode',
                                    "hid='{$hid}'");
        $row = $db->fetch_array($query);

        if($row)
        {
            $mybb->input = array_merge($mybb->input, $row);
        }
    }

    // Header stuff.
    $editurl = $PL->url_append(HOOKS_URL, array('mode' => 'edit'));

    $page->add_breadcrumb_item($lang->hooks_edit, $editurl);

    hooks_output_header();
    hooks_output_tabs();

    if($errors)
    {
        $page->output_inline_error($errors);
    }

    else if($preview)
    {
        hooks_output_preview();
    }

    $form = new Form($editurl, 'post');
    $form_container = new FormContainer($lang->hooks_edit);

    echo $form->generate_hidden_field('hook',
                                      intval($mybb->input['hook']),
                                      array('id' => 'hook'));

    $form_container->output_row(
        $lang->hooks_hook,
        $lang->hooks_hook_desc,
        $form->generate_text_box('hhook',
                                 trim($mybb->input['hhook']),
                                 array('id' => 'hhook')),
        'hhook'
        );

    $form_container->output_row(
        $lang->hooks_title,
        $lang->hooks_title_desc,
        $form->generate_text_box('htitle',
                                 trim($mybb->input['htitle']),
                                 array('id' => 'htitle')),
        'htitle'
        );

    $form_container->output_row(
        $lang->hooks_description,
        $lang->hooks_description_desc,
        $form->generate_text_box('hdescription',
                                 trim($mybb->input['hdescription']),
                                 array('id' => 'hdescription')),
        'hdescription'
        );

    $form_container->output_row(
        $lang->hooks_priority,
        $lang->hooks_priority_desc,
        $form->generate_text_box('hpriority',
                                 $mybb->input['hpriority'],
                                 array('id' => 'hpriority')),
        'hpriority'
        );

    $form_container->output_row(
        $lang->hooks_code,
        $lang->hooks_code_desc,
        $form->generate_text_area('hcode',
                                  $mybb->input['hcode'],
                                  array('id' => 'hcode')),
        'hcode'
        );

    $form_container->end();

    $buttons[] = $form->generate_submit_button($lang->hooks_save);
    $buttons[] = $form->generate_submit_button($lang->hooks_preview,
                                               array('name' => 'preview'));
    $buttons[] = $form->generate_submit_button($lang->hooks_cancel,
                                               array('name' => 'cancel'));

    $form->output_submit_wrapper($buttons);
    $form->end();

    $page->output_footer();
}

/**
 * Import hooks
 */
function hooks_page_import()
{
    global $mybb, $db, $lang, $page, $PL;
    $PL or require_once PLUGINLIBRARY;

    $importurl = $PL->url_append(HOOKS_URL, array('mode' => 'import'));

    $page->add_breadcrumb_item($lang->hooks_import, $importurl);

    if($mybb->request_method == 'post')
    {
        if($mybb->input['cancel'])
        {
            admin_redirect(HOOKS_URL);
        }

        if(@is_uploaded_file($_FILES['hooks']['tmp_name']))
        {
            $contents = @file_get_contents($_FILES['hooks']['tmp_name']);
            @unlink($_FILES['hooks']['tmp_name']);

            if($contents)
            {
                $contents = $PL->xml_import($contents);
                $inserts = array();
                $errors = 0;

                if(is_array($contents))
                {
                    foreach($contents as $hook)
                    {
                        if(!is_array($hook))
                        {
                            $errors++;
                            continue;
                        }

                        if(!is_string($hook['hhook'])
                           || !strlen($hook['hhook'])
                           || !is_string($hook['htitle'])
                           || !strlen($hook['htitle'])
                           || !is_int($hook['hpriority'])
                           || !is_string($hook['hcode'])
                           || !strlen($hook['hcode']))
                        {
                            $errors++;
                            continue;
                        }

                        $inserts[] = array(
                            'hactive' => '0',
                            'hhook' => $db->escape_string($hook['hhook']),
                            'htitle' => $db->escape_string($hook['htitle']),
                            'hdescription' => $db->escape_string($hook['hdescription']),
                            'hpriority' => intval($hook['hpriority']),
                            'hcode' => $db->escape_string($hook['hcode']),
                            );
                    }
                }

                if(count($inserts))
                {
                    $db->insert_query_multiple('hooks', $inserts);

                    $success = $lang->sprintf($lang->hooks_import_success,
                                              count($inserts));

                    if($errors)
                    {
                        $success .= $lang->sprintf($lang->hooks_import_errors,
                                                   $errors);
                    }

                    flash_message($success, 'success');
                    admin_redirect(HOOKS_URL);
                }
            }
        }

        if(is_array($inserts) || $errors)
        {
            flash_message($lang->hooks_import_badfile, 'error');
        }

        else
        {
            flash_message($lang->hooks_import_nofile, 'error');
        }
    }

    hooks_output_header();
    hooks_output_tabs();

    $table = new Table;

    $table->construct_header($lang->hooks);

    $form = new Form($importurl, 'post', '', 1);

    $table->construct_cell($lang->hooks_import_file
                           .'<br /><br />'
                           .$form->generate_file_upload_box("hooks"));
    $table->construct_row();

    $table->output($lang->hooks_import_caption);

    $buttons[] = $form->generate_submit_button($lang->hooks_import_button);
    $buttons[] = $form->generate_submit_button($lang->hooks_cancel,
                                               array('name' => 'cancel'));
    $form->output_submit_wrapper($buttons);

    $page->output_footer();
}

/**
 * Export hooks
 */
function hooks_page_export()
{
    global $mybb, $db, $lang, $page, $PL;

    $PL or require_once PLUGINLIBRARY;

    $exporturl = $PL->url_append(HOOKS_URL, array('mode' => 'export'));

    $page->add_breadcrumb_item($lang->hooks_export, $exporturl);

    if($mybb->request_method == 'post')
    {
        if($mybb->input['cancel'])
        {
            admin_redirect(HOOKS_URL);
        }

        if($mybb->input['filename'])
        {
            $filename = $mybb->input['filename'];
            $filename = str_replace('/', '_', $filename);
            $filename = str_replace('\\', '_', $filename);
            $filename = str_replace('.', '_', $filename);
            $filename = "hooks-{$filename}.xml";
        }

        else
        {
            $filename = "hooks.xml";
        }

        if($mybb->input['hooks'])
        {
            $where = array();

            foreach((array)$mybb->input['hooks'] as $hid)
            {
                $where[] = $db->escape_string(strval($hid));
            }

            $where = implode("','", $where);
            $where = "hid IN ('{$where}')";

            $query = $db->simple_select("hooks",
                                        "hhook,htitle,hdescription,hpriority,hcode",
                                        $where,
                                        array('order_by' => 'hhook,htitle,hid'));

            $hooks = array();

            while($row = $db->fetch_array($query))
            {
                $row['hpriority'] = intval($row['hpriority']);
                $hooks[] = $row;
            }

            if(count($hooks))
            {
                $PL->xml_export($hooks, $filename, 'MyBB Hooks exported {time}');
                // exit on success
            }
        }

        flash_message($lang->hooks_export_error, 'error');
    }

    else if($mybb->input['hook'])
    {
        $hooks = array();

        foreach(explode(",", $mybb->input['hook']) as $hid)
        {
            $hooks[] = htmlspecialchars($hid);
        }
    }

    hooks_output_header();
    hooks_output_tabs();

    // Build list of hooks
    $hooks_selects = array();
    $currenthook = '';

    $query = $db->simple_select('hooks', 'hhook,htitle,hid', '',
                                array('order_by' => 'hhook,htitle,hid'));

    while($row = $db->fetch_array($query))
    {
        if($currenthook != $row['hhook'])
        {
            $currenthook = $row['hhook'];
            $hooks_selects["hook{$row['hid']}"] = '&nbsp;&nbsp;&nbsp;--- '.htmlspecialchars($currenthook).' ---';
        }

        $hooks_selects[$row['hid']] = htmlspecialchars($row['htitle']);
    }

    $table = new Table;

    $table->construct_header($lang->hooks);

    $form = new Form($exporturl, "post");

    $table->construct_cell($lang->hooks_export_select
                           .'<br /><br />'
                           .$form->generate_select_box("hooks[]", $hooks_selects, $hooks, array('multiple' => true, 'id' => 'hooks_select')));
    $table->construct_row();

    $table->construct_cell($lang->hooks_export_filename
                           .'<br /><br />'
                           .$form->generate_text_box('filename', $mybb->input['filename']));
    $table->construct_row();

    $table->output($lang->hooks_export_caption);

    $buttons[] = $form->generate_submit_button($lang->hooks_export_button);
    $buttons[] = $form->generate_submit_button($lang->hooks_cancel,
                                               array('name' => 'cancel'));
    $form->output_submit_wrapper($buttons);

    $page->output_footer();
}

/* --- End of file. --- */
?>
