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

$plugin['version'] = '0.5';
$plugin['author'] = 'Simon Finch';
$plugin['author_uri'] = 'https://github.com/spiffin/spf_codemirror';
$plugin['description'] = 'CodeMirror syntax-highlighting in Pages, Forms, CSS, JavaScript & External Files + Zen Coding (Pages, Forms & limited CSS)';

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

$plugin['flags'] = '0';

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
 * Thanks to Marijn (CodeMirror), Sergey (Zen Coding), Dale (mrd_codeMirror)
 *
 * Version 0.5 -- 30 May 2012
 */

if (@txpinterface == 'admin') {
    register_callback('spf_textarea_page', 'page');
    register_callback('spf_textarea_form', 'form');
    register_callback('spf_textarea_css', 'css');
    register_callback('spf_textarea_js', 'spf_js');
    register_callback('spf_textarea_ext', 'spf_ext');
}

function spf_textarea_page($event, $step) {
spf_codemirror_theme_select();
spf_textarea_common();

$cm_page_js = <<<EOF
\n<script type="text/javascript" src="../codemirror/cm_htmlmixed_min.js"></script>
<script type="text/javascript" src="../codemirror/zen_codemirror.min.js"></script>
<script type="text/javascript">
var editor = CodeMirror.fromTextArea(document.getElementById("html"), {
    mode: "text/html",
    tabMode: "indent",
    lineWrapping: true,
    lineNumbers: true,
    theme: "ambiance",
    syntax: 'html',   /* define Zen Coding syntax */
    profile: 'xhtml', /* define Zen Coding output profile */
    // send all key events to Zen Coding
        onKeyEvent: function() {
            return zen_editor.handleKeyEvent.apply(zen_editor, arguments);
        }
});
</script>
<!-- spf_codemirror END -->\n
EOF;

echo $cm_page_js;
}

function spf_textarea_form($event, $step) {
spf_codemirror_theme_select();
spf_textarea_common();

$cm_form_js = <<<EOF
\n<script type="text/javascript" src="../codemirror/cm_htmlmixed_min.js"></script>
<script type="text/javascript" src="../codemirror/zen_codemirror.min.js"></script>
<script type="text/javascript">
var editor = CodeMirror.fromTextArea(document.getElementById("form"), {
    mode: "text/html",
    tabMode: "indent",
    lineWrapping: true,
    lineNumbers: true,
    theme: "ambiance",
    syntax: 'html',   /* define Zen Coding syntax */
    profile: 'xhtml', /* define Zen Coding output profile */
    // send all key events to Zen Coding
        onKeyEvent: function() {
            return zen_editor.handleKeyEvent.apply(zen_editor, arguments);
        }
});
</script>
<!-- spf_codemirror END -->\n
EOF;

echo $cm_form_js;
}

function spf_textarea_css($event, $step) {
spf_codemirror_theme_select();
spf_textarea_common();

$cm_css_js = <<<EOF
\n<script type="text/javascript" src="../codemirror/cm_css_min.js"></script>
<script type="text/javascript" src="../codemirror/zen_codemirror.min.js"></script>
<script type="text/javascript">
var editor = CodeMirror.fromTextArea(document.getElementById("css"), {
    mode: "text/css",
    lineWrapping: true,
    lineNumbers : true,
    matchBrackets : true,
    theme: "ambiance",
    syntax: 'css',   /* define Zen Coding syntax */
    // send all key events to Zen Coding
        onKeyEvent: function() {
            return zen_editor.handleKeyEvent.apply(zen_editor, arguments);
        }
});
</script>
<!-- spf_codemirror END -->\n
EOF;

echo $cm_css_js;
}

function spf_textarea_js($event, $step) {
spf_codemirror_theme_select();
spf_textarea_common();

$cm_js_js = <<<EOF
\n<script type="text/javascript" src="../codemirror/cm_javascript_min.js"></script>
<script type="text/javascript">
var editor = CodeMirror.fromTextArea(document.getElementById("spf_js"), {
    lineWrapping: true,
    lineNumbers: true,
    matchBrackets: true,
    theme: "ambiance"
});
</script>
<!-- spf_codemirror END -->\n
EOF;

echo $cm_js_js;
}

function spf_textarea_ext($event, $step) {
spf_codemirror_theme_select();
spf_textarea_common();

$cm_ext_js = <<<EOF
\n<script type="text/javascript" src="../codemirror/cm_php_min.js"></script>
<script type="text/javascript" src="../codemirror/zen_codemirror.min.js"></script>
<script type="text/javascript">
var editor = CodeMirror.fromTextArea(document.getElementById("spf_ext"), {
    lineWrapping: true,
    lineNumbers: true,
    matchBrackets: true,
    mode: "application/x-httpd-php",
    indentUnit: 4,
    indentWithTabs: true,
    enterMode: "keep",
    tabMode: "shift",
    theme: "ambiance",
    syntax: 'html',   /* define Zen Coding syntax */
    profile: 'xhtml', /* define Zen Coding output profile */
    // send all key events to Zen Coding
        onKeyEvent: function() {
            return zen_editor.handleKeyEvent.apply(zen_editor, arguments);
        }
});
</script>
<!-- spf_codemirror END -->\n
EOF;

echo $cm_ext_js;
}

function spf_textarea_common() {
$cm = <<<EOF
\n<link href="../codemirror/cm_combined_min.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="../codemirror/cm_theme_select_min.js"></script>
EOF;

echo $cm;
}

function spf_codemirror_theme_select() {
$select = <<<EOF
\n<!-- spf_codemirror START -->
<p style="position:fixed;right:20px;bottom:20px">CodeMirror theme: <select onchange="selectTheme()" id="select">
    <option>default</option>
    <option selected>ambiance</option>
    <option>blackboard</option>
    <option>cobalt</option>
    <option>eclipse</option>
    <option>elegant</option>
    <option>lesser-dark</option>
    <option>monokai</option>
    <option>neat</option>
    <option>night</option>
    <option>rubyblue</option>
    <option>xq-dark</option>
</select>
</p>
<p>return syntax</p>
EOF;

echo $select;
}
# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---
<h1>spf_codemirror</h1>

<p>A syntax-highlighting plugin for Textpattern admin - now with <a href="http://code.google.com/p/zen-coding/">Zen Coding</a> (Pages, Forms and limited CSS).</p>

<h2>Background:</h2>

<p>This was a quick update to Dale Chapman’s <a href="http://forum.textpattern.com/viewtopic.php?id=38015">mrd_codeMirror</a> which, in turn, was prompted by my <a href="http://forum.textpattern.com/viewtopic.php?id=37957">CodeMirror admin theme</a> and, of course, Marijn Haverbeke’s <a href="http://codemirror.net">CodeMirror</a>. Now with <a href="http://code.google.com/p/zen-coding/">Zen Coding</a> goodness thrown in.</p>
<p>Thanks to Marijn, Sergey and Dale.</p>


<h2>Features:</h2>
<ol>
<li>Adds <a href="http://codemirror.net">CodeMirror</a> syntax-highlighting to textareas in Textpattern’s Forms, Pages and Style tabs;</li>
<li>Also to JavaScript tab (<a href="http://forum.textpattern.com/viewtopic.php?id=37849">spf_js</a> required) and External Files tab (<a href="http://forum.textpattern.com/viewtopic.php?id=38032">spf_ext</a> required);</li>
<li>Theme selector.</li>
<li>HTML shorthand/code-completion now added to Pages, Forms & limited CSS courtesy of <a href="http://code.google.com/p/zen-coding/">Zen Coding</a>.
</ol>


<h2>Installation:</h2>
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
</ol>

<br /><hr /><br />

<h2>Zen Coding notes:</h2>
<ol>
<li>Initiate completion by hitting TAB (or Cmd+E).</li>
<li>Try typing this: <code>div#page>div.logo+ul#navigation>li*5>a</code> and then TAB.</li>
<li>Works with opening and closing txp tags (try typing <code>txp:if_section</code> and then TAB).</li>
<li>CSS shortcuts work within <code>style</code> tags.</li>
<li>Try typing 'style' +TAB and then, within the <code>style</code> tags, type '@f' +TAB.</li>
</ol>


<h2>Notes &amp; issues:</h2>
<ol>
<li>Included .js and .css files have been combined and minified (original filenames are referenced in header);</li>
<li>Remora drop-down menus are still hidden behind the textarea;</li>
<li>Textarea resizing is disabled (enabling gives erratic results);</li>
<li>Plugins editor not supported;</li>
<li>Code-folding requires input to the Javascript (which lines to fold) and is therefore disabled;</li>
<li>Theme selector is bottom-right: not the most elegant solution but works consistently across most admin themes (specifically Classic, Hive, Steel).</li>
<li>Zen Coding is only available for HTML (Pages & Forms) - with support for CSS within <code>style</code> tags.</li>
</ol>

<br /><hr /><br />


<h2>Version history:</h2>

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