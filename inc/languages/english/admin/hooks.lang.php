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

$l = array(
    'hooks' => 'Hooks',
    'hooks_PL' => 'The Hooks plugin depends on <a href="http://mods.mybb.com/view/pluginlibrary">PluginLibrary</a>, which is missing. Please install it.',
    'hooks_PL_old' => 'The Hooks plugin depends on <a href="http://mods.mybb.com/view/pluginlibrary">PluginLibrary</a>, which is too old. Please update it.',
    'hooks_activate' => 'Activate',
    'hooks_activated' => 'The selected hook has been activated.',
    'hooks_argument' => 'Argument',
    'hooks_argument_desc' => "If this hook takes an argument / parameter, enter the desired variable name here. For example, if you set this to 'arg', you can use $arg in your code.",
    'hooks_cancel' => 'Cancel',
    'hooks_code' => 'Code',
    'hooks_code_desc' => 'Enter the PHP code that should be executed for this hook.',
    'hooks_controls' => 'Controls',
    'hooks_deactivate' => 'Deactivate',
    'hooks_deactivated' => 'The selected hook has been deactivated.',
    'hooks_delete' => 'Delete Hook',
    'hooks_deleted' => 'The selected hook has been deleted.',
    'hooks_desc' => 'Create and manage plugin hooks. Depends on PluginLibrary.',
    'hooks_description' => 'Description',
    'hooks_description_desc' => 'Optionally, you may enter a description for this hook.',
    'hooks_edit' => 'Edit Hook',
    'hooks_error_argument' => 'Invalid argument name.',
    'hooks_error_code' => 'No code specified.',
    'hooks_error_faulty' => 'Could not activate the selected hook because it contains errors.',
    'hooks_error_hook' => 'No hook specified.',
    'hooks_error_hook_invalid' => 'Invalid hook specified.',
    'hooks_error_key' => 'Invalid post key.',
    'hooks_error_missing' => 'The specified hook does not exist.',
    'hooks_error_syntax' => 'There is a syntax error in your code.',
    'hooks_error_title' => 'No title specified.',
    'hooks_error_write_permission' => "Write permission for '{1}' is missing.",
    'hooks_export' => 'Export',
    'hooks_export_button' => 'Export as XML',
    'hooks_export_caption' => 'Export Hooks',
    'hooks_export_error' => 'Failed to export hooks. None selected?',
    'hooks_export_error_unknown' => 'Failed to export XML - please report a bug.',
    'hooks_export_filename' => 'Enter a name component for the exported file. The file will be called <i>hooks-<b>name</b>.xml</i> or <b>name</b>.php.',
    'hooks_export_plugin_author' => 'The name of the author of the plugin.',
    'hooks_export_plugin_authorsite' => '(Optional) The URL to the website of the author.',
    'hooks_export_plugin_button' => 'Export as Plugin',
    'hooks_export_plugin_caption' => 'Export Hooks as Plugin Information',
    'hooks_export_plugin_compatibility' => 'A CSV list of MyBB versions supported. E.g. 16* or 1604,1605,1606. Wildcards supported.',
    'hooks_export_plugin_description' => '(Optional) The description of what the plugin does.',
    'hooks_export_plugin_error_author' => 'No plugin author specified.',
    'hooks_export_plugin_error_compatibility' => 'No plugin compatibility specified.',
    'hooks_export_plugin_error_filename' => 'No plugin filename specified.',
    'hooks_export_plugin_error_name' => 'No plugin name specified.',
    'hooks_export_plugin_error_prefix' => 'Invalid plugin filename. Use a-z_ only for plugin export.',
    'hooks_export_plugin_error_version' => 'No plugin version specified.',
    'hooks_export_plugin_guid' => '(Optional) Unique GUID issued by the MyBB Mods site for version checking.',
    'hooks_export_plugin_name' => 'The name of the plugin.',
    'hooks_export_plugin_version' => 'The version number of the plugin.',
    'hooks_export_plugin_website' => '(Optional) The website the plugin is maintained at.',
    'hooks_export_select' => 'Select the hooks you want to export here. By default, the currently active hooks are selected. Hold down CTRL to select multiple hooks. Hook names are shown for your convenience, selecting them does nothing.',
    'hooks_hook' => 'Hook',
    'hooks_hook_desc' => 'Enter the name of the hook.',
    'hooks_import' => 'Import',
    'hooks_import_badfile' => 'Failed to import hooks. The file contained errors.',
    'hooks_import_button' => 'Perform Import',
    'hooks_import_caption' => 'Import Hooks',
    'hooks_import_errors' => ' (some hooks may be missing due to {1} errors in the file)',
    'hooks_import_file' => 'Select a hooks XML file to import.',
    'hooks_import_nofile' => 'Failed to import hooks. Apparently no file was uploaded.',
    'hooks_import_success' => '{1} Hooks successfully imported.',
    'hooks_legend_cross' => 'Hook is disabled and inactive',
    'hooks_legend_tick' => 'Hook is enabled and active',
    'hooks_new' => 'Add a new Hook...',
    'hooks_preview' => 'Preview Hook',
    'hooks_preview_output' => 'Preview',
    'hooks_priority' => 'Priority',
    'hooks_priority_desc' => 'Specify the priority of this hook. Default value is 10. Lower value means higher priority.',
    'hooks_save' => 'Save Hook',
    'hooks_saved' => 'Hook saved.',
    'hooks_tab_desc' => 'This section allows you to create and manage plugin hooks.',
    'hooks_title' => 'Title',
    'hooks_title_desc' => 'Enter a title for this hook.',
    );

?>
