<?php

/**
 * @file
 * Handles the conversion of HTML content to RTF.
 */

namespace Drupal\bookexportrtf;

use CssParser;

class BookConvertRtf {
  // Variables to be used within the class.
  public $bookexportrtf_base_url;
  public $bookexportrtf_colortbl = [];
  public $bookexportrtf_toc = [];
  public $bookexportrtf_index = [];
  public $bookexportrtf_css = [];
  public $bookexportrtf_book_title = [];
  public $bookexportrtf_fonttbl = [];
  public $bookexportrtf_page_height = 16838;
  public $bookexportrtf_page_width = 11906;
  public $bookexportrtf_page_margin_top = 1440;
  public $bookexportrtf_page_margin_right = 1800;
  public $bookexportrtf_page_margin_bottom = 1800;
  public $bookexportrtf_page_margin_left = 1440;
  public $bookexportrtf_page_width_inner = 8306;

  public function __construct() {
    // Load the HTML parser.
    // Get it here: https://simplehtmldom.sourceforge.io/
    // Save simple_html_dom.php to: /libraries/simle_html_dom/
    include_once(DRUPAL_ROOT . '/libraries/simple_html_dom/simple_html_dom.php');

    // Load the CSS parser.
    // Get it here: https://github.com/Schepp/CSS-Parser
    // Save parser.php to: /libraries/schepp-css-parser/
    include_once(DRUPAL_ROOT . '/libraries/schepp-css-parser/parser.php');
    
    // Load the base url.
    global $base_url;
    $this->bookexportrtf_base_url = $base_url;
  }

  /**
   * Converts the book and its subpages to RTF.
   *
   * @param $css_file
   *   The css file to load.
   *
   * @return bool
   *   Whether the css file was loaded or not.
   */
  public function bookexportrtf_load_css($css_file) {
    $css = ["main" => []];
    if (is_file($css_file)) {
      $css_parser = new CssParser();
      $css_parser->load_files($css_file);
      $css_parser->parse();
      $css = $css_parser->parsed;

      foreach (array_keys($css['main']) as $selector) {
        foreach(array_keys($css['main'][$selector]) as $property) {
          $this->bookexportrtf_css[$selector][$property] = $css['main'][$selector][$property];
        }
      }
      // Set the default font.
      if (array_key_exists("body", $this->bookexportrtf_css)) {
        if (array_key_exists("font-family", $this->bookexportrtf_css["body"])) {
          preg_match("|^([^,]+),?|", $this->bookexportrtf_css["body"]["font-family"], $r);
          $font = trim($r[1]);
          $font = preg_replace("|\"|", "", $font);
          $this->bookexportrtf_fonttbl[$font] = 0;
        }
      }
      return true;
    }
    else {
      return false;
    }
  }

  /**
   * Converts the book and its subpages to RTF.
   *
   * @param string $content
   *   The the book in HTML format.
   * @param bool $is_book
   *   Whether the content is a book (TRUE) or a page (FALSE).
   *
   * @return string
   *   Return the book in RTF format.
   */
  public function bookexportrtf_convert($content, $is_book) {
    // Collect the page settings
    $size = explode(" ", trim($this->bookexportrtf_css[".page"]["size"]));
    $this->bookexportrtf_page_height = $this->bookexportrtf_convert_length($size[1]);
    $this->bookexportrtf_page_width = $this->bookexportrtf_convert_length($size[0]);
    $this->bookexportrtf_page_margin_top = $this->bookexportrtf_convert_length($this->bookexportrtf_css[".page"]["margin-top"]);
    $this->bookexportrtf_page_margin_right = $this->bookexportrtf_convert_length($this->bookexportrtf_css[".page"]["margin-right"]);
    $this->bookexportrtf_page_margin_bottom = $this->bookexportrtf_convert_length($this->bookexportrtf_css[".page"]["margin-bottom"]);
    $this->bookexportrtf_page_margin_left = $this->bookexportrtf_convert_length($this->bookexportrtf_css[".page"]["margin-left"]);
    $this->bookexportrtf_page_width_inner = $this->bookexportrtf_page_width - $this->bookexportrtf_page_margin_left - $this->bookexportrtf_page_margin_right;

    // Prepare the HTML for processing.

    // Remove everything before and after the HTML tags.
    $content = preg_replace("|^[^<]+|", "", $content);
    $content = preg_replace("|>[^>]+$|", ">", $content);

    // Replace newlines within pre elements with rtf newlines, then remove all
    // newlines
    $html = str_get_html($content, true, true, 'UTF-8', false);
    $elements = $html->find("pre");
    foreach ($elements as $e) {
      $e->outertext = preg_replace("|[\r\n]|", "\\tab\\line ", $e->outertext);
    }
    $content = $html;
    $content = preg_replace("|[\r\n]|", "", $content);

    // Remove white-space between structural elements.
    foreach (['td', 'p', 'li', 'div', 'h1', 'h2', 'h3', 'ol', 'ul', 'body', 'head', 'html', 'pre', 'code'] as $element) {
      $content = preg_replace("|<\/".$element.">\s+<|", "</".$element."><", $content);
      $content = preg_replace("|>\s+<".$element."|", "><".$element, $content);
    }
    $content = preg_replace("|-->\s+<|", "--><", $content);
    $content = preg_replace("|>\s+<!--|", "><!--", $content);
    // Remove white-space after the body and br.
    $content = preg_replace("|<body>\s+|", "<body>", $content);
    $content = preg_replace("|<br>\s+|", "<br>", $content);
    $content = preg_replace("|<br />\s+|", "<br />", $content);

    // Create the HTML object.
    $html = str_get_html($content);

    // Get the table of contents (all h1) and title (first h1)
    $toc = $html->find("h1");
    $this->bookexportrtf_book_title = $toc[0]->innertext;

    // Convert the content first as the font and color tables may be appended
    // during the conversion process.
    $elements = $html->find('html');
    $this->bookexportrtf_traverse($elements);
    $content = strip_tags($html);

    // Generate the footer second, so the header can be added to the toc.
    //
    // Footer
    // - index

    $footer = "";

    if ($is_book && count($this->bookexportrtf_index) > 0) {
      // Add a new chapter unless the last chapter is the index.
      if ($toc[array_key_last($toc)]->innertext != "Index") {
        $elements = $html->find("article");
        $section_style = $this->bookexportrtf_get_rtf_style_from_element($elements[1]);
        $footer .= "\r\n\\sect"  . $section_style[1] . "\r\n";
        $header_html = str_get_html("<html><body><h1>" . t("Index") . "</h1></body></html>");
        $e = $header_html->find("html");
        // Process through bookexportrtf_traverse() to add it to the toc and
        // update the font and color table.
        $this->bookexportrtf_traverse($e);
        $footer .= strip_tags($header_html);
      }

      $footer .= "\\sect\\sbknone\\cols2\r\n";

      ksort($this->bookexportrtf_index);
      $cur_initial = "";
      foreach (array_keys($this->bookexportrtf_index) as $label) {
        $anchor = "index-" . $this->bookexportrtf_index[$label];
        $initial = substr($label, 0, 1);
        if (is_numeric($initial)) {
          $initial = "#";
        }
        $h2_style = $this->bookexportrtf_get_rtf_style_from_selector("h2");
        // Try to find inheritted style elements
        $h2 = $html->find('h2');
        if (isset($h2[0])) {
          $h2_style = $this->bookexportrtf_get_rtf_style_from_element($h2[0]);
        }
        $p = $html->find('p');
        $p_style = $this->bookexportrtf_get_rtf_style_from_element($p[0]);
        if ($initial != $cur_initial) {
          if ($cur_initial != "") {
            $footer .= "\\par}\r\n";
          }
          $footer .= $h2_style[0] . "{\\pard " . $h2_style[1] .  $initial . "\\par}\r\n";
          $footer .= "{\\pard " . $p_style[1] . $label . " {\\field{\*\\fldinst PAGEREF ".$anchor."}}";
          $cur_initial = $initial;
        }
        else {
          // add a tab to keep the label and page togeter in case text is aligned justified.
          $footer .= "\\tab\\line\r\n" . $label . " {\\field{\*\\fldinst PAGEREF ".$anchor."}}";
        }
      }
      $footer .= "\\par}\r\n";
      $footer .= $section_style[2];
    }

    // Generate the header.
    //
    // Header for books
    // - RTF header
    // - Front page
    // - Flyleaf containing URL and date of download
    // - Table of contents
    //
    // Header for pages
    // - RTF header
    // - Merged front page and flyleaf

    // RTF header
    $header = "\\rtf1\\ansi\r\n";
    $header .= "\\deff0 {\\fonttbl ";
    if (!is_array($this->bookexportrtf_fonttbl)) {
      $header .= "{\\f0 Calibri;}";
    }
    else {
      foreach (array_keys($this->bookexportrtf_fonttbl) as $font) {
        $header .= "{\\f" . $this->bookexportrtf_fonttbl[$font] . "\\fnil " . $font . ";}";
      }
    }
    $header .= "}\r\n";
    if (count($this->bookexportrtf_colortbl) > 0) {
      $header .= "{\\colortbl ;";
      foreach (array_keys($this->bookexportrtf_colortbl) as $color) {
        $header .= " " . $color . ";";
      }
      $header .= "}\r\n";
    }
    $page_style = $this->bookexportrtf_get_rtf_style_from_selector(".page");
    $header .= "\\vertdoc\\paperh" . $this->bookexportrtf_page_height . "\\paperw" . $this->bookexportrtf_page_width . "\r\n";
    $header .= "\\margl" . $this->bookexportrtf_page_margin_left;
    $header .= "\\margr" . $this->bookexportrtf_page_margin_right;
    $header .= "\\margt" . $this->bookexportrtf_page_margin_top;
    $header .= "\\margb" . $this->bookexportrtf_page_margin_bottom . "\r\n";
    $header .= "\\fet0\\facingp\\ftnbj\\ftnrstpg\\widowctrl\r\n";
    $header .= "\\plain\r\n";

    // Front page and flyleaf
    $style = $this->bookexportrtf_get_rtf_style_from_selector(".book-title");
    $header .= "{\\pard " . $style[1] . $this->bookexportrtf_book_title . " \\par}\r\n";
    if ($is_book) {
      $header .= "\\sect\\sftnrstpg\r\n";
      $header .= "{\\pard\\qc {\\b " . $this->bookexportrtf_book_title . "}\\par}\r\n";
    }
    if (isset($this->bookexportrtf_base_url)) {
      $header .= "{\\pard\\qc " . $this->bookexportrtf_base_url . "\\par}\r\n";
    }
    $header .= "\\line\r\n\\line\r\n";
    $header .= "{\\pard\\qc " . t("Generated: ") . \Drupal::service('date.formatter')->format(time(), "long") . "\\par}\r\n";

    // Table of contents
    if ($is_book) {
      $header .= "\\sect\\sftnrstpg\r\n";

      $header .= "{\\pard ";
      $style = $this->bookexportrtf_get_rtf_style_from_element($toc[0]);
      $header .=  $style[1];
      $header .= t("Contents");
      $header .= "\\par}\r\n{\\pard {";

      // Remove the first title as this should be the title of the book and
      // does not belong in the toc.
      $book_title_element = array_shift($toc);
      $book_title_element->outertext = "";
      $tid = 1;
      foreach ($this->bookexportrtf_toc as $toc_title) {
        $header .= "\\trowd";
        $header .= "\\cellx7000 \\cellx8309\r\n";
        $header .= "\\pard\\intbl " . $toc_title . "\\cell";
        $header .= "\\qr{\\field{\*\\fldinst PAGEREF chapter";
        $header .= $tid;
        $header .= "}}\\cell\\row\r\n";
        $tid++;
      }

      $header .= "}\\par}\r\n";
    }

    // Make the final document.
    $content = "{" . $header . trim($content) . $footer . "}";

    // Encode extended ascii characters as RTF.
    $content = preg_replace("|€|", "\'80", $content);
    // $content = preg_replace("|foo|", "\'81", $content);
    $content = preg_replace("|‚|", "\'82", $content);
    $content = preg_replace("|ƒ|", "\'83", $content);
    $content = preg_replace("|„|", "\'84", $content);
    $content = preg_replace("|…|", "\'85", $content);
    $content = preg_replace("|†|", "\'86", $content);
    $content = preg_replace("|‡|", "\'87", $content);
    $content = preg_replace("|ˆ|", "\'88", $content);
    $content = preg_replace("|‰|", "\'89", $content);
    $content = preg_replace("|Š|", "\'8a", $content);
    $content = preg_replace("|‹|", "\'8b", $content);
    $content = preg_replace("|Œ|", "\'8c", $content);
    // $content = preg_replace("|foo|", "\'8d", $content);
    $content = preg_replace("|Ž|", "\'8e", $content);
    // $content = preg_replace("|foo|", "\'8f", $content);
    $content = preg_replace("|‘|", "\'91", $content);
    $content = preg_replace("|’|", "\'92", $content);
    $content = preg_replace("|´|", "\'b4", $content);
    $content = preg_replace("|“|", "\'93", $content);
    $content = preg_replace("|”|", "\'94", $content);
    $content = preg_replace("|•|", "\'95", $content);
    $content = preg_replace("|–|", "\'96", $content);
    $content = preg_replace("|–|", "\'97", $content);
    $content = preg_replace("|˜|", "\'98", $content);
    $content = preg_replace("|™|", "\'99", $content);
    $content = preg_replace("|š|", "\'9a", $content);
    $content = preg_replace("|›|", "\'9b", $content);
    $content = preg_replace("|œ|", "\'9c", $content);
    // $content = preg_replace("|foo|", "\'9d", $content);
    $content = preg_replace("|ž|", "\'9e", $content);
    $content = preg_replace("|Ÿ|", "\'9f", $content);
    // $content = preg_replace("|foo|", "\'a0", $content);
    $content = preg_replace("|¡|", "\'a1", $content);
    $content = preg_replace("|¢|", "\'a2", $content);
    $content = preg_replace("|£|", "\'a3", $content);
    $content = preg_replace("|¤|", "\'a4", $content);
    $content = preg_replace("|¥|", "\'a5", $content);
    $content = preg_replace("|¦|", "\'a6", $content);
    $content = preg_replace("|§|", "\'a7", $content);
    $content = preg_replace("|¨|", "\'a8", $content);
    $content = preg_replace("|©|", "\'a9", $content);
    $content = preg_replace("|ª|", "\'aa", $content);
    $content = preg_replace("|«|", "\'ab", $content);
    $content = preg_replace("|¬|", "\'ac", $content);
    // $content = preg_replace("|foo|", "\'ad", $content); // Should be soft hyphen
    $content = preg_replace("|®|", "\'ae", $content);
    $content = preg_replace("|¯|", "\'af", $content);
    $content = preg_replace("|°|", "\'b0", $content);
    $content = preg_replace("|±|", "\'b1", $content);
    $content = preg_replace("|²|", "\'b2", $content);
    $content = preg_replace("|³|", "\'b3", $content);
    $content = preg_replace("|´|", "\'b4", $content);
    $content = preg_replace("|µ|", "\'b5", $content);
    $content = preg_replace("|¶|", "\'b6", $content);
    $content = preg_replace("|·|", "\'b7", $content);
    $content = preg_replace("|¸|", "\'b8", $content);
    $content = preg_replace("|¹|", "\'b9", $content);
    $content = preg_replace("|º|", "\'ba", $content);
    $content = preg_replace("|»|", "\'bb", $content);
    $content = preg_replace("|¼|", "\'bc", $content);
    $content = preg_replace("|½|", "\'bd", $content);
    $content = preg_replace("|¾|", "\'be", $content);
    $content = preg_replace("|¿|", "\'bf", $content);
    $content = preg_replace("|À|", "\'c0", $content);
    $content = preg_replace("|Á|", "\'c1", $content);
    $content = preg_replace("|Â|", "\'c2", $content);
    $content = preg_replace("|Ã|", "\'c3", $content);
    $content = preg_replace("|Ä|", "\'c4", $content);
    $content = preg_replace("|Å|", "\'c5", $content);
    $content = preg_replace("|Æ|", "\'c6", $content);
    $content = preg_replace("|Ç|", "\'c7", $content);
    $content = preg_replace("|È|", "\'c8", $content);
    $content = preg_replace("|É|", "\'c9", $content);
    $content = preg_replace("|Ê|", "\'ca", $content);
    $content = preg_replace("|Ë|", "\'cb", $content);
    $content = preg_replace("|Ì|", "\'cc", $content);
    $content = preg_replace("|Í|", "\'cd", $content);
    $content = preg_replace("|Î|", "\'ce", $content);
    $content = preg_replace("|Ï|", "\'cf", $content);
    $content = preg_replace("|Ð|", "\'d0", $content);
    $content = preg_replace("|Ñ|", "\'d1", $content);
    $content = preg_replace("|Ò|", "\'d2", $content);
    $content = preg_replace("|Ó|", "\'d3", $content);
    $content = preg_replace("|Ô|", "\'d4", $content);
    $content = preg_replace("|Õ|", "\'d5", $content);
    $content = preg_replace("|Ö|", "\'d6", $content);
    $content = preg_replace("|×|", "\'d7", $content);
    $content = preg_replace("|Ø|", "\'d8", $content);
    $content = preg_replace("|Ù|", "\'d9", $content);
    $content = preg_replace("|Ú|", "\'da", $content);
    $content = preg_replace("|Û|", "\'db", $content);
    $content = preg_replace("|Ü|", "\'dc", $content);
    $content = preg_replace("|Ý|", "\'dd", $content);
    $content = preg_replace("|Þ|", "\'de", $content);
    $content = preg_replace("|ß|", "\'df", $content);
    $content = preg_replace("|à|", "\'e0", $content);
    $content = preg_replace("|á|", "\'e1", $content);
    $content = preg_replace("|â|", "\'e2", $content);
    $content = preg_replace("|ã|", "\'e3", $content);
    $content = preg_replace("|ä|", "\'e4", $content);
    $content = preg_replace("|å|", "\'e5", $content);
    $content = preg_replace("|æ|", "\'e6", $content);
    $content = preg_replace("|ç|", "\'e7", $content);
    $content = preg_replace("|è|", "\'e8", $content);
    $content = preg_replace("|é|", "\'e9", $content);
    $content = preg_replace("|ê|", "\'ea", $content);
    $content = preg_replace("|ë|", "\'eb", $content);
    $content = preg_replace("|ì|", "\'ec", $content);
    $content = preg_replace("|í|", "\'ed", $content);
    $content = preg_replace("|î|", "\'ee", $content);
    $content = preg_replace("|ï|", "\'ef", $content);
    $content = preg_replace("|ð|", "\'f0", $content);
    $content = preg_replace("|ñ|", "\'f1", $content);
    $content = preg_replace("|ò|", "\'f2", $content);
    $content = preg_replace("|ó|", "\'f3", $content);
    $content = preg_replace("|ô|", "\'f4", $content);
    $content = preg_replace("|õ|", "\'f5", $content);
    $content = preg_replace("|ö|", "\'f6", $content);
    $content = preg_replace("|÷|", "\'f7", $content);
    $content = preg_replace("|ø|", "\'f8", $content);
    $content = preg_replace("|ù|", "\'f9", $content);
    $content = preg_replace("|ú|", "\'fa", $content);
    $content = preg_replace("|û|", "\'fb", $content);
    $content = preg_replace("|ü|", "\'fc", $content);
    $content = preg_replace("|ý|", "\'fd", $content);
    $content = preg_replace("|þ|", "\'fe", $content);
    $content = preg_replace("|ÿ|", "\'ff", $content);

    // Encode HTML characters as RTF.
    $content = preg_replace("|&amp;|", "&", $content);
    $content = preg_replace("|&deg;|", "\'b0", $content);
    $content = preg_replace("|&gt;|", ">", $content);
    $content = preg_replace("|&lt;|", "<", $content);
    $content = preg_replace("|&nbsp;|", " ", $content);
    $content = preg_replace("|&#039;|", "'", $content);

    // Encode HTML non-breaking space as RTF.
    $content = preg_replace("|\x{C2}\x{A0}|", " ", $content);

    return $content;
  }

  /**
   * Traverse the HTML tree and change HTML into RTF.
   *
   * The HTML is traversed backwards replacing HTML elements by their
   * corresponding RTF elements. Does not return anything but replaces the
   * $html element.
   *
   * @param array $elements
   *   The elements from the HTML tree from which to start.
   *
   */
  public function bookexportrtf_traverse($elements) {
    foreach ($elements as $e) {
      if ($e->first_child()) {
        $children = $e->children();
        $this->bookexportrtf_traverse($children);
      }

      // No children anymore, start changing tags.
      $tag = $e->tag;

      switch($tag) {
        case 'a':
          if ($e->href) {
            // Link, keep the text if the url and link text are the same,
            // otherwise use the link text and add a footnote with the url.
            $url = $e->href;
            $title = $e->innertext;

            if (preg_match("|^(https?://)?(mailto:)?" . $title . "/?$|", $url)) {
              $e->outertext = $title;
            }
            else {
              $e->outertext = $title . "{\\footnote \\pard {\\super \\chftn} " . $url . "}";
            }
          }
          else if ($e->name) {
            // Anchor, replace for the index, ignore others.
            if (preg_match("|^index|", $e->name)) {
              $label = substr($e->name, 5);
              if (!array_key_exists($label, $this->bookexportrtf_index)) {
                $iid = count($this->bookexportrtf_index);
                $this->bookexportrtf_index[$label] = $iid;
                $e->outertext = "{\\*\\bkmkstart index-" . $iid . "}{\\*\\bkmkend index-".$iid."}";
              }
              else {
                // Double entry, remove
                $e->outertext = "";
              }
            }
          }
          break;

        case 'article':
          // Book pages are within article elements. Start a new section with a
          // style based on article depth.
          $depth = 1;
          $p = $e->parent();
          while($p) {
            if ($p->tag == 'article') {
              $depth++;
            }
            $p = $p->parent();
          }

          $e->class .= " article-depth-" . $depth;

          $style = $this->bookexportrtf_get_rtf_style_from_element($e);
          $e->outertext = "\\sect\\sftnrstpg" . $style[1] . "\r\n" . $e->innertext . $style[2];
          break;

        case 'br':
          // Add a tab before the newline otherwise justified last lines will
          // be justified instead of left aligned.
          $e->outertext = "\\tab\\line\r\n";
          break;

       case 'div':
         // Add page-break before or page-break after if applicable.
         $style = $this->bookexportrtf_get_rtf_style_from_element($e);
         $e->outertext = $style[0] . $e->innertext . $style[2];
          break;

        case 'h1':
          // Chapter titles. Reset the headers and footers, add the chapter to
          // the table of contents and add the chapter title iteslf.
          $title = $e->innertext;

          $style = $this->bookexportrtf_get_rtf_style_from_element($e);
          $rtf = $style[0];

          foreach ([".header-left", ".header-right", ".footer-left", ".footer-right"] as $selector) {
            $css = $this->bookexportrtf_css[$selector];
            if (!array_key_exists("display", $css)) {
              $css["display"] = "initial";
            }
            if (trim($css["display"]) != "none") {
              $element_style = $this->bookexportrtf_get_rtf_style_from_selector($selector);
              if ($selector == ".header-left") {
                $rtf .= "{\\headerl\\pard ". $element_style[1] . $this->bookexportrtf_book_title . "\\par}\r\n";
              }
              else if ($selector == ".header-right") {
                $rtf .= "{\\headerr\\pard ". $element_style[1] . $title . "\\par}\r\n";
              }
              else if ($selector == ".footer-left") {
                $rtf .= "{\\footerl\\pard ". $element_style[1] . "\\chpgn \\par}\r\n";
              }
              else if ($selector == ".footer-right") {
                $rtf .= "{\\footerr\\pard ". $element_style[1] . "\\chpgn \\par}\r\n";
              }
            }
          }

          array_push($this->bookexportrtf_toc, $title);
          $tid = count($this->bookexportrtf_toc);
          $rtf .= "{\\*\\bkmkstart chapter".$tid."}{\\*\\bkmkend chapter".$tid."}\r\n";

          $rtf .= "{\\pard " . $style[1] . $title . "\\par}\r\n" . $style[2];

          $e->outertext = $rtf;
          break;

        case 'h2':
        case 'h3':
        case 'h4':
        case 'h5':
        case 'h6':
          // Sub headers
          $style = $this->bookexportrtf_get_rtf_style_from_element($e);
          $e->outertext = $style[0] . "{\\pard " . $style[1] . $e->innertext . "\\par}\r\n" . $style[2];
          break;

        case 'head':
          // Remove the head as it does not contain useful information.
          $e->outertext = "";
          break;

        case 'i':
          $e->outertext = "{\\i " . $e->innertext . "}";
          break;

        case 'img':
          // Download the image and add it to the book. If the image could not
          // be added, add the alt-text instead.
          $url = $e->src;
          if (substr($url, 0, 4) != "http") {
            // Paranoia check, relative urls should always be based on the host.
            if (substr($url, 0, 1) == "/") {
              // relative urls stems from host
              $url = parse_url($this->bookexportrtf_base_url, PHP_URL_SCHEME) . "://" .
                     parse_url($this->bookexportrtf_base_url, PHP_URL_HOST) . $url;
            }
            else {
              $url = $this->bookexportrtf_base_url . "/" . $url;
            }
          }

          $string = file_get_contents($url);

          // Replace the image with the alt-text if it was not found.
          if (!$string) {
            if (isset($e->alt)) {
              $rtf = "{\\pard " . $e->alt . "\\par}\r\n";
            }
            $e->outertext = $rtf;
            break;
          }

          $info = getimagesizefromstring($string);

          // Set the image dimensions. Scale to page width if wider.
          $width = $info[0];
          $height = $info[1];
          $picwidth = $this->bookexportrtf_convert_length($width . "px");
          $picheight = $this->bookexportrtf_convert_length($height . "px");
          if ($picwidth > $this->bookexportrtf_page_width_inner) {
            $ratio = $width/$height;
            $picwidth = $this->bookexportrtf_page_width_inner;
            $picheight = round($picwidth / $ratio);
          }
          $rtf = "{";
          $rtf .= "\\pard{\\pict";
          $rtf .= "\\picw" . $width;
          $rtf .= "\\pich" . $height;
          $rtf .= "\\picwgoal" . $picwidth;
          $rtf .= "\\pichgoal" . $picheight;
          $rtf .= "\\picscalex100\\picscaley100";

          // Set the image type.
          switch($info['mime']) {
            case "image/png":
              $rtf .= "\\pngblip\r\n";
              break;
            case "image/jpeg":
              $rtf .= "\\jpegblip\r\n";
              break;
            case "image/gif":
              // RTF does not support gif, convert to png
              $img = imagecreatefromstring($string);
              ob_start();
              imagepng($img);
              $string = ob_get_contents();
              ob_end_clean();
              $rtf .= "\\pngblip\r\n";
          }

          // Convert the image data.
          $rtf .= wordwrap(bin2hex($string), 80, "\r\n", TRUE);
          $rtf .= "\r\n}\\par}\r\n";

          $e->outertext = $rtf;
          break;

        case 'li':
          // Replace the list item with indentation based on depth and the 
          // appropriate marker. Add opening and closing lay-out to the first
          // and last item in a list.

          $rtf = "";

          $depth = 0;
          $type = "ul";
          $number = 1;
          $lastinlevel = 1;
          $lastinlist = 1;

          // Get the type and depth of the list.
          $p = $e->parent();
          while($p) {
            if ($p->tag == "ul" | $p->tag == "ol") {
              if ($depth == 0) {
                $type = $p->tag;
              }
              $depth++;
            }
            $p = $p->parent();
          }
          // Get the place in the list.
          $s = $e->prev_sibling();
          while($s) {
            if ($s->tag == "li") {
              $number++;
            }
            $s = $s->prev_sibling();
          }
          // Is it the last item in the list? Also check for more items in the
          // parents and children. Add a class to mark the end of the list.
          $s = $e->next_sibling();
          while($s) {
            if ($s->tag == "li") {
              $lastinlevel = 0;
              $lastinlist = 0;
              break;
            }
            $s = $s->next_sibling();
          }
          if ($lastinlist) {
            $p = $e->parent();
            while ($p) {
              if ($p->tag == "li") {
                $s = $p->next_sibling();
                if (isset($s)) {
                  if ($s->tag == "li") {
                    $lastinlist = 0;
                    break;
                  }
                }
              }
              $p = $p->parent();
            }
          }
          if ($lastinlist) {
            $children = $e->children();
            foreach ($children as $c) {
              array_merge($children, $c->children());
              if ($c->tag == "ol" | $c->tag == "ul") {
                $lastinlist = 0;
                break;
              }
            }
          }
          if ($lastinlist) {
            $e->class = $e->class . " last-item-in-list";
          }

          // If the first item of a nested list close the current paragraph.
          if ($depth > 1 & $number == 1) {
            $rtf .= "\\par}\r\n";
          }

          // The left indentation for depth 1 is returned as li### and has to
          // be multiplied with the depth of the list.
          $style = $this->bookexportrtf_get_rtf_style_from_element($e);
          preg_match("|li(\d+)|", $style[1], $matches);
          $style[1] = preg_replace("|\\\\li\d+|","\\li" . ($depth*$matches[1]), $style[1]);

          // Wrap the list item in a paragraph
          $rtf .= "{\\pard " . $style[1];

          // Add the marker
          if ($type == "ul") {
            $rtf .= "\\bullet\\tab ";
          }
          else {
            $rtf .= " " . $number . ".\\tab ";
          }
          $rtf .= $e->innertext;

          // Finish the paragraph unless it's the last item in a nested list.
          if ($lastinlevel != 1 | $depth == 1) {
            $rtf .= "\\par}\r\n";
          }

          /**
           * @todo Text after the nested list will be included in the last item
           * of the nested list. That should not be the case.
           */

          $e->outertext = $rtf;
          break;

        case 'code':
        case 'p':
          // These are all paragraphs with a specific style.
          $css = $this->bookexportrtf_get_css_style_from_element($e);
          if (array_key_exists("display", $css)) {
            if (trim($css["display"]) == "none") {
              $e->outertext = "";
            }
          }
          else {
            $style = $this->bookexportrtf_get_rtf_style_from_css($css, $e->tag);
            $e->outertext = "{\\pard " . $style[1] . $e->innertext . "\\par}\r\n";
          }
          break;

        case 's':
        case 'del':
        case 'ins':
        case 'span':
          // These are inline elements with  a specific style.
          $css = $this->bookexportrtf_get_css_style_from_element($e);
          if (array_key_exists("display", $css)) {
            if (trim($css["display"]) == "none") {
              $e->outertext = "";
            }
          }
          else {
            $style = $this->bookexportrtf_get_rtf_style_from_css($css, $e->tag);
            $e->outertext = "{" . $style[1] . $e->innertext . "}";
          }
          break;

        case 'strong':
        case 'b':
          $e->outertext = "{\\b " . $e->innertext . "}";
          break;

        case 'strike':
          $e->outertext = "{\\strike " . $e->innertext . "}";
          break;

        case 'sub':
          $e->outertext = "{\\sub " . $e->innertext . "}";
          break;

        case 'sup':
          $e->outertext = "{\\super " . $e->innertext . "}";
          break;

        case 'tbody':
          // Get the contents of the whole table and then remake it in RTF.

          // Get the table dimensions, content and column width.
          $num_rows = 0;
          $num_cols = 0;
          $table;
          $colwidth = [];

          $rows = $e->children();
          foreach ($rows as $r) {
            if ($r->tag != "tr") {
              continue;
            }
            $num_rows++;
            $cells = $r->children();
            $cur_cols = 0;
            foreach ($cells as $c) {
              if ($c->tag != 'td' & $c->tag != 'th') {
                continue;
              }
              $cur_cols++;
              $table[$num_rows][$cur_cols]['element'] = $c;
              $table[$num_rows][$cur_cols]['innertext'] = $c->innertext;
              $table[$num_rows][$cur_cols]['col'] = $cur_cols;
              if ($c->colspan) {
                $table[$num_rows][$cur_cols]['colspan'] = $c->colspan;
              }
              else {
                $table[$num_rows][$cur_cols]['colspan'] = 1;
              }

              $css = $this->bookexportrtf_get_css_style_from_element($c);
              $style = $this->bookexportrtf_get_rtf_style_from_css($css, $c->tag);

              $table[$num_rows][$cur_cols]['style'] = $css;
              $table[$num_rows][$cur_cols]['style_prefix'] = $style[0];
              $table[$num_rows][$cur_cols]['style_infix'] = $style[1];

              if (array_key_exists('width', $css)) {
                $colwidth[$cur_cols] = $this->bookexportrtf_convert_length($css['width']);
              }

              // Correct cur_cols for colspan.
              $cur_cols += $table[$num_rows][$cur_cols]['colspan']-1;
            }
            if ($cur_cols > $num_cols) {
              $num_cols = $cur_cols;
            }
          }

          // Calculate column width:
          // 1. Determined width already defined
          // 2. Space out evenly over the remaining columns
          $col_right = [];
          $defined_width = 0;
          $auto = 0;
          for ($col = 1; $col <= $num_cols; $col++) {
            if (array_key_exists($col, $colwidth)) {
              $defined_width += $colwidth[$col];
            }
            else {
              $auto++;
            }
          }

          $auto_width = ($this->bookexportrtf_page_width_inner - $defined_width)/$auto;

          $col_left = 0;
          for ($col = 1; $col <= $num_cols; $col++) {
            if (array_key_exists($col, $colwidth)) {
              $col_left += $colwidth[$col];
            }
            else {
              $col_left += $auto_width;
            }
            $col_right[$col] = ceil($col_left);
          }

          // Build the table
          $rtf = "{";
          foreach ($table as $row) {
            $rtf .= "\\trowd\r\n";

            // First itteration to define cell style.
            foreach ($row as $cell) {
              $rtf .= $cell['style_prefix'];
              $rtf .= "\\cellx";
              $rtf .= $col_right[$cell['col']+$cell['colspan']-1];
              $rtf .= "\r\n";
            }

            // Second iteration to make the cells themselves.
            foreach ($row as $cell) {
              $rtf .= "\\pard\\intbl{";
              $rtf .= $cell['style_infix'];
              $rtf .= $cell['innertext'];
              $rtf .= "}\\cell\r\n";
            }
            $rtf .= "\\row\r\n";
          }
          $rtf .= "}\r\n{\\pard\\sa0\\par}\r\n";

          $e->outertext = $rtf;
          break;

        case 'u':
          // Use CSS to get the right underlyne style.
          $style = $this->bookexportrtf_get_rtf_style_from_element($e);
          $e->outertext = "{" . $style[1] . $e->innertext . "}";
      }
    }
  }

  /**
   * Get the style for an HTML element.
   *
   * @param object $e
   *   An element from the html tree.
   *
   * @return array
   *   The list of css properties and values.
   */
  public function bookexportrtf_get_css_style_from_element($e) {
    // Should we look in parents for additional style elements?
    // Most of these aren't used but keep them in for completeness.
    $css_inherit = [
      'border-collapse' => 1,
      'border-spacing' => 1,
      'caption-side' => 1,
      'color' => 1,
      'cursor' => 1,
      'direction' => 1,
      'empty-cells' => 1,
      'font-family' => 1,
      'font-size' => 1,
      'font-style' => 1,
      'font-variant' => 1,
      'font-weight' => 1,
      'font-size-adjust' => 1,
      'font-stretch' => 1,
      'font' => 1,
      'letter-spacing' => 1,
      'line-height' => 1,
      'list-style-image' => 1,
      'list-style-position' => 1,
      'list-style-type' => 1,
      'list-style' => 1,
      'orphans' => 1,
      'quotes' => 1,
      'tab-size' => 1,
      'text-align' => 1,
      'text-align-last' => 1,
      'text-decoration-color' => 1,
      'text-indent' => 1,
      'text-justify' => 1,
      'text-shadow' => 1,
      'text-transform' => 1,
      'visibility' => 1,
      'white-space' => 1,
      'widows' => 1,
      'word-break' => 1,
      'word-spacing' => 1,
      'word-wrap' => 1];

    $css = [];
    $depth = 0;

    // Start the cascade looking upwards from the element to get all the CSS.
    while ($e) {
      // First check the elements style attribute.
      $style = $e->style;
      if ($style != '') {
        $style = ".attribute {" . $style . " }";
        $css_parser = new CssParser();
        $css_parser->load_string($style);
        $css_parser->parse();
        $my_css = $css_parser->parsed;

        foreach (array_keys($my_css['main']['.attribute']) as $property) {
          // inheritance by default
          if (!array_key_exists($property, $css) & ($depth == 0 | array_key_exists($property, $css_inherit))) {
            $css[$property] = $my_css['main']['.attribute'][$property];
          }
          // inheritance by setting
          if (array_key_exists($property, $css)) {
            if (trim($css[$property]) == 'inherit') {
              $css[$property] = $my_css['main']['.attribute'][$property];
            }
          }
        }
      }

      // Then get the CSS associated with the element's id, class and element.
      $id = '#' . $e->id;
      $classes = explode(' ', $e->class);
      $classes = array_map(static function ($class) {
            return "." . $class;
        }, $classes);
      $tag = $e->tag;

      foreach (array_merge([$id], $classes, [$tag]) as $selector) {
        if (array_key_exists($selector, $this->bookexportrtf_css)) {
          foreach (array_keys($this->bookexportrtf_css[$selector]) as $property) {
            // Inheritance by default.
            if (!array_key_exists($property, $css) & ($depth == 0 | array_key_exists($property, $css_inherit))) {
              $css[$property] = $this->bookexportrtf_css[$selector][$property];
            }
            // Inheritance by setting.
            if (array_key_exists($property, $css)) {
              if (trim($css[$property]) == 'inherit') {
                $css[$property] = $this->bookexportrtf_css[$selector][$property];
              }
            }
          }
        }
      }

      $e = $e->parent();
      $depth--;
    }

    return $css;
  }

  /**
   * Retrieve the RTF markup from an HTML element.
   *
   * @param object $element
   *   An HTML element
   *
   * @return array
   *   The RTF markup as prefix, infix and suffix.
   */
  public function bookexportrtf_get_rtf_style_from_element($element) {
    return $this->bookexportrtf_get_rtf_style_from_css($this->bookexportrtf_get_css_style_from_element($element), $element->tag);
  }

  /**
   * Retrieve the RTF markup from an CSS selector.
   *
   * @param string $selector
   *   The CSS selector.
   *
   * @return array
   *   The RTF markup as prefix, infix and suffix.
   */
  public function bookexportrtf_get_rtf_style_from_selector($selector) {
    return $this->bookexportrtf_get_rtf_style_from_css($this->bookexportrtf_css[$selector], $selector);
  }

  /**
   * Convert a CSS-array into the appropriate RTF markup.
   *
   * @param array $css
   *   The css property-value pairs.
   *
   * @param string $selector
   *   A selector
   *
   * @return array
   *   The RTF markup as prefix, infix and suffix.
   */
  public function bookexportrtf_get_rtf_style_from_css($css, $selector) {
    if (empty($selector)) {
      return ["","",""];
    }

    // there are 4 basic style elements:
    //   div: page break
    //   p: font, margin etc.
    //   td: like p, but with borders
    //   span: font only
    // several other elements have similar properties to p, td or span.

    $supported = [
      'div' => [
        'page-break-before' => 1,
        'page-break-after' => 1,],
      'p' => [
        'color' => 1,
        'font-family' => 1,
        'font-size' => 1,
        'font-weight' => 1,
        'margin-top' => 1,
        'margin-right' => 1,
        'margin-bottom' => 1,
        'margin-left' => 1,
        'text-align' => 1,
        'text-decoration' => 1,
        'text-decoration-color' => 1,
        'text-decoration-style' => 1,
        'text-indent' => 1],
      'td' => [
        'color' => 1,
        'border-bottom-style' => 1,
        'border-bottom-width' => 1,
        'border-left-style' => 1,
        'border-left-width' => 1,
        'border-right-style' => 1,
        'border-right-width' => 1,
        'border-top-style' => 1,
        'border-top-width' => 1,
        'margin-top' => 1,
        'margin-right' => 1,
        'margin-bottom' => 1,
        'margin-left' => 1,
        'font-family' => 1,
        'font-size' => 1,
        'font-weight' => 1,
        'text-align' => 1,
        'text-decoration' => 1,
        'text-decoration-color' => 1,
        'text-decoration-style' => 1,
        'text-indent' => 1,
        'vertical-align' => 1,],
      'span' => [
        'color' => 1,
        'font-family' => 1,
        'font-size' => 1,
        'font-weight' => 1,
        'text-decoration' => 1,
        'text-decoration-color' => 1,
        'text-decoration-style' => 1,],];

    // Headers support everything from paragraphs, but also page breaks.
    $supported['h1'] = $supported['p'];
    $supported['h1']['page-break-before'] = 1;
    $supported['h1']['page-break-after'] = 1;

    // Inherit support from other elements.
    $supported['article'] = $supported['div'];
    $supported['h2'] = $supported['h1'];
    $supported['h3'] = $supported['h1'];
    $supported['h4'] = $supported['h1'];
    $supported['h5'] = $supported['h1'];
    $supported['h6'] = $supported['h1'];
    $supported['code'] = $supported['p'];
    $supported['li'] = $supported['p'];
    $supported['.book-title'] = $supported['p'];
    $supported['.header-left'] = $supported['p'];
    $supported['.header-right'] = $supported['p'];
    $supported['.footer-left'] = $supported['p'];
    $supported['.footer-right'] = $supported['p'];
    $supported['th'] = $supported['td'];
    $supported['del'] = $supported['span'];
    $supported['s'] = $supported['span'];
    $supported['ins'] = $supported['span'];
    $supported['u'] = $supported['span'];

    if (!array_key_exists($selector, $supported)) {
      return ["","",""];
    }

    foreach (array_keys($css) as $property) {
      if (!array_key_exists(trim($property), $supported[$selector])) {
        unset($css[$property]);
      }
    }

    if (!is_array($css)) {
      return ["", "", ""];
    }

    // RTF can hold style elements before, within and after blocks.
    $rtf_prefix = "";
    $rtf_infix = "";
    $rtf_suffix = "";

    // Use if statements rather than switch to group tags.
    if (array_key_exists('text-indent', $css)) {
      $rtf_infix .= "\\fi" . $this->bookexportrtf_convert_length($css['text-indent']);
    }
    if (array_key_exists('margin-top', $css)) {
      $rtf_infix .= "\\sb" . $this->bookexportrtf_convert_length($css['margin-top']);
    }
    if (array_key_exists('margin-right', $css)) {
      $rtf_infix .= "\\ri" . $this->bookexportrtf_convert_length($css['margin-right']);
    }
    if (array_key_exists("margin-bottom", $css)) {
      $rtf_infix .= "\\sa" . $this->bookexportrtf_convert_length($css['margin-bottom']);
    }
    if (array_key_exists('margin-left', $css)) {
      $rtf_infix .= "\\li" . $this->bookexportrtf_convert_length($css['margin-left']);
    }
    if (array_key_exists('text-align', $css)) {
      switch(trim($css['text-align'])) {
        case 'center':
          $rtf_infix .= "\\qc";
          break;

        case 'justify':
          $rtf_infix .= "\\qj";
          break;

        case 'left':
          $rtf_infix .= "\\ql";
          break;

        case 'right':
          $rtf_infix .= "\\qr";
      }
    }
    if (array_key_exists('font-family', $css)) {
      // In CSS a family of fonts is given, if the first is not available the
      // second is tried etc. RTF doesn't support this so pick the first.
      $r = explode(",", $css['font-family']);
      $font = trim($r[0]);
      $font = preg_replace("|\"|", "", $font);

      if (!array_key_exists($font, $this->bookexportrtf_fonttbl)) {
        $this->bookexportrtf_fonttbl[$font] = count($this->bookexportrtf_fonttbl);
      }
      if ($this->bookexportrtf_fonttbl[$font] != 0) {
         $rtf_infix .= "\\f" . $this->bookexportrtf_fonttbl[$font];
      }
    }
    if (array_key_exists('font-size', $css)) {
      $rtf_infix .= "\\fs". $this->bookexportrtf_convert_font_size($css['font-size']);
    }
    if (array_key_exists('font-weight', $css)) {
      switch(trim($css['font-weight'])) {
        case 'bold':
          $rtf_infix .= "\\b";
          break;

        case 'normal':
      }
    }
    if (array_key_exists('color', $css)) {
      $color = $this->bookexportrtf_convert_color($css['color']);
      if ($color != 0) {
        $rtf_infix .= "\\cf" . $color;
      }
    }
    if (array_key_exists('text-decoration', $css)) {
      // Multiple values are accepted.
      $values = explode(" ", trim($css['text-decoration']));
      foreach ($values as $v) {
        switch(trim($v)) {
          case 'line-through':
            $rtf_infix .= "\\strike";
            break;

          case 'underline':
            if (array_key_exists('text-decoration-style', $css)) {
              switch(trim($css['text-decoration-style'])) {
                case 'initial':
                case 'inherit':
                case 'solid':
                  $rtf_infix .= "\\ul";
                  break;

                case 'double':
                  $rtf_infix .= "\\uldb";
                  break;

                case 'dotted':
                  $rtf_infix .= "\\uld";
                  break;

                case 'dashed':
                  $rtf_infix .= "\\uldash";
                  break;

                case 'wavy':
                  $rtf_infix .= "\\ulwave";
                  break;

                default:
                  $rtf_infix .= "\\ul";
              }
            }
            else {
              $rtf_infix .= "\\ul";
            }
            break;

          case 'none':
            $rtf_infix .= "";
        }
      }
    }
    if (array_key_exists('text-decoration-color', $css)) {
      $rtf_infix .= "\\ulc" . $this->bookexportrtf_convert_color($css['text-decoration-color']);
    }

    // Page breaks.
    if (array_key_exists('page-break-before', $css)) {
      if ($selector == 'article') {
        // Articles come with a section break, this also supports avoid.
        switch (trim($css['page-break-before'])) {
          case "always":
            $rtf_infix .= "\\sbkpage";
            break;

          case "avoid":
            $rtf_infix .= "\\sbknone";
            break;

        }
      }
      else {
        if (trim($css['page-break-before']) == "always") {
          $rtf_prefix .= "\\page";
        }
      }
    }
    if (array_key_exists('page-break-after', $css)) {
      if (trim($css['page-break-after']) == "always") {
        $rtf_suffix .= "\\page";
      }
      if (trim($css['page-break-after']) == "avoid") {
        if ($selector != "div" & $selector != "article") {
          // Divs and article can't be attached to the next part.
          $rtf_infix .= "\\keepn";
        }
      }
    }

    // Tables
    foreach (["border-top", "border-right", "border-bottom", "border-left"] as $border) {
      if (array_key_exists($border . "-width", $css)) {
        $rtf_prefix .= "\\clbrdr" . substr($border, 7, 1);
        $rtf_prefix .= "\\brdrw" . $this->bookexportrtf_convert_length($css[$border . "-width"]);
        if (array_key_exists($border . "-style", $css)) {
          switch(trim($css[$border . "-style"])) {
            case "dotted":
              $rtf_prefix .= "\\brdrdot ";
              break;

            case "dashed":
              $rtf_prefix .= "\\brdrdash ";
              break;

            case "double":
              $rtf_prefix .= "\\brdrdb ";
              break;

            case "hidden":
            case "none":
              $rtf_prefix .= "\\brdrnone ";
              break;

            default:
              $rtf_prefix .= "\\brdrs ";
          }
        }
        else {
          $rtf_prefix .= "\\brdrs ";
        }
      }
    }
    if (array_key_exists("vertical-align", $css)) {
      switch (trim($css["vertical-align"])) {
      case "top":
        $rtf_prefix .= "\\clvertalt";
        break;

      case "middle":
        $rtf_prefix .= "\\clvertalc";
        break;

      case "bottom":
        $rtf_prefix .= "\\clvertalb";
      }
    }

    // Add a white space or newline to prevent mixup with text.
    if (strlen($rtf_prefix) > 0) {
      $rtf_prefix .= "\r\n";
    }
    if (strlen($rtf_infix) > 0) {
      $rtf_infix .= " ";
    }
    if (strlen($rtf_suffix) > 0) {
      $rtf_suffix .= "\r\n";
    }
    return [$rtf_prefix, $rtf_infix, $rtf_suffix];
  }

  /**
   * Convert CSS colors to a position in the colortable
   *
   * @param string $css
   *   The value in CSS, this is a string with the value and unit.
   *   Supported color schemes are: rgb, six character hex and CSS color names.
   *
   * @return string
   *   The position in the color table or the first item in the color table if
   *   the color could not be converted.
   */
  public function bookexportrtf_convert_color($css) {
    $color = "";
    $css = trim($css);
    if (preg_match("|^rgb\((\d+),(\d+),(\d+)\)$|", $css ,$r)) {
      $red = $r[1];
      $green = $r[2];
      $blue = $r[3];
      if ($red >=0 & $red <= 255 & $green >=0 & $green <= 255 & $blue >=0 & $blue <= 255) {
        $color = "\\red" . $red . "\\green" . $green . "\\blue" . $blue;
      }
    }
    if (preg_match("|^\#([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$|", $css, $r)) {
      $red = hexdec($r[1]);
      $green = hexdec($r[2]);
      $blue = hexdec($r[3]);
      if ($red >=0 & $red <= 255 & $green >=0 & $green <= 255 & $blue >=0 & $blue <= 255) {
        $color = "\\red" . $red . "\\green" . $green . "\\blue" . $blue;
      }
    }
    if (preg_match("|^\w+$|", $css)) {
      $css_color_names = [
        'aliceblue' => "\\red240\\green248\\blue255",
        'antiquewhite' => "\\red250\\green235\\blue215",
        'aqua' => "\\red0\\green255\\blue255",
        'aquamarine' => "\\red127\\green255\\blue212",
        'azure' => "\\red240\\green255\\blue255",
        'beige' => "\\red245\\green245\\blue220",
        'bisque' => "\\red255\\green228\\blue196",
        'black' => "\\red0\\green0\\blue0",
        'blanchedalmond' => "\\red255\\green235\\blue205",
        'blue' => "\\red0\\green0\\blue255",
        'blueviolet' => "\\red138\\green43\\blue226",
        'brown' => "\\red165\\green42\\blue42",
        'burlywood' => "\\red222\\green184\\blue135",
        'cadetblue' => "\\red95\\green158\\blue160",
        'chartreuse' => "\\red127\\green255\\blue0",
        'chocolate' => "\\red210\\green105\\blue30",
        'coral' => "\\red255\\green127\\blue80",
        'cornflowerblue' => "\\red100\\green149\\blue237",
        'cornsilk' => "\\red255\\green248\\blue220",
        'crimson' => "\\red220\\green20\\blue60",
        'cyan' => "\\red0\\green255\\blue255",
        'darkblue' => "\\red0\\green0\\blue139",
        'darkcyan' => "\\red0\\green139\\blue139",
        'darkgoldenrod' => "\\red184\\green134\\blue11",
        'darkgray' => "\\red169\\green169\\blue169",
        'darkgrey' => "\\red169\\green169\\blue169",
        'darkgreen' => "\\red0\\green100\\blue0",
        'darkkhaki' => "\\red189\\green183\\blue107",
        'darkmagenta' => "\\red139\\green0\\blue139",
        'darkolivegreen' => "\\red85\\green107\\blue47",
        'darkorange' => "\\red255\\green140\\blue0",
        'darkorchid' => "\\red153\\green50\\blue204",
        'darkred' => "\\red139\\green0\\blue0",
        'darksalmon' => "\\red233\\green150\\blue122",
        'darkseagreen' => "\\red143\\green188\\blue143",
        'darkslateblue' => "\\red72\\green61\\blue139",
        'darkslategray' => "\\red47\\green79\\blue79",
        'darkslategrey' => "\\red47\\green79\\blue79",
        'darkturquoise' => "\\red0\\green206\\blue209",
        'darkviolet' => "\\red148\\green0\\blue211",
        'deeppink' => "\\red255\\green20\\blue147",
        'deepskyblue' => "\\red0\\green191\\blue255",
        'dimgray' => "\\red105\\green105\\blue105",
        'dimgrey' => "\\red105\\green105\\blue105",
        'dodgerblue' => "\\red30\\green144\\blue255",
        'firebrick' => "\\red178\\green34\\blue34",
        'floralwhite' => "\\red255\\green250\\blue240",
        'forestgreen' => "\\red34\\green139\\blue34",
        'fuchsia' => "\\red255\\green0\\blue255",
        'gainsboro' => "\\red220\\green220\\blue220",
        'ghostwhite' => "\\red248\\green248\\blue255",
        'gold' => "\\red255\\green215\\blue0",
        'goldenrod' => "\\red218\\green165\\blue32",
        'gray' => "\\red128\\green128\\blue128",
        'grey' => "\\red128\\green128\\blue128",
        'green' => "\\red0\\green128\\blue0",
        'greenyellow' => "\\red173\\green255\\blue47",
        'honeydew' => "\\red240\\green255\\blue240",
        'hotpink' => "\\red255\\green105\\blue180",
        'indianred' => "\\red205\\green92\\blue92",
        'indigo' => "\\red75\\green0\\blue130",
        'ivory' => "\\red255\\green255\\blue240",
        'khaki' => "\\red240\\green230\\blue140",
        'lavender' => "\\red230\\green230\\blue250",
        'lavenderblush' => "\\red255\\green240\\blue245",
        'lawngreen' => "\\red124\\green252\\blue0",
        'lemonchiffon' => "\\red255\\green250\\blue205",
        'lightblue' => "\\red173\\green216\\blue230",
        'lightcoral' => "\\red240\\green128\\blue128",
        'lightcyan' => "\\red224\\green255\\blue255",
        'lightgoldenrodyellow' => "\\red250\\green250\\blue210",
        'lightgray' => "\\red211\\green211\\blue211",
        'lightgrey' => "\\red211\\green211\\blue211",
        'lightgreen' => "\\red144\\green238\\blue144",
        'lightpink' => "\\red255\\green182\\blue193",
        'lightsalmon' => "\\red255\\green160\\blue122",
        'lightseagreen' => "\\red32\\green178\\blue170",
        'lightskyblue' => "\\red135\\green206\\blue250",
        'lightslategray' => "\\red119\\green136\\blue153",
        'lightslategrey' => "\\red119\\green136\\blue153",
        'lightsteelblue' => "\\red176\\green196\\blue222",
        'lightyellow' => "\\red255\\green255\\blue224",
        'lime' => "\\red0\\green255\\blue0",
        'limegreen' => "\\red50\\green205\\blue50",
        'linen' => "\\red250\\green240\\blue230",
        'magenta' => "\\red255\\green0\\blue255",
        'maroon' => "\\red128\\green0\\blue0",
        'mediumaquamarine' => "\\red102\\green205\\blue170",
        'mediumblue' => "\\red0\\green0\\blue205",
        'mediumorchid' => "\\red186\\green85\\blue211",
        'mediumpurple' => "\\red147\\green112\\blue219",
        'mediumseagreen' => "\\red60\\green179\\blue113",
        'mediumslateblue' => "\\red123\\green104\\blue238",
        'mediumspringgreen' => "\\red0\\green250\\blue154",
        'mediumturquoise' => "\\red72\\green209\\blue204",
        'mediumvioletred' => "\\red199\\green21\\blue133",
        'midnightblue' => "\\red25\\green25\\blue112",
        'mintcream' => "\\red245\\green255\\blue250",
        'mistyrose' => "\\red255\\green228\\blue225",
        'moccasin' => "\\red255\\green228\\blue181",
        'navajowhite' => "\\red255\\green222\\blue173",
        'navy' => "\\red0\\green0\\blue128",
        'oldlace' => "\\red253\\green245\\blue230",
        'olive' => "\\red128\\green128\\blue0",
        'olivedrab' => "\\red107\\green142\\blue35",
        'orange' => "\\red255\\green165\\blue0",
        'orangered' => "\\red255\\green69\\blue0",
        'orchid' => "\\red218\\green112\\blue214",
        'palegoldenrod' => "\\red238\\green232\\blue170",
        'palegreen' => "\\red152\\green251\\blue152",
        'paleturquoise' => "\\red175\\green238\\blue238",
        'palevioletred' => "\\red219\\green112\\blue147",
        'papayawhip' => "\\red255\\green239\\blue213",
        'peachpuff' => "\\red255\\green218\\blue185",
        'peru' => "\\red205\\green133\\blue63",
        'pink' => "\\red255\\green192\\blue203",
        'plum' => "\\red221\\green160\\blue221",
        'powderblue' => "\\red176\\green224\\blue230",
        'purple' => "\\red128\\green0\\blue128",
        'rebeccapurple' => "\\red102\\green51\\blue153",
        'red' => "\\red255\\green0\\blue0",
        'rosybrown' => "\\red188\\green143\\blue143",
        'royalblue' => "\\red65\\green105\\blue225",
        'saddlebrown' => "\\red139\\green69\\blue19",
        'salmon' => "\\red250\\green128\\blue114",
        'sandybrown' => "\\red244\\green164\\blue96",
        'seagreen' => "\\red46\\green139\\blue87",
        'seashell' => "\\red255\\green245\\blue238",
        'sienna' => "\\red160\\green82\\blue45",
        'silver' => "\\red192\\green192\\blue192",
        'skyblue' => "\\red135\\green206\\blue235",
        'slateblue' => "\\red106\\green90\\blue205",
        'slategray' => "\\red112\\green128\\blue144",
        'slategrey' => "\\red112\\green128\\blue144",
        'snow' => "\\red255\\green250\\blue250",
        'springgreen' => "\\red0\\green255\\blue127",
        'steelblue' => "\\red70\\green130\\blue180",
        'tan' => "\\red210\\green180\\blue140",
        'teal' => "\\red0\\green128\\blue128",
        'thistle' => "\\red216\\green191\\blue216",
        'tomato' => "\\red255\\green99\\blue71",
        'turquoise' => "\\red64\\green224\\blue208",
        'violet' => "\\red238\\green130\\blue238",
        'wheat' => "\\red245\\green222\\blue179",
        'white' => "\\red255\\green255\\blue255",
        'whitesmoke' => "\\red245\\green245\\blue245",
        'yellow' => "\\red255\\green255\\blue0",
        'yellowgreen' => "\\red154\\green205\\blue50"];

      $css = strtolower($css);
      if (array_key_exists($css, $css_color_names)) {
        $color = $css_color_names[$css];
      }
    }

    // Return the default color if it could not be converted.
    if ($color == "") {
      return 0;
    }

    // Add the color to the color table if it isn't in there.
    if (!array_key_exists($color, $this->bookexportrtf_colortbl)) {
       $this->bookexportrtf_colortbl[$color] = count($this->bookexportrtf_colortbl)+1;
    }

    return $this->bookexportrtf_colortbl[$color];
  }

  /**
   * Convert CSS length to RTF
   *
   * @param string $css
   *   The value in CSS, this is a string with the value and unit.
   *   Supported units are cm, in, mm, pt, px, pc.
   *
   * @return string
   *   The length in twips or 0 if the length could not be converted.
   */
  public function bookexportrtf_convert_length($css) {
    preg_match("|^(-?\d+\.?\d*)([a-zA-Z]+)$|", trim($css), $r);
    if (count($r) == 0) {
      return 0;
    }
    $css_value = $r[1];
    $css_unit = $r[2];

    if ($css_unit == 'cm') {
      return round($css_value / 2.54 * 1440);
    }
    if ($css_unit == 'in') {
      return round($css_value * 1440);
    }
    if ($css_unit == 'mm') {
      return round($css_value / 2.54 * 144);
    }
    if ($css_unit == 'pt') {
      return round($css_value * 20);
    }
    if ($css_unit == 'px') {
      return round($css_value * 15);
    }
    if ($css_unit == 'pc') {
      return round($css_value * 240);
    }

    return 0;
  }

  /**
   * Converter CSS font size to RTF
   *
   * @param string $css
   *   The value in CSS, this is a string with the value and unit.
   *   Supported units are cm, in, mm, pt, px, pc.
   *
   * @return string
   *   The font size in half points or 24 if the font-size could not be
   *   converted.
   */

  public function bookexportrtf_convert_font_size($css) {
    preg_match("|^(\d+\.?\d*)([a-zA-Z]+)$|", trim($css), $r);
    if (count($r) == 0) {
      return 24;
    }
    $css_value = $r[1];
    $css_unit = $r[2];

    // font size
    if ($css_unit == 'cm') {
      return round($css_value / 2.54 * 144);
    }
    if ($css_unit == 'in') {
      return round($css_value * 144);
    }
    if ($css_unit == 'mm') {
      return  round($css_value / 2.54 * 14.4);
    }
    if ($css_unit == 'pt') {
      return round($css_value * 2);
    }
    if ($css_unit == 'px') {
      return round($css_value * 1.5);
    }
    if ($css_unit == 'pc') {
      return round($css_value * 24);
    }

    return 24;
  }
}
