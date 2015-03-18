<?php

// This is a PLUGIN TEMPLATE for Textpattern CMS.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'spf_codemirror';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '1.0';
$plugin['author'] = 'Simon Finch';
$plugin['author_uri'] = 'https://github.com/spiffin/spf_codemirror';
$plugin['description'] = 'CodeMirror syntax-highlighting + Emmet code-completion (HTML & CSS)';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '9';

// Plugin 'type' defines where the plugin is loaded
// 0 = public              : only on the public side of the website (default)
// 1 = public+admin        : on both the public and admin side
// 2 = library             : only when include_plugin() or require_plugin() is called
// 3 = admin               : only on the admin side (no AJAX)
// 4 = admin+ajax          : only on the admin side (AJAX supported)
// 5 = public+admin+ajax   : on both the public and admin side (AJAX supported)
$plugin['type'] = '3';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '2';

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().
// Syntax:
// ## arbitrary comment
// #@event
// #@language ISO-LANGUAGE-CODE
// abc_string_name => Localized String

$plugin['textpack'] = <<< EOT
#@admin
#@spf_codemirror
#@language en-gb
spf_codemirror_emmet => CodeMirror enable Emmet
spf_codemirror_enter_fs => CodeMirror enter full-screen hot-key
spf_codemirror_exit_fs => CodeMirror exit full-screen hot-key
spf_codemirror_font_size => CodeMirror font size
spf_codemirror_theme => CodeMirror theme
spf_codemirror_url => CodeMirror URL
EOT;
// End of textpack

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
/** <?php
 *
 * spf_codemirror - CodeMirror syntax-highlighting for Textpattern
 *
 * © 2015 Simon Finch - https://github.com/spiffin/spf_codemirror
 *
 * Licensed under GNU General Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Thanks to Marijn (CodeMirror), Sergey (Emmet), Dale (mrd_codeMirror)
 *
 * Version 1.0 -- March 2015
 */

if (@txpinterface == 'admin') {
    add_privs('spf_codemirror','1,2');
    register_callback('spf_codemirror_installprefs', 'plugin_lifecycle.spf_codemirror', 'installed');
    register_callback('spf_codemirror_removeprefs', 'plugin_lifecycle.spf_codemirror', 'deleted');
    register_callback('spf_cm_prefs', 'plugin_lifecycle.spf_codemirror', 'enabled');
    register_callback('spf_textarea_html', 'page');
    register_callback('spf_textarea_php', 'form');
    register_callback('spf_textarea_css', 'css');
    register_callback('spf_textarea_js', 'spf_js');
    register_callback('spf_textarea_php', 'spf_ext');
    register_callback('spf_textarea_php', 'ied_plugin_composer');

}
/* Prefs array */
global $prefs, $spf_cm_prefs;
if(isset($prefs['spf_codemirror_url'])) {
	if (strpos($prefs['spf_codemirror_theme'],'solarized-') !== false) {
		$spf_cm_prefs = array(
			'cm_url' => $prefs['spf_codemirror_url'],
			'cm_theme_class' => str_replace("-", " ", $prefs['spf_codemirror_theme']),
			'cm_theme_css' => substr($prefs['spf_codemirror_theme'], 0, 9),
			'cm_fsize' => $prefs['spf_codemirror_font_size'],
			'cm_enterfs' => $prefs['spf_codemirror_enter_fs'],
			'cm_exitfs' => $prefs['spf_codemirror_exit_fs'],
			'cm_emmet' => $prefs['spf_codemirror_emmet'],
		);
	} else {
		$spf_cm_prefs = array(
			'cm_url' => $prefs['spf_codemirror_url'],
			'cm_theme_class' => $prefs['spf_codemirror_theme'],
			'cm_theme_css' => $prefs['spf_codemirror_theme'],
			'cm_fsize' => $prefs['spf_codemirror_font_size'],
			'cm_enterfs' => $prefs['spf_codemirror_enter_fs'],
			'cm_exitfs' => $prefs['spf_codemirror_exit_fs'],
			'cm_emmet' => $prefs['spf_codemirror_emmet'],
		);
	}
}
/* Set default prefs on install */
// -------------------------------------------------------------
function spf_codemirror_installprefs($event, $step) {
global $prefs, $step;
$default_url = ihu . "codemirror";

    if(!isset($prefs['spf_codemirror_url'])) {
        safe_insert(
            'txp_prefs',
            "prefs_id=1,
            name='spf_codemirror_url',
            val='$default_url',
            type=1,
            event='admin',
            html='text_input',
            position=22"
        );
    }
    if(!isset($prefs['spf_codemirror_theme'])) {
        safe_insert(
            'txp_prefs',
            "prefs_id=1,
            name='spf_codemirror_theme',
            val='solarized-light',
            type=1,
            event='admin',
            html='text_input',
            position=23"
        );
    }
    if(!isset($prefs['spf_codemirror_font_size'])) {
        safe_insert(
            'txp_prefs',
            "prefs_id=1,
            name='spf_codemirror_font_size',
            val='1.1em',
            type=1,
            event='admin',
            html='text_input',
            position=24"
        );
    }
    if(!isset($prefs['spf_codemirror_enter_fs'])) {
        safe_insert(
            'txp_prefs',
            "prefs_id=1,
            name='spf_codemirror_enter_fs',
            val='F5',
            type=1,
            event='admin',
            html='text_input',
            position=25"
        );
    }
    if(!isset($prefs['spf_codemirror_exit_fs'])) {
        safe_insert(
            'txp_prefs',
            "prefs_id=1,
            name='spf_codemirror_exit_fs',
            val='Esc',
            type=1,
            event='admin',
            html='text_input',
            position=26"
        );
    }
    if(!isset($prefs['spf_codemirror_emmet'])) {
        safe_insert(
            'txp_prefs',
            "prefs_id=1,
            name='spf_codemirror_emmet',
            val='0',
            type=1,
            event='admin',
            html='yesnoradio',
            position=27"
        );
    }

}

/* Remove prefs and textpack on delete */
// -------------------------------------------------------------
function spf_codemirror_removeprefs($event, $step) {
global $prefs, $step;

    if(isset($prefs['spf_codemirror_url'])) {
        safe_delete(
            'txp_prefs',
            "name = 'spf_codemirror_url'"
        );
    }
    if(isset($prefs['spf_codemirror_theme'])) {
        safe_delete(
            'txp_prefs',
            "name = 'spf_codemirror_theme'"
        );
    }
    if(isset($prefs['spf_codemirror_font_size'])) {
        safe_delete(
            'txp_prefs',
            "name = 'spf_codemirror_font_size'"
        );
    }
    if(isset($prefs['spf_codemirror_enter_fs'])) {
        safe_delete(
            'txp_prefs',
            "name = 'spf_codemirror_enter_fs'"
        );
    }
    if(isset($prefs['spf_codemirror_exit_fs'])) {
        safe_delete(
            'txp_prefs',
            "name = 'spf_codemirror_exit_fs'"
        );
    }
    if(isset($prefs['spf_codemirror_emmet'])) {
        safe_delete(
            'txp_prefs',
            "name = 'spf_codemirror_emmet'"
        );
    }

        // delete the Textpack

        safe_delete(
            'txp_lang',
            "event = 'spf_codemirror'"
        );

}

/* HTML settings (Presentation > Pages and Forms) */
// -------------------------------------------------------------
function spf_textarea_html($event) {
global $prefs, $spf_cm_prefs;
extract($spf_cm_prefs);
spf_textarea_common();
spf_emmetjs(); spf_editor();
$emmetjs = spf_emmetjs();
$editor = spf_editor();

$id = '';
switch ($event) {
	case 'page':
		$id = 'html';
		break;
	case 'form':
		$id = 'form';
		break;
}

$cmfiles = <<<EOF
\n<script type="text/javascript" src="$cm_url/mode/xml/xml.js"></script>
<script type="text/javascript" src="$cm_url/mode/javascript/javascript.js"></script>
<script type="text/javascript" src="$cm_url/mode/css/css.js"></script>
<script type="text/javascript" src="$cm_url/mode/htmlmixed/htmlmixed.js"></script>
<script type="text/javascript" src="$cm_url/addon/search/searchcursor.js"></script>
<script type="text/javascript" src="$cm_url/addon/search/match-highlighter.js"></script>
$emmetjs
EOF;

$cm_html = <<<EOF
\n<script type="text/javascript">
var editor = CodeMirror.fromTextArea(document.getElementById("$id"), {
    mode: 'text/html',
    tabMode: 'indent',
    lineWrapping: true,
    lineNumbers: true,
    viewportMargin: Infinity,
    theme: '$cm_theme_class',
    extraKeys: {
      '$cm_enterfs': function(cm) {
        cm.setOption("fullScreen", !cm.getOption("fullScreen"));
      },
      '$cm_exitfs': function(cm) {
        if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
      }
    }
});
$editor
</script>
<!-- spf_codemirror END -->\n
EOF;

echo $cmfiles;
echo $cm_html;
}


/* CSS settings (Presentation > Styles) */
// -------------------------------------------------------------
function spf_textarea_css() {
global $prefs, $spf_cm_prefs;
extract($spf_cm_prefs);
spf_textarea_common();
spf_emmetjs(); spf_editor();
$emmetjs = spf_emmetjs();
$editor = spf_editor();

$cmfiles = <<<EOF
\n<script type="text/javascript" src="$cm_url/mode/css/css.js"></script>
<script type="text/javascript" src="$cm_url/addon/edit/matchbrackets.js"></script>
$emmetjs
EOF;

$cm_css = <<<EOF
\n<script type="text/javascript">
var editor = CodeMirror.fromTextArea(document.getElementById("css"), {
    mode: 'text/css',
    lineWrapping: true,
    lineNumbers: true,
    matchBrackets : true,
    theme: '$cm_theme_class',
    viewportMargin: Infinity,
    extraKeys: {
      '$cm_enterfs': function(cm) {
        cm.setOption("fullScreen", !cm.getOption("fullScreen"));
      },
      '$cm_exitfs': function(cm) {
        if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
      }
    },
    // define Emmet output profile
    profile: 'html',
  });
$editor
</script>
<!-- spf_codemirror END -->\n
EOF;

echo $cmfiles;
echo $cm_css;
}

/* JavaScript settings (Presentation > JavaScript -requires spf_js) */
// -------------------------------------------------------------
function spf_textarea_js() {
global $prefs, $spf_cm_prefs;
extract($spf_cm_prefs);
spf_textarea_common();

$cmfiles = <<<EOF
\n<script type="text/javascript" src="$cm_url/mode/javascript/javascript.js"></script>
<script type="text/javascript" src="$cm_url/addon/edit/matchbrackets.js"></script>
<script type="text/javascript" src="$cm_url/addon/search/searchcursor.js"></script>
<script type="text/javascript" src="$cm_url/addon/search/match-highlighter.js"></script>
EOF;

$cm_js = <<<EOF
\n<script type="text/javascript">
var editor = CodeMirror.fromTextArea(document.getElementById("spf_js"), {
    lineWrapping: true,
    lineNumbers: true,
    matchBrackets: true,
    theme: '$cm_theme_class',
    viewportMargin: Infinity,
    extraKeys: {
      '$cm_enterfs': function(cm) {
        cm.setOption("fullScreen", !cm.getOption("fullScreen"));
      },
      '$cm_exitfs': function(cm) {
        if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
      }
    }
});
</script>
<!-- spf_codemirror END -->\n
EOF;

echo $cmfiles;
echo $cm_js;
}

/* PHP settings (Extensions > External Files & Plugin Composer) */
// -------------------------------------------------------------
function spf_textarea_php($event) {
global $prefs, $spf_cm_prefs;
extract($spf_cm_prefs);
spf_textarea_common();
spf_emmetjs(); spf_editor();
$emmetjs = spf_emmetjs();
$editor = spf_editor();

$id = '';
switch ($event) {
	case 'form':
		$id = 'form';
		break;
	case 'spf_ext':
		$id = 'spf_ext';
		break;
	case 'ied_plugin_composer':
		$id = 'plugin_editor';
		break;
}

$cmfiles = <<<EOF
\n<script type="text/javascript" src="$cm_url/mode/xml/xml.js"></script>
<script type="text/javascript" src="$cm_url/mode/javascript/javascript.js"></script>
<script type="text/javascript" src="$cm_url/mode/css/css.js"></script>
<script type="text/javascript" src="$cm_url/mode/clike/clike.js"></script>
<script type="text/javascript" src="$cm_url/mode/php/php.js"></script>
<script type="text/javascript" src="$cm_url/addon/edit/matchbrackets.js"></script>
$emmetjs
EOF;

$cm_php = <<<EOF
\n<script type="text/javascript">
var editor = CodeMirror.fromTextArea(document.getElementById("$id"), {
    lineWrapping: true,
    lineNumbers: true,
    matchBrackets: true,
    mode: 'application/x-httpd-php',
    indentUnit: 4,
    indentWithTabs: true,
    enterMode: 'keep',
    tabMode: 'shift',
    theme: '$cm_theme_class',
    extraKeys: {
      '$cm_enterfs': function(cm) {
        cm.setOption("fullScreen", !cm.getOption("fullScreen"));
      },
      '$cm_exitfs': function(cm) {
        if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
      }
    },
    // define Emmet output profile
    profile: 'html',
  });
$editor
</script>
<!-- spf_codemirror END -->\n
EOF;

echo $cmfiles;
echo $cm_php;
}

/* Common settings */
// -------------------------------------------------------------
function spf_textarea_common() {
global $prefs, $spf_cm_prefs;
extract($spf_cm_prefs);

$cmfiles = <<<EOF
\n<!-- spf_codemirror START -->
<link href="$cm_url/lib/codemirror.css" rel="stylesheet" type="text/css" />
<link href="$cm_url/theme/$cm_theme_css.css" rel="stylesheet" type="text/css" />
<link href="$cm_url/addon/display/fullscreen.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="$cm_url/lib/codemirror.js"></script>
<script type="text/javascript" src="$cm_url/addon/display/fullscreen.js"></script>
EOF;

// Textpattern-specific css and js
$cm_txp = <<<EOF
\n<style>
/* Additional styles for Textpattern */
.CodeMirror { font-family: Menlo, Consolas, "Liberation Mono", monospace; font-size: $cm_fsize; height: 39.256em; min-width: 54.876em; max-width: 78.264em; border: 1px solid; border-color: #bbb #ddd #ddd #bbb; }
.CodeMirror-fullscreen { z-index: 12; }
</style>
EOF;

echo $cmfiles;
echo $cm_txp;
}

function spf_emmetjs() {
global $prefs, $spf_cm_prefs;
extract($spf_cm_prefs);

  if(isset($prefs['spf_codemirror_emmet'])) {
    if ($cm_emmet == "0") {
	return;
    } else {
        return "<script type=\"text/javascript\" src=\"$cm_url/emmet.min.js\"></script>";
    }
  }
}

function spf_editor() {
global $prefs, $spf_cm_prefs;
extract($spf_cm_prefs);

  if(isset($prefs['spf_codemirror_emmet'])) {
    if ($cm_emmet == "0") {
	return;
    } else {
	return "emmetCodeMirror(editor);";
    }
  }
}
# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---
h1. spf_codemirror

p. "CodeMirror":http://codemirror.net syntax-highlighting and "Emmet":http://docs.emmet.io code-completion plugin for Textpattern admin.

p. Edit pages, forms, css, javascript (and more) full-screen, in your browser, with a choice of syntax-highlighting and code-completion.

p. Customisable via preferences: choose a theme, font-size and full-screen hot-keys.

<hr >

h2. Features

# Adds "CodeMirror":http://codemirror.net syntax-highlighting to textareas in Textpattern's Forms, Pages and Style tabs;
# Also to JavaScript tab ("spf_js":http://forum.textpattern.com/viewtopic.php?id=37849 required), External Files tab ("spf_ext":http://forum.textpattern.com/viewtopic.php?id=38032 required), and Plugin composer ("ied_plugin_composer":http://forum.textpattern.com/viewtopic.php?id=14898 required);
# Configurable preferences via Admin > Preferences > Advanced;
# Code-completion added to HTML & CSS  courtesy of "Emmet":http://docs.emmet.io.

<hr />

h2. Upgrading from previous versions

# This version of the plugin (1.0) requires version 5.0 of CodeMirror. If you have an older installation, trash it.

<hr />

h2. CodeMirror installation

# "Download the latest release":http://codemirror.net/codemirror.zip of CodeMirror, unzip, and upload the entire directory (perhaps renaming it to 'codemirror') to your web server.
# The default location is your-web-root/codemirror - accessible via http://your.server/codemirror.
# If you chose an alternative location (e.g. a 'vendor' directory) you need to set it in the Prefs.
# Install the plugin: spf_codemirror.txt from the "spf_codemirror repo":https://github.com/spiffin/spf_codemirror.
# Check the Prefs (Admin > Preferences > Advanced), leave Emmet disabled (see next step), and activate.

<hr />

h2. Emmet installation

# Download emmet.min.js from the "spf_codemirror repo":https://github.com/spiffin/spf_codemirror - or "build your own with npm and gulp":https://github.com/emmetio/codemirror
# Put emmet.min.js inside your codemirror directory. That's it.
# Emmet is disabled by default (for speed).
# You can enable/disable it in the Prefs (Admin > Preferences > Advanced).


<hr />

h2. CodeMirror/Emmet upgrade

# You can now easily upgrade CodeMirror (5.0 as of March 2015).
# "Download the latest release":http://codemirror.net/codemirror.zip, unzip, and replace your old codemirror directory.
# To upgrade Emmet, go to the "CodeMirror plugin repo":https://github.com/emmetio/codemirror, and follow the build instructions. Make sure the js file in minified and named emmet.min.js.
# Upload emmet.min.js to the codemirror directory on your server - replacing your previous version.

<hr />

h2. Emmet notes

# Initiate completion by hitting TAB (or Cmd+E).
# Try typing this: @div#page>div.logo+ul#navigation>li*5>a@ and then TAB.
# Works with opening and closing Textpattern tags (try typing @txp:if_section@ and then TAB).
# CSS shortcuts work even better with Emmet* - try typing m and then TAB in Styles.
# "Emmet documentation":http://docs.emmet.io.

<hr />

<div class="history">

h2. Version history

1.0 - March 2015
* Changes for CodeMirror 5.0
* Added support for Plugin Composer (add a commented PHP tag: @//<?php@ at start to enable)
* Removed Minify support
* Enable or disable Emmet (for speed)

0.9 - October 2014
* Changes for CodeMirror 4.6

0.8 - January 2013
* Changes for CodeMirror 3.1 & Emmet 1.3.3
* Added match-highlighter feature for Pages, Forms & Javascript.
* Fixed search in full-screen mode.

0.7 - November 2012
* Code clean-up, CSS tweaks & "Emmet documentation":http://docs.emmet.io.

0.6 - November 2012
* Re-written for Textpattern 4.5.1.
* Upgraded to CodeMirror 2.35 and Emmet.
* Full-screen support - hit F5 (and Esc to exit).
* Basic preferences via Admin > Preferences > Advanced.
* CodeMirror folder structure now mirrors standard CodeMirror for easy upgrades.
* Automatic Minify support.

0.5 - May 2012
* CSS support for Zen Coding within style tags.

0.4 - May 2012
* Added code block indicators: @spf_codemirror START/END@.
* Code cleanup.

0.3 - May 2012
* Added Zen Coding code completion to Pages and Forms (HTML).
* Upgraded to CodeMirror 2.25.

0.2 - May 2012
* Changed load order (to allow interaction with other plugins).

0.1 - May 2012
* first release.
</div>
# --- END PLUGIN HELP ---
-->
<?php
}
?>