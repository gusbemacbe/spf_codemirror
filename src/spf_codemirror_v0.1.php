/**
 * spf_codemirror - CodeMirror syntax-highlighting for Textpattern
 *
 * © 2012 Simon Finch - https://github.com/spiffin/spf_codemirror
 *
 * Licensed under GNU General Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * a quick update to mrd_codeMirror - thanks Dale
 *
 * Version 0.1 -- 09 May 2012
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
$js = '<script type="text/javascript" src="/codemirror/cm_htmlmixed_min.js"></script>';
$js .= '<script type="text/javascript">';

$js .= <<<EOF
var editor = CodeMirror.fromTextArea(document.getElementById("html"), {
    mode: "text/html",
    tabMode: "indent",
    lineWrapping: true,
    lineNumbers: true,
    extraKeys: {
    "'>'": function(cm) { cm.closeTag(cm, '>'); },
    "'/'": function(cm) { cm.closeTag(cm, '/'); }
    },
    theme: "ambiance"
});
EOF;

$js .= '</script>';

echo $js;
}

function spf_textarea_form($event, $step) {
spf_codemirror_theme_select();
spf_textarea_common();
$js = '<script type="text/javascript" src="/codemirror/cm_htmlmixed_min.js"></script>';
$js .= '<script type="text/javascript">';

$js .= <<<EOF
var editor = CodeMirror.fromTextArea(document.getElementById("form"), {
    mode: "text/html",
    tabMode: "indent",
    lineWrapping: true,
    lineNumbers: true,
    extraKeys: {
    "'>'": function(cm) { cm.closeTag(cm, '>'); },
    "'/'": function(cm) { cm.closeTag(cm, '/'); }
    },
    theme: "ambiance"
});
EOF;

$js .= '</script>';

echo $js;
}

function spf_textarea_css($event, $step) {
spf_codemirror_theme_select();
spf_textarea_common();
$js = '<script type="text/javascript" src="/codemirror/cm_css_min.js"></script>';
$js .= '<script type="text/javascript">';

$js .= <<<EOF
var editor = CodeMirror.fromTextArea(document.getElementById("css"), {
    lineWrapping: true,
    lineNumbers : true,
    matchBrackets : true,
    theme: "ambiance"
});  
EOF;

$js .= '</script>';

echo $js;
}

function spf_textarea_js($event, $step) {
spf_codemirror_theme_select();
spf_textarea_common();
$js = '<script type="text/javascript" src="/codemirror/cm_javascript_min.js"></script>';
$js .= '<script type="text/javascript">';

$js .= <<<EOF
var editor = CodeMirror.fromTextArea(document.getElementById("spf_js"), {
    lineWrapping: true,
    lineNumbers: true,
    matchBrackets: true,
    theme: "ambiance"
});   
EOF;

$js .= '</script>';

echo $js;
}

function spf_textarea_ext($event, $step) {
spf_codemirror_theme_select();
spf_textarea_common();
$js = '<script type="text/javascript" src="/codemirror/cm_php_min.js"></script>';
$js .= '<script type="text/javascript">';

$js .= <<<EOF
var editor = CodeMirror.fromTextArea(document.getElementById("spf_ext"), {
    lineWrapping: true,
    lineNumbers: true,
    matchBrackets: true,
    mode: "application/x-httpd-php",
    indentUnit: 4,
    indentWithTabs: true,
    enterMode: "keep",
    tabMode: "shift",
    theme: "ambiance"
});
EOF;

$js .= '</script>';

echo $js;
}

function spf_textarea_common() {
$cm = '<link href="/codemirror/cm_combined_min.css" rel="stylesheet" type="text/css" />
    <script type="text/javascript" src="/codemirror/cm_theme_select_min.js"></script>';

echo $cm;
}

function spf_codemirror_theme_select() {
$select = '<p style="position:fixed;right:20px;bottom:20px">CodeMirror theme: <select onchange="selectTheme()" id="select">
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
</p>';

echo $select;
}