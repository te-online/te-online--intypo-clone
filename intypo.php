<?php
/*
Plugin Name: Intypo
Plugin URI: http://dossier.dunker.de/intypo/
Description: Intypo (International Typography) changes quotation marks, e.g. &#8222;...&#8220; instead of &#8220;...&#8221; for Germany etc. See <a href="options-general.php?page=intypo/intypo.php">Options/Intypo</a> for configuration.
Author: Darius Dunker
Version: 0.9.2a
Author URI: http://dossier.dunker.de/

Version 0.9.2a has been bugfixed by Johannes Freudendahl. Details here:
http://code.freudendahl.net/2012/04/wordpress-plugin-intypo-bugfix/
*/

# [En] No editing of this file required.
# [De] Keine Anpassung dieser Datei erforderlich.

/* hooks from WP 2.1 default-filters.php */
$ity_hooks = array('category_description', 'list_cats', 'comment_author', 'comment_text', 'single_post_title', 'the_title', 'the_content', 'the_excerpt', 'bloginfo');

/* replace default filters by modified ones */
foreach ($ity_hooks as $hook) {
  remove_filter($hook,'wptexturize');
  add_filter($hook,'ity_texturize');
  }

/*  add admin-page   */
add_action('admin_menu', 'ity_adminpage');

function ity_adminpage() {
    // Add a new menu under Options:
    if (function_exists('add_options_page')) add_options_page('Intypo - International Typography', 'Intypo', 'manage_options', __FILE__, 'ity_qmarkoptions');
}

/* Main filter function, derrived from original texturize function */
function ity_texturize($text) {
        global $wp_cockneyreplace;
        $ity_option = explode(":",get_option("intypo_mark"));
        if (!($ity_option[0])) $ity_option[0] = 0;     # if options page never visited
        if (!isset($ity_option[1]) || !($ity_option[1])) {
	  $ity_quot = ity_allquot();                   # old method: get marks from all options
          $quot = $ity_quot[$ity_option[0]];
        }
        else $quot = explode(",",$ity_option[1]);      # new method: current marks in database
        if (isset($ity_option[2])) {
          $ity_cockney = explode(",",stripslashes($ity_option[2]));
        }
        else {
          $ity_cockney = array("'tain't","'twere","'twas","'tis","'twill","'til","'bout","'nuff","'round","'cause");
          }
        foreach ($ity_cockney as $k => $cock) {
          $cockney[$k] = trim($cock).' ';
          $cockneyreplace[$k] = str_replace("'","&#8217;",trim($cock)).' ';
          }
        $output = '';
        // Capture tags and everything inside them
        $textarr = preg_split('/(<[^>]*>|(?<!\[)\[\b[^\/\]]*\/?\](?:.+?\[])?)/Us', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $stop = count($textarr); $in_ignore = false; // loop stuff
        for ($i = 0; $i < $stop; $i++) {
                $curl = $textarr[$i];
                if (isset($curl{0}) && '<' != $curl{0} && '[' != $curl{0} && !$in_ignore) { // If it's not a tag
                        $curl = str_replace('---', '&#8212;', $curl);
                        if ($ity_option[0] == 0) $curl = str_replace(' -- ', ' &#8212; ', $curl); # dirty hack to apply m-dash only if english style selected
                        $curl = str_replace(' - ', ' &#8211; ', $curl);   # added for InTypo
                        $curl = str_replace(' -, ', ' &#8211;, ', $curl); # added for InTypo
                        $curl = str_replace('--', '&#8211;', $curl);
                        $curl = str_replace('xn&#8211;', 'xn--', $curl);
                        $curl = str_replace('...', '&#8230;', $curl);
                        $curl = str_replace('``', '&#8220;', $curl);

                        $curl = str_replace($cockney, $cockneyreplace, $curl);

                        $curl = preg_replace("/'s/", '&#8217;s', $curl);                      #apost
                        $curl = preg_replace("/'(\d\d(?:&#8217;|')?s)/", "&#8217;$1", $curl);
                        $curl = preg_replace('/(\s|\A|")(\(|\[*)\'/', '$1$2'.$quot[2], $curl);          #lsquo
                        $curl = preg_replace('/(\d+)"/', '$1&#8243;', $curl);                 #secs
                        $curl = preg_replace("/(\d+)'/", '$1&#8242;', $curl);                 #mins
                        $curl = preg_replace("/(\S)'([^'\s])/", '$1'.$quot[3].'$2', $curl);   #rsquo
                        $curl = preg_replace('/"([\.,;\!\?\s\)\]])/', $quot[1].'$1',$curl);  # rdquo
                        $curl = preg_replace('/(\s|\A)(\(|\[)*"(?!\s)/', '$1$2'.$quot[0].'$3', $curl);  #ldquo
                        $curl = str_replace('"',$quot[1],$curl); # rest is rdquo
                       #$curl = preg_replace('/"(\s|\S|\Z)/', $quot[1].'$1', $curl);          #rdquo
                       #$curl = preg_replace("/'([\s.]|\Z)/", '&#8217;$1', $curl); #apost false
                        $curl = preg_replace("/'([\s.]|\Z)/", $quot[3].'$1', $curl);          #rsquo fixed
                        $curl = preg_replace("/ \(tm\)/i", ' &#8482;', $curl);                #trade
                        $curl = str_replace("''", $quot[1], $curl);                           #rdquo
                        $curl = preg_replace('/(\d+)x(\d+)/', "$1&#215;$2", $curl);           #times

                } elseif (!$in_ignore && (strstr($curl, '<code') || strstr($curl, '<pre') || strstr($curl, '<kbd') || strstr($curl, '<style') || strstr($curl, '<script'))) {
                        // strstr is fast
                        $in_ignore = substr($curl, 1, 3); // first three letters are enough to distinguish the tags
                } elseif ($in_ignore && strstr($curl, '</' . $in_ignore)) {
                        $in_ignore = false;
                }
                $curl = preg_replace('/&([^#])(?![a-zA-Z1-4]{1,8};)/', '&#038;$1', $curl);   #amp
                $output .= $curl;
        }
        return $output;
}

function ity_lipsum($mark) {
    $ity_quot = ity_allquot();
    $text = $ity_quot[$mark][0].'Lorem ipsum '.$ity_quot[$mark][2].'dolor'.$ity_quot[$mark][3].' sit amet.'.$ity_quot[$mark][1];
    return $text;
}

/* admin page */
function ity_qmarkoptions() {
  require_once("languages.php");
  if (!isset($ity_quot)) $ity_quot = ity_allquot();
  $ity_lc = substr(WPLANG,0,2);
  if (!$ity_lc || strlen($ity_lc) == 0) {
    $ity_lc = 'en';
  }
/*   Appropriate styles for languages */
  $ity_style['en'] = Array(0);
  $ity_style['ga'] = Array(0);
  $ity_style['pt'] = Array(0,6);
  $ity_style['es'] = Array(0,6);
  $ity_style['nl'] = Array(1,2);
  $ity_style['fi'] = Array(1,5);
  $ity_style['sv'] = Array(1,5);
  $ity_style['pl'] = Array(2,4);
  $ity_style['cs'] = Array(3,4);
  $ity_style['da'] = Array(3,4);
  $ity_style['et'] = Array(3,6);
  $ity_style['de'] = Array(3,4,6);
  $ity_style['lv'] = Array(3,4);
  $ity_style['lt'] = Array(3,4);
  $ity_style['ro'] = Array(3,6);
  $ity_style['sl'] = Array(3,4);
  $ity_style['hr'] = Array(4);
  $ity_style['sq'] = Array(6,9);
  $ity_style['no'] = Array(6);
  $ity_style['tr'] = Array(6,11);
  $ity_style['fr'] = Array(7);
  $ity_style['it'] = Array(7,10);
  $ity_style['hu'] = Array(8);
/* generate dropdown language menu */
  $langlist = '';
  foreach ($ity_style as $key => $style) {
    $langlist .= '<option value="'.implode(",",$ity_style[$key]).'"';
    if ($key == $ity_lc) $langlist .= ' selected="selected"';
    $langlist .= '>'.$ity_lang[$key]."</option>";
  }
  ?>
<script type="text/javascript">
function ityselect() {
  langsel = document.ityform.itysel.value;
  langs = langsel.split(",");
  for (i = 0; i < <?php echo count($ity_quot); ?>; i++) {
    if (i == langs[0]||i == langs[1]||i == langs[2]) document.getElementById("ity"+i).style.display = "table-row";
    else document.getElementById("ity"+i).style.display = "none";
  }
}
</script>
  <div class="wrap">
    <h2>Intypo &ndash; International Typography 0.9.1</h2>
    <form name="ityform"><select name="itysel" onChange="ityselect();"><?php echo $langlist; ?></select></form>
<?php
      if (isset($_POST['ity_mark'])) {
        $ity_mark = $_POST['ity_mark'];
        $ity_cockney = stripslashes(strip_tags($_POST['ity_cockney']));
        $ity_option = $ity_mark.':'.implode(",",$ity_quot[$ity_mark]).':'.addslashes($ity_cockney);
        if (get_option("intypo_mark") == "") add_option("intypo_mark", $ity_option, null, true);
        else update_option("intypo_mark", $ity_option);
      }
      else {
	$ity_option = explode(":",get_option("intypo_mark"));
        $ity_mark = $ity_option[0];
        if ($ity_mark == "") $ity_mark = 0;            # if options page never visited
        if (isset($ity_option[2])) $ity_cockney = stripslashes($ity_option[2]);
        else $ity_cockney = "'tain't,'twere,'twas,'tis,'twill,'til,'bout,'nuff,'round,'cause,'t,'ne,'nen";
        echo "<p>Please select your favourite kind of quotation marks. &mdash; "
            ."Bitte w&auml;hlen Sie die gew&uuml;nschte Art von Anf&uuml;hrungszeichen.</p>\n";
      }
?>
    <p>Current setting: <strong><?php echo ity_lipsum($ity_mark); ?></strong></p>
    <form method="post">
      <table>
      <?php
        foreach ($ity_quot as $cmark => $value) {
          echo '<tr style="display:';
          if (isset($ity_style[$ity_lc]) && in_array($cmark,$ity_style[$ity_lc])) echo "table-row";
	  else echo "none";
          echo "\" id=\"ity$cmark\"><td><input type=\"radio\" name=\"ity_mark\" value=\"$cmark\"";
          if ($cmark == $ity_mark) echo " checked";
          echo ' /></td><td style="font:2em serif">'.ity_lipsum($cmark)."</td></tr>\n";
          }
      ?>
      </table>
      <div style="margin-top:1em">Terms beginning with apostrophe (comma seperated)<br /><input type="text" name="ity_cockney" value="<?php echo $ity_cockney; ?>" style="width:600px" /></div>
      <div class="submit"><input type="submit" name="info_update" value="<?php _e('Update Options'); ?> &#0187;" /></div>
    </form>
    <small>Quotation marks according to <a href="http://www.amazon.de/exec/obidos/ASIN/3874396428/schonersimuli-21/ref=nosim">Forssman / de Jong: Detailtypografie.</a> Mainz (Germany), 2nd issue 2004 (ISBN 3-87439-642-8), page 318f.</small>
    <p><a href="http://dossier.dunker.de/intypo/">Plugin Homepage</a></p>
  </div>
  <?php
}

# These arrays contain: opening and closing double quotation marks, then opening and closing single quotation marks
function ity_allquot() {
$ity_quot[0] = array('&#8220;','&#8221;','&#8216;','&#8217;'); # English commas
$ity_quot[1] = array('&#8221;','&#8221;','&#8217;','&#8217;'); # Dutch commas
$ity_quot[2] = array('&#8222;','&#8221;','&#8218;','&#8217;'); # Dutch commas with lower first
$ity_quot[3] = array('&#8222;','&#8220;','&#8218;','&#8216;'); # German commas
$ity_quot[4] = array('&#0187;','&#0171;','&#8250;','&#8249;'); # German guillemets
$ity_quot[5] = array('&#0187;','&#0187;','&#8250;','&#8250;'); # Swedish guillemets
$ity_quot[6] = array('&#0171;','&#0187;','&#8249;','&#8250;'); # Swiss guillemets
$ity_quot[7] = array('&#0171;&nbsp;','&nbsp;&#0187;','&#8249;&nbsp;','&nbsp;&#8250;'); # French guillemets
$ity_quot[8] = array('&#8222;','&#8221;','&#0171;','&#0187;'); # Hungarian
$ity_quot[9] = array('&#8220;','&#8222;','&#8216;','&#8218;'); # Albanian commas
$ity_quot[10] = array('&#8220;&nbsp;','&nbsp;&#8222;','&#8216;&nbsp;','&nbsp;&#8218;'); # Italian commas
$ity_quot[11] = array('&#8220;','&#8222;','&#8216;','&#8217;'); # Turkish commas
return $ity_quot;
}
?>