Hooks plugin for MyBB 1.6
-------------------------

Manage MyBB plugin hooks.

This plugin adds a Hooks tab to the plugins page where you can 
create/delete, activate/deactivate, import/export custom hooks. 
This is useful in particular for small changes you want to make 
without creating a whole plugin file for it.

This is the counterpart for the Patches plugin. Where Patches 
lets you modify existing code, Hooks lets you add new code 
which will be executed through MyBB's hook system.

Installation instructions
-------------------------

1) This plugin depends on PluginLibrary. Please download it first.

   http://mods.mybb.com/view/pluginlibrary
   https://github.com/frostschutz/PluginLibrary

2) Upload inc/plugins/hooks.php and inc/plugins/hooks/plugin.php
   and inc/languages/english/admin/hooks.lang.php

   If you are using a language other than English, you will also
   have to place a copy of hooks.lang.php in the folders of the
   other languages. Language packs may be available on the mods
   site.

3) Make sure your cache/ directory is writable. This plugin 
   creates a file called cache/hooks-plugin-data.php which 
   contains the created / active plugin hooks.

4) Activate the plugin

Usage
-----

On the plugins page, there will be a new tab called 'Hooks',
which will let you create and manage hooks.

Uninstallation instructions
---------------------------

You can uninstall the plugin any time, however when you do so,
you will lose all information about your hooks.
