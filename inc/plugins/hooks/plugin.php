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
        'version'       => '1.5',
        'guid'          => '51897afbd949d567b9bf97e44800e508',
        'compatibility' => '18*',
        'codename'      => 'hooks',
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

            case 'pgsql':
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
                hargument VARCHAR(50),
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
    global $mybb, $db, $lang;
    global $PL;

    hooks_depend();

    // Confirmation step.
    if(!$mybb->input['confirm'])
    {
        $link = $PL->url_append('index.php', array(
                                    'module' => 'config-plugins',
                                    'action' => 'deactivate',
                                    'uninstall' => '1',
                                    'plugin' => 'hooks',
                                    'my_post_key' => $mybb->post_code,
                                    'confirm' => '1',
                                    ));

        flash_message("{$lang->hooks_plugin_uninstall} <a href=\"{$link}\">{$lang->hooks_plugin_uninstall_confirm}</a>", "error");
        admin_redirect("index.php?module=config-plugins");
    }

    if($db->table_exists('hooks'))
    {
        $db->drop_table('hooks');
    }

    @unlink(HOOKS_DATA);
}


/**
 * Activate the plugin.
 */
function hooks_activate()
{
    hooks_depend();
    hooks_update_data();
}

/**
 * Deactivate the plugin.
 */
function hooks_deactivate()
{
    // truncate the hooks file
    // activate recreates it from the DB
    @unlink(HOOKS_DATA);
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

    if((file_exists(HOOKS_DATA) && !@is_writable(HOOKS_DATA)) ||
       (!file_exists(HOOKS_DATA) && !@is_writable(dirname(HOOKS_DATA))))
    {
        $lackperms = $lang->sprintf($lang->hooks_error_write_permission,
                                    HOOKS_DATA);
        flash_message($lackperms, 'error');
        admin_redirect("index.php?module=config-plugins");
    }
}

/**
 * Generate code segment.
 */
function hooks_generate_code($hooks, $prefix='hooks')
{
    foreach($hooks as $row)
    {
        $row['htitle'] = strtr($row['htitle'], array('*/', '* /'));

        if($row['hargument'])
        {
            $arg = "&\${$row['hargument']}";
        }

        else
        {
            $arg = '';
        }

        $output[] = "\n/* --- Hook #{$row['hid']} - {$row['htitle']} --- */\n\n"
            ."\$plugins->add_hook('{$row['hhook']}','{$prefix}_{$row['hhook']}_{$row['hid']}',{$row['hpriority']});\n\n"
            ."function {$prefix}_{$row['hhook']}_{$row['hid']}({$arg})\n{\n"
            .$row['hcode']
            ."\n}\n";
    }

    return $output;
}

/**
 * Create / Update data file
 */
function hooks_update_data()
{
    global $db;

    $query = $db->simple_select('hooks', 'hid,hhook,hpriority,hargument,hcode,htitle',
                                'hactive=1',
                                array('order_by' => 'hhook,hpriority,hid'));

    while($row = $db->fetch_array($query))
    {
        $hooks[] = $row;
    }

    $output = array();

    if(count($hooks))
    {
        $output = hooks_generate_code($hooks);
    }

    $data = fopen(HOOKS_DATA, 'w');

    fwrite($data, "<?php\n/********** GENERATED BY HOOKS PLUGIN DO NOT EDIT **********/\n\n"
           ."if(!defined('IN_MYBB'))\n{\n    die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');\n}\n\n"
           ."global \$plugins;\n");

    foreach($output as $hook)
    {
        fwrite($data, $hook);
    }

    fwrite($data, "\n/********** GENERATED BY HOOKS PLUGIN DO NOT EDIT **********/\n?>\n");
}

/**
 * Validate code.
 *
 * PHP offers little in terms of safe code validation.
 * create_function() is a false friend: it's just another eval().
 * These crude tests are all eval() based and may execute arbitrary code.
 * Not a problem as anyone who creates hooks executes arbitrary code anyway.
 */
function hooks_create_function($arg, $code)
{
    $result = true;

    if(version_compare(PHP_VERSION, '5.3.0', '>='))
    {
        // closure test.
        $result = @eval("return function({$arg}) { {$code} };");
    }

    if($result !== false)
    {
        // blind if test.
        $result = @eval("if(0) { function xy({$arg}) { {$code} } } return 1;");
    }

    if($result !== false)
    {
        // blind if class test.
        $result = @eval("if(0) { class x { function y({$arg}) { {$code} } } } return 1;");
    }

    if($result !== false)
    {
        // create_function test.
        $result = @create_function($arg, $code);
    }

    return $result !== false;
}

function hooks_validate($hook, $arg, $code, &$errors)
{
    global $lang;

    // Validate Hook Name
    if(hooks_create_function('', "function hooks_{$hook}(){}") === false)
    {
        $errors[] = $lang->hooks_error_hook_invalid;
    }

    // Validate Arg
    if(strlen($arg))
    {
        $arg = "&\${$arg}";
    }

    else
    {
        $arg = '';
    }

    if($arg && hooks_create_function($arg, '') == false)
    {
        $errors[] = $lang->hooks_error_argument;
    }

    // Validate Code
    if(hooks_create_function('', $code) == false)
    {
        $errors[] = $lang->hooks_error_syntax;
    }
}

/* --- Hook functions: --- */

/**
 * Add Hooks tab on the plugins page.
 */
function hooks_tabs_start(&$arguments)
{
    global $mybb, $lang;

    if($mybb->input['module'] == 'config-plugins')
    {
        $lang->load('hooks');

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

    if(strlen($mybb->input['hargument']))
    {
        $arg = "&\${$mybb->input['hargument']}";
    }

    else
    {
        $arg = "";
    }

    $code = str_replace("\n", "\n    ", "\n{$mybb->input['hcode']}");
    $code = substr($code, 1);
    $code = $parser->mycode_parse_php(
        "function hooks_{$mybb->input['hhook']}({$arg})\n{\n{$code}\n}",
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
        // query hook data
        $query = $db->simple_select('hooks', 'hhook,hargument,hcode', "hid={$hook}");
        $row = $db->fetch_array($query);

        if($row)
        {
            $error = array();
            hooks_validate($row['hhook'], $row['hargument'], $row['hcode'], $error);

            if(!count($error))
            {
                $db->update_query('hooks', array('hactive' => '1'), "hid={$hook}");
                hooks_update_data();
                flash_message($lang->hooks_activated, 'success');
            }

            else
            {
                flash_message($lang->hooks_error_faulty, 'error');
            }
        }

        else
        {
            flash_message($lang->hooks_error_missing, 'error');
        }
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
        hooks_update_data();
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
        hooks_update_data();
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
    $table->construct_header($lang->hooks_controls,
                             array('colspan' => 2,
                                   'class' => 'align_center'));
    $table->construct_header($lang->hooks_hook,
                             array('width' => '100%'));

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

            $table->construct_cell('', array('colspan' => 2,
                                             'class' => 'align_center',
                                             'style' => 'white-space: nowrap;'));
            $table->construct_cell('<strong>'.htmlspecialchars($row['hhook']).'</strong>');
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
            $delete = " <a href=\"{$deleteurl}\"><img src=\"styles/{$page->style}/images/icons/delete.png\" alt=\"{$lang->hooks_delete}\" title=\"{$lang->hooks_delete}\" /></a>";
        }

        if(!$row['hactive'])
        {
            $activateurl = $PL->url_append(HOOKS_URL,
                                           array('mode' => 'activate',
                                                 'hook' => $row['hid'],
                                                 'my_post_key' => $mybb->post_code));

            $table->construct_cell("<a href=\"{$activateurl}\">{$lang->hooks_activate}</a>",
                                   array('class' => 'align_center',
                                         'style' => 'white-space: nowrap;'));
        }

        else
        {
            $deactivateurl = $PL->url_append(HOOKS_URL,
                                             array('mode' => 'deactivate',
                                                   'hook' => $row['hid'],
                                                   'my_post_key' => $mybb->post_code));

            $table->construct_cell("<a href=\"{$deactivateurl}\">{$lang->hooks_deactivate}</a>",
                                   array('class' => 'align_center',
                                         'style' => 'white-space: nowrap;'));
        }

        if($row['hactive'])
        {
            $table->construct_cell("<img src=\"styles/{$page->style}/images/icons/tick.png\" alt=\"{$lang->hooks_tick}\" />",
                                   array('class' => 'align_center',
                                         'style' => 'white-space: nowrap;'));

            $exportids[] = $row['hid'];
        }

        else
        {
            $table->construct_cell("<img src=\"styles/{$page->style}/images/icons/cross.png\" alt=\"{$lang->hooks_cross}\" />",
                                   array('class' => 'align_center',
                                         'style' => 'white-space: nowrap;'));
        }

        $table->construct_cell("<div style=\"padding-left: 40px;\"><a href=\"{$editurl}\">"
                               .htmlspecialchars($row['htitle'])
                               .'</a>'
                               .$delete
                               .'<br />'
                               .htmlspecialchars($row['hdescription'])
                               .'</div>');

        $table->construct_row();
    }

    $createurl = $PL->url_append(HOOKS_URL, array('mode' => 'edit'));
    $importurl = $PL->url_append(HOOKS_URL, array('mode' => 'import'));
    $exporturl = $PL->url_append(HOOKS_URL, array('mode' => 'export',
                                                  'hook' => implode(",", $exportids)));

    $table->construct_cell("<img src=\"styles/{$page->style}/images/icons/increase.png\" /> <a href=\"{$importurl}\">{$lang->hooks_import}</a>",
                           array('class' => 'align_center', 'style' => 'white-space: nowrap;'));
    $table->construct_cell("<img src=\"styles/{$page->style}/images/icons/decrease.png\" /> <a href=\"{$exporturl}\">{$lang->hooks_export}</a>",
                           array('class' => 'align_center', 'style' => 'white-space: nowrap;'));
    $table->construct_cell("<img src=\"styles/{$page->style}/images/icons/custom.png\" /> <a href=\"{$createurl}\">{$lang->hooks_new}</a> ",
                           array('class' => 'align_center'));

    $table->construct_row();

    $table->output($lang->hooks);

    // legend
    echo "
<ul class=\"smalltext\">
    <li style=\"list-style-image: url(styles/{$page->style}/images/icons/tick.png)\" />
        {$lang->hooks_legend_tick}
    </li>
    <li style=\"list-style-image: url(styles/{$page->style}/images/icons/cross.png)\" />
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

        $argument = $mybb->input['hargument'];

        hooks_validate($hook, $argument, $code, $errors);

        if(!$errors && !$mybb->input['preview'])
        {
            $data = array(
                'htitle' => $db->escape_string($title),
                'hdescription' => $db->escape_string($description),
                'hhook' => $db->escape_string($hook),
                'hpriority' => $priority,
                'hcode' => $db->escape_string($code),
                'hargument' => $db->escape_string($argument),
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

            // Update in case an active hook was edited.
            hooks_update_data();

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
                                    'htitle,hdescription,hhook,hpriority,hargument,hcode',
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

    if($preview)
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
        $lang->hooks_argument,
        $lang->hooks_argument_desc,
        $form->generate_text_box('hargument',
                                 $mybb->input['hargument'],
                                 array('id' => 'hargument')),
        'hargument'
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
                           || !strlen($hook['hcode'])
                           || !is_string($hook['hargument']))
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
                            'hargument' => $db->escape_string($hook['hargument']),
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
        $errors  = array();

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
            $mybb->input['hook'] = implode(',', $mybb->input['hooks']);

            $where = array();

            foreach((array)$mybb->input['hooks'] as $hid)
            {
                $where[] = $db->escape_string(strval($hid));
            }

            $where = implode("','", $where);
            $where = "hid IN ('{$where}')";

            $query = $db->simple_select("hooks",
                                        "hhook,htitle,hdescription,hpriority,hargument,hcode,hid",
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
                if($mybb->input['plugin'])
                {
                    hooks_export_plugin($hooks, $errors);
                }

                else
                {
                    $PL->xml_export($hooks, $filename, 'MyBB Hooks exported {time}');
                }

                // Exit on success.
            }

            else
            {
                $errors[] = $lang->hooks_export_error;
            }
        }

        else
        {
            $errors[] = $lang->hooks_export_error;
        }
    }

    if($mybb->input['hook'])
    {
        $hooks = array();

        foreach(explode(",", $mybb->input['hook']) as $hid)
        {
            $hooks[] = htmlspecialchars($hid);
        }
    }

    // Input field defaults
    $mybb->input = array_replace(array('compatibility' => '18*',
                                       'version' => '1.0'),
                                 $mybb->input);

    hooks_output_header();
    hooks_output_tabs();

    if($errors)
    {
        $page->output_inline_error($errors);
    }

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

    $form = new Form($exporturl, "post");

    $table = new Table;

//    $table->construct_header($lang->hooks);

    $table->construct_cell($lang->hooks_export_select
                           .'<br /><br />'
                           .$form->generate_select_box("hooks[]", $hooks_selects, $hooks, array('multiple' => true, 'id' => 'hooks_select')));
    $table->construct_row();

    $table->construct_cell($lang->hooks_export_filename
                           .'<br /><br />'
                           .$form->generate_text_box('filename', $mybb->input['filename']));
    $table->construct_row();

    $buttons = array($form->generate_submit_button($lang->hooks_export_button),
                     $form->generate_submit_button($lang->hooks_export_plugin_button,
                                                   array('name' => 'plugin')),
                     $form->generate_submit_button($lang->hooks_cancel,
                                                   array('name' => 'cancel')));
    $table->output($lang->hooks_export_caption);
    $form->output_submit_wrapper($buttons);

    echo "<br />";

    // --- plugin export fields: ---

    $table = new Table;

    $table->construct_cell($lang->hooks_export_plugin_name
                           .'<br /><br />'
                           .$form->generate_text_box('name', $mybb->input['name']));
    $table->construct_row();

    $table->construct_cell($lang->hooks_export_plugin_description
                           .'<br /><br />'
                           .$form->generate_text_box('description', $mybb->input['description']));
    $table->construct_row();

    $table->construct_cell($lang->hooks_export_plugin_website
                           .'<br /><br />'
                           .$form->generate_text_box('website', $mybb->input['website']));
    $table->construct_row();

    $table->construct_cell($lang->hooks_export_plugin_author
                           .'<br /><br />'
                           .$form->generate_text_box('author', $mybb->input['author']));
    $table->construct_row();

    $table->construct_cell($lang->hooks_export_plugin_authorsite
                           .'<br /><br />'
                           .$form->generate_text_box('authorsite', $mybb->input['authorsite']));
    $table->construct_row();

    $table->construct_cell($lang->hooks_export_plugin_version
                           .'<br /><br />'
                           .$form->generate_text_box('version', $mybb->input['version']));
    $table->construct_row();

    $table->construct_cell($lang->hooks_export_plugin_guid
                           .'<br /><br />'
                           .$form->generate_text_box('guid', $mybb->input['guid']));
    $table->construct_row();

    $table->construct_cell($lang->hooks_export_plugin_compatibility
                           .'<br /><br />'
                           .$form->generate_text_box('compatibility', $mybb->input['compatibility']));
    $table->construct_row();

    $table->output($lang->hooks_export_plugin_caption);

    $form->output_submit_wrapper($buttons);
    $form->end();

    $page->output_footer();
}

function hooks_export_plugin($hooks, &$errors)
{
    global $mybb, $lang;

    $validate = array();
    $data = "";

    // Validate input.

    if(!strlen($mybb->input['filename']))
    {
        $errors[] = $lang->hooks_export_plugin_error_filename;
    }

    else
    {
        $filename = "{$mybb->input['filename']}.php";
        $prefix = $mybb->input['filename'];

        hooks_validate("{$prefix}_suffix", '', '', $validate);

        if(count($validate))
        {
            $errors[] = $lang->hooks_export_plugin_error_prefix;
        }
    }

    if(!strlen($mybb->input['name']))
    {
        $errors[] = $lang->hooks_export_plugin_error_name;
    }

    if(!strlen($mybb->input['author']))
    {
        $errors[] = $lang->hooks_export_plugin_error_author;
    }

    if(!strlen($mybb->input['version']))
    {
        $errors[] = $lang->hooks_export_plugin_error_version;
    }

    if(!strlen($mybb->input['compatibility']))
    {
        $errors[] = $lang->hooks_export_plugin_error_compatibility;
    }

    if(count($errors))
    {
        return;
    }

    // Generate Plugin Code
    $output = hooks_generate_code($hooks, $prefix);

    // Output PHP.
    $date = gmdate('D, d M Y H:i:s T');

    @header('Content-Type: application/text; charset=UTF-8');
    @header('Expires: Sun, 20 Feb 2011 13:47:47 GMT'); // past
    @header("Last-Modified: {$date}");
    @header('Pragma: no-cache');
    @header('Content-Disposition: attachment; filename="'.$filename.'"');

    echo "<?php\n/* Exported by Hooks plugin {$date} */\n\n";
    echo "if(!defined('IN_MYBB'))\n{\n    die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');\n}\n\n";
    echo "/* --- Plugin API: --- */\n\n";

    $info = array(
        "name" => strval($mybb->input['name']),
        "description" => strval($mybb->input['description']),
        "website" => strval($mybb->input['website']),
        "author" => strval($mybb->input['author']),
        "authorsite" => strval($mybb->input['authorsite']),
        "version" => strval($mybb->input['version']),
        "guid" => strval($mybb->input['guid']),
        "compatibility" => strval($mybb->input['compatibility']),
    );

    echo "function {$prefix}_info()\n{\n    return ";

    var_export($info);

    echo ";\n}\n\n/**\n * function {$prefix}_activate()\n * function {$prefix}_deactivate()\n * function {$prefix}_is_installed()\n * function {$prefix}_install()\n * function {$prefix}_uninstall()\n */\n\n\n/* --- Hooks: --- */\n";

    foreach($output as $code)
    {
        echo $code;
    }

    echo "\n/* Exported by Hooks plugin {$date} */\n?>\n";

    exit;
}

/* --- End of file. --- */
?>
