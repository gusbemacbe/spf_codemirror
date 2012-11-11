<?php

// This is a PLUGIN TEMPLATE.

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

$plugin['version'] = '0.7';
$plugin['author'] = 'Simon Finch';
$plugin['author_uri'] = 'https://github.com/spiffin/spf_codemirror';
$plugin['description'] = 'CodeMirror syntax-highlighting in Pages, Forms, CSS, JavaScript & External Files + Zen Coding/Emmet code-completion (HTML & CSS)';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '9';

// Plugin 'type' defines where the plugin is loaded
// 0 = public       : only on the public side of the website (default)
// 1 = public+admin : on both the public and admin side
// 2 = library      : only when include_plugin() or require_plugin() is called
// 3 = admin        : only on the admin side
$plugin['type'] = '3';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '2';

// Plugin 'textpack' - provides i18n strings to be used in conjunction with gTxt().
$plugin['textpack'] = <<< EOT
#@spf_codemirror
spf_codemirror_theme => CodeMirror theme
spf_codemirror_font_size => CodeMirror font size
spf_codemirror_enter_fs => CodeMirror enter full-screen hot-key
spf_codemirror_exit_fs => CodeMirror exit full-screen hot-key
EOT;

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
/**
 * spf_codemirror - CodeMirror syntax-highlighting for Textpattern
 *
 * © 2012 Simon Finch - https://github.com/spiffin/spf_codemirror
 *
 * Licensed under GNU General Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Thanks to Marijn (CodeMirror), Sergey (Zen Coding/Emmet), Dale (mrd_codeMirror)
 *
 * Version 0.7 -- 12 November 2012
 */

if (@txpinterface == 'admin') {
    add_privs('spf_codemirror','1,2');
    register_callback('spf_codemirror_installprefs', 'plugin_lifecycle.spf_codemirror', 'installed');
    register_callback('spf_codemirror_removeprefs', 'plugin_lifecycle.spf_codemirror', 'deleted');
    register_callback('spf_textarea_html', 'page');
    register_callback('spf_textarea_html', 'form');
    register_callback('spf_textarea_css', 'css');
    register_callback('spf_textarea_js', 'spf_js');
    register_callback('spf_textarea_php', 'spf_ext');

}
/* Prefs array */
global $prefs, $spf_cm_prefs;
$spf_cm_prefs = array(
	'cm_theme' => $prefs['spf_codemirror_theme'],
	'cm_fsize' => $prefs['spf_codemirror_font_size'],
	'cm_enterfs' => $prefs['spf_codemirror_enter_fs'],
	'cm_exitfs' => $prefs['spf_codemirror_exit_fs'],
);

/* Set default prefs on install */
// -------------------------------------------------------------
function spf_codemirror_installprefs($event, $step) {
global $prefs, $step;

    if(!isset($prefs['spf_codemirror_theme'])) {
        safe_insert(
            'txp_prefs',
            "prefs_id=1,
            name='spf_codemirror_theme',
            val='ambiance',
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

}

/* Remove prefs and textpack on delete */
// -------------------------------------------------------------
function spf_codemirror_removeprefs($event, $step) {
global $prefs, $step;

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
$public_url = ihu;
spf_textarea_common();

$id = '';
switch ($event) {
	case 'page':
		$id = 'html';
		break;
	case 'form':
		$id = 'form';
		break;
}

$mini = <<<EOF
\n<script type="text/javascript" src="${public_url}min/b=codemirror/mode&amp;f=xml/xml.js,javascript/javascript.js,css/css.js,htmlmixed/htmlmixed.js"></script>
<script type="text/javascript" src="${public_url}codemirror/emmet.min.js"></script>
EOF;

$maxi = <<<EOF
\n<script type="text/javascript" src="${public_url}codemirror/mode/xml/xml.js"></script>
<script type="text/javascript" src="${public_url}codemirror/mode/javascript/javascript.js"></script>
<script type="text/javascript" src="${public_url}codemirror/mode/css/css.js"></script>
<script type="text/javascript" src="${public_url}codemirror/mode/htmlmixed/htmlmixed.js"></script>
<script type="text/javascript" src="${public_url}codemirror/emmet.min.js"></script>
EOF;

$cm_html = <<<EOF
\n<script type="text/javascript">
var editor = CodeMirror.fromTextArea(document.getElementById("$id"), {
    mode: "text/html",
    tabMode: "indent",
    lineWrapping: true,
    lineNumbers: true,
    theme: "$cm_theme",
    syntax: 'html',   /* define Emmet syntax */
    extraKeys: _.extend({
    "$cm_enterfs": function(cm) {
      setFullScreen(cm, !isFullScreen(cm));
    },
    "$cm_exitfs": function(cm) {
      if (isFullScreen(cm)) { setFullScreen(cm, false); }
    }
  }, CodeMirror.defaults.extraKeys || {})
});
</script>
<!-- spf_codemirror END -->\n
EOF;

    if(if_minify() === true) {
        echo $mini;
    } else {
        echo $maxi;
    }

echo $cm_html;
}


/* CSS settings (Presentation > Styles) */
// -------------------------------------------------------------
function spf_textarea_css() {
global $prefs, $spf_cm_prefs;
extract($spf_cm_prefs);
$public_url = ihu;
spf_textarea_common();

$mini = <<<EOF
\n<script type="text/javascript" src="${public_url}min/f=codemirror/mode/css/css.js"></script>
<script type="text/javascript" src="${public_url}codemirror/emmet.min.js"></script>
EOF;

$maxi = <<<EOF
\n<script type="text/javascript" src="${public_url}codemirror/mode/css/css.js"></script>
<script type="text/javascript" src="${public_url}codemirror/emmet.min.js"></script>
EOF;

$cm_css = <<<EOF
\n<script type="text/javascript">
var editor = CodeMirror.fromTextArea(document.getElementById("css"), {
    mode: "text/css",
    lineWrapping: true,
    lineNumbers : true,
    matchBrackets : true,
    theme: "$cm_theme",
    syntax: 'css',   /* define Emmet syntax */
    extraKeys: _.extend({
    "$cm_enterfs": function(cm) {
      setFullScreen(cm, !isFullScreen(cm));
    },
    "$cm_exitfs": function(cm) {
      if (isFullScreen(cm)) { setFullScreen(cm, false); }
    }
  }, CodeMirror.defaults.extraKeys || {})
});
</script>
<!-- spf_codemirror END -->\n
EOF;

    if(if_minify() === true) {
        echo $mini;
    } else {
        echo $maxi;
    }

echo $cm_css;
}

/* JavaScript settings (Presentation > JavaScript -requires spf_js) */
// -------------------------------------------------------------
function spf_textarea_js() {
global $prefs, $spf_cm_prefs, $spf_if_minify;
extract($spf_cm_prefs);
$public_url = ihu;
spf_textarea_common();

$mini = <<<EOF
\n<script type="text/javascript" src="${public_url}min/f=codemirror/mode/javascript/javascript.js"></script>
EOF;

$maxi = <<<EOF
\n<script type="text/javascript" src="${public_url}codemirror/mode/javascript/javascript.js"></script>
EOF;

$cm_js = <<<EOF
\n<script type="text/javascript">
var editor = CodeMirror.fromTextArea(document.getElementById("spf_js"), {
    lineWrapping: true,
    lineNumbers: true,
    matchBrackets: true,
    theme: "$cm_theme",
    extraKeys: {
	"$cm_enterfs": function(cm) {setFullScreen(cm, !isFullScreen(cm));},
	"$cm_exitfs": function(cm) {if (isFullScreen(cm)) {setFullScreen(cm, false);}}
	}
});
</script>
<!-- spf_codemirror END -->\n
EOF;

    if(if_minify() === true) {
        echo $mini;
    } else {
        echo $maxi;
    }

echo $cm_js;
}

/* PHP settings (Extensions > External Files -requires spf_ext) */
// -------------------------------------------------------------
function spf_textarea_php() {
global $prefs, $spf_cm_prefs;
extract($spf_cm_prefs);
$public_url = ihu;
spf_textarea_common();

$mini = <<<EOF
\n<script type="text/javascript" src="${public_url}min/b=codemirror/mode&amp;f=xml/xml.js,javascript/javascript.js,css/css.js,clike/clike.js,php/php.js"></script>
<script type="text/javascript" src="${public_url}codemirror/emmet.min.js"></script>
EOF;

$maxi = <<<EOF
\n<script type="text/javascript" src="${public_url}codemirror/mode/xml/xml.js"></script>
<script type="text/javascript" src="${public_url}codemirror/mode/javascript/javascript.js"></script>
<script type="text/javascript" src="${public_url}codemirror/mode/css/css.js"></script>
<script type="text/javascript" src="${public_url}codemirror/mode/clike/clike.js"></script>
<script type="text/javascript" src="${public_url}codemirror/mode/php/php.js"></script>
<script type="text/javascript" src="${public_url}codemirror/emmet.min.js"></script>
EOF;

$cm_php = <<<EOF
\n<script type="text/javascript">
var editor = CodeMirror.fromTextArea(document.getElementById("spf_ext"), {
    lineWrapping: true,
    lineNumbers: true,
    matchBrackets: true,
    mode: "application/x-httpd-php",
    indentUnit: 4,
    indentWithTabs: true,
    enterMode: "keep",
    tabMode: "shift",
    theme: "$cm_theme",
    syntax: 'html',   /* define Emmet syntax */
    extraKeys: _.extend({
    "$cm_enterfs": function(cm) {
      setFullScreen(cm, !isFullScreen(cm));
    },
    "$cm_exitfs": function(cm) {
      if (isFullScreen(cm)) { setFullScreen(cm, false); }
    }
  }, CodeMirror.defaults.extraKeys || {})
});
</script>
<!-- spf_codemirror END -->\n
EOF;

    if(if_minify() === true) {
        echo $mini;
    } else {
        echo $maxi;
    }

echo $cm_php;
}

/* Common settings */
// -------------------------------------------------------------
function spf_textarea_common() {
global $prefs, $spf_cm_prefs;
extract($spf_cm_prefs);
$public_url = ihu;

$mini = <<<EOF
\n<!-- spf_codemirror START -->
<link type="text/css" rel="stylesheet" href="${public_url}min/f=codemirror/lib/codemirror.css" />
<link href="${public_url}min/f=codemirror/theme/$cm_theme.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="${public_url}min/f=codemirror/lib/codemirror.js"></script>
EOF;

$maxi = <<<EOF
\n<!-- spf_codemirror START -->
<link href="${public_url}codemirror/lib/codemirror.css" rel="stylesheet" type="text/css" />
<link href="${public_url}codemirror/theme/$cm_theme.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="${public_url}codemirror/lib/codemirror.js"></script>
EOF;

// Textpattern-specific css & js
$cm_txp = <<<EOF
<script type="text/javascript">
function isFullScreen(cm) {
      return /\bCodeMirror-fullscreen\b/.test(cm.getWrapperElement().className);
    }
    function winHeight() {
      return window.innerHeight || (document.documentElement || document.body).clientHeight;
    }
    function setFullScreen(cm, full) {
      var wrap = cm.getWrapperElement(), scroll = cm.getScrollerElement();
      if (full) {
        wrap.className += " CodeMirror-fullscreen";
        scroll.style.height = winHeight() + "px";
        document.documentElement.style.overflow = "hidden";
      } else {
        wrap.className = wrap.className.replace(" CodeMirror-fullscreen", "");
        scroll.style.height = "";
        document.documentElement.style.overflow = "";
      }
      cm.refresh();
    }
</script>
<style>
/* Additional styles for Textpattern */
.CodeMirror {
  font-size: $cm_fsize;
}
.CodeMirror-scroll {
  height: 39.256em;
  min-width: 54.876em;
  max-width: 78.264em;
  border: 1px solid;
  border-color: #bbb #ddd #ddd #bbb;
}
.CodeMirror-fullscreen {
  display: block;
  position: absolute;
  top: 0px !important; left: 0;
  width: 100%;
  z-index: 99;
}
.CodeMirror-fullscreen .CodeMirror-scroll {
  max-width: 100%;
  border: none;
}
</style>
EOF;

    if(if_minify() === true) {
        echo $mini;
    } else {
        echo $maxi;
    }

echo $cm_txp;
}

/* Check for Minify */
// -------------------------------------------------------------
function spf_if_minify() {
    if (file_exists(@$_SERVER['DOCUMENT_ROOT'].'/min/config.php')) {
        return true;
    } else {
        return false;
    }
}
# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN CSS ---
<style type="text/css">
#plugin_container {
font-size: 1.1em;
}
#plugin_container .small {
font-size: 0.9em;
}
</style>
# --- END PLUGIN CSS ---
-->
<!--
# --- BEGIN PLUGIN HELP ---
<h1>spf_codemirror</h1>

<p><a href="http://codemirror.net">CodeMirror</a> syntax-highlighting and <a href="http://docs.emmet.io">Emmet</a> code-completion plugin for Textpattern admin.</p>

<p class="small">(Emmet is the new name for Zen Coding.)</p>

<p><a href="/textpattern/index.php?event=prefs&step=advanced_prefs">Preferences</a> can be set for theme, font-size and full-screen hot-keys.</p>

<p><a href="http://codemirror.net/demo/theme.html">View themes here</a> - to set a theme just type the name (e.g. lesser-dark) into the CodeMirror theme field and click Save.</p>

<br /><hr /><br />

<h2>Features</h2>
<ol>
<li>Adds <a href="http://codemirror.net">CodeMirror</a> syntax-highlighting to textareas in Textpattern’s Forms, Pages and Style tabs;</li>
<li>Also to JavaScript tab (<a href="http://forum.textpattern.com/viewtopic.php?id=37849">spf_js</a> required) and External Files tab (<a href="http://forum.textpattern.com/viewtopic.php?id=38032">spf_ext</a> required);</li>
<li>Basic <a href="/textpattern/index.php?event=prefs&step=advanced_prefs">preferences</a> via Admin > Preferences > Advanced;</li>
<li>HTML/CSS shorthand/code-completion added to Pages, Forms & CSS courtesy of <a href="http://code.google.com/p/zen-coding/">Zen Coding/Emmet</a>.
<li>Full-screen support - just hit F5 (and Esc to exit) - or set your own hot-keys via <a href="/textpattern/index.php?event=prefs&step=advanced_prefs">Preferences</a>.</li>
</ol>

<br /><hr /><br />

<h2>Installation</h2>
<ol>
<li><a href="https://github.com/spiffin/spf_codemirror/zipball/master">DOWNLOAD</a> and unzip;</li>
<li>Upload the containing ‘codemirror’ directory to your web root:</li>
<ul style="margin-left:50px;list-style-type:square">
<li><strong>codemirror</strong></li>
<li>css.php</li>
<li>files</li>
<li>images</li>
<li>index.php</li>
<li>rpc</li>
<li>sites</li>
<li>textpattern</li>
</ul>
<li>Install and activate the plugin (spf_codemirror.txt - inside the unzipped folder).</li>
<li>NOTE: the folder structure mirrors that of a standard CodeMirror download (just the 'lib', 'mode' and 'theme' directories) with the addition of the minified emmet.min.js file from <a href="https://github.com/emmetio/emmet/downloads">Emmet-CodeMirror2</a> - for easy upgrade.</li>
</ol>

<br /><hr /><br />

<h2>CodeMirror/Emmet upgrade</h2>
<ol>
<li>You can now easily upgrade CodeMirror (2.35 as of Nov 2012).</li>
<li><a href="http://codemirror.net/codemirror.zip">Download the latest release</a>.</li>
<li>Unzip the download and upload the containing 'lib', 'mode' and 'theme' directories to the codemirror directory on your web server.</li>
<li>To upgrade Emmet <a href="https://github.com/emmetio/emmet/downloads">download 'Emmet-CodeMirror2'</a>, unzip it and upload the containing 'emmet.min.js' file to your codemirror directory.</li>
</ol>

<br /><hr /><br />

<h2>Minify support</h2>
<ol>
<li>If you have <a href="http://code.google.com/p/minify/">Minify</a> on your web server in the standard DOCUMENT_ROOT/min location, spf_codemirror will use it to minify css & js - if you don't, it won't.</li>
</ol>

<br /><hr /><br />

<h2>Emmet* notes</h2>
<ol>
<li>Initiate completion by hitting TAB (or Cmd+E).</li>
<li>Try typing this: <code>div#page>div.logo+ul#navigation>li*5>a</code> and then TAB.</li>
<li>Works with opening and closing txp tags (try typing <code>txp:if_section</code> and then TAB).</li>
<li>CSS shortcuts work even better with Emmet - try typing <code>m</code> and then TAB in Styles.</li>
<li><a href="http://docs.emmet.io">Emmet documentation</a>.</li>
<p class="small">*Emmet is the new name for Zen Coding.</p>
</ol>

<br /><hr /><br />


<h2>Version history</h2>

<p>0.7 - November 2012</p>
<ul>
<li>Code clean-up, CSS tweaks & <a href="http://docs.emmet.io">Emmet documentation</a>.</li>
</ul>

<p>0.6 - November 2012</p>
<ul>
<li>Re-written for Textpattern 4.5.1 (still works on 4.4.x).</li>
<li>Upgraded to <a href="http://codemirror.net/">CodeMirror</a> 2.35 and <a href="https://github.com/sergeche/zen-coding">Emmet</a>.</li>
<li>Full-screen support - hit F5 (and Esc to exit).</li>
<li>Basic <a href="/textpattern/index.php?event=prefs&step=advanced_prefs">preferences</a> via Admin > Preferences > Advanced.</li>
<li>CodeMirror folder structure now mirrors standard CodeMirror for easy upgrades.</li>
<li>Automatic Minify support.</li>
</ul>

<p>0.5 - May 2012</p>
<ul>
<li>CSS support for Zen Coding within <code>style</code> tags.</li>
</ul>

<p>0.4 - May 2012</p>
<ul>
<li>Added code block indicators: <code>spf_codemirror START/END</code>.</li>
<li>Code cleanup.</li>
</ul>

<p>0.3 - May 2012</p>
<ul>
<li>Added <a href="http://code.google.com/p/zen-coding/">Zen Coding</a> code completion to Pages and Forms (HTML).</li>
<li>Upgraded to <a href="http://codemirror.net/">CodeMirror</a> 2.25.</li>
</ul>

<p>0.2 - May 2012</p>
<ul>
<li>Changed load order (to allow interaction with other plugins).</li>
</ul>

<p>0.1 - May 2012</p>
<ul>
<li>first release.</li>
</ul>
# --- END PLUGIN HELP ---
-->
<?php
}
?>