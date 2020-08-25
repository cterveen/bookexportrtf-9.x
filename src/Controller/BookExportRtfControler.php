<?php

namespace Drupal\bookexportrtf\Controller;

use CssParser;
use DateTime;
use Drupal\book\BookExport;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Load Simple HTML DOM
 *
 * TODO: This library should probably be included using composer but it's too
 * much for now to get all that up and running so do it the old fashioned way.
 *
 * get it here: https://simplehtmldom.sourceforge.io/
 * save it to: sites/all/libraries/simle_html_dom/
 */

include_once('sites/all/libraries/simple_html_dom/simple_html_dom.php');

 /**
 * Load the css parser if not done yet
 *
 * TODO: This library should probably be included using composer but it's too
 * much for now to get all that up and running so do it the old fashioned way.
 *
 * get it here: https://github.com/Schepp/CSS-Parser
 * save it to: sites/all/libraries/schepp-css-parser/
 */ 

include_once('sites/all/libraries/schepp-css-parser/parser.php');


/**
 * Defines BookIndexController class.
 */
class BookExportRTFController extends ControllerBase {

  /**
   * The book export service.
   *
   * @var \Drupal\book\BookExport
   */
  protected $bookExport;
  
  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a BookIndexController object.
   *
   * @param \Drupal\book\BookExport $bookExport
   *   The book export service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(BookExport $bookExport, RendererInterface $renderer) {
    $this->bookExport = $bookExport;
    $this->renderer = $renderer;

    // variables to be used within the class
    global $base_url;
    $this->bookexportrtf_base_url = $base_url;
  }

   /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('book.export'),
      $container->get('renderer')
    );
  }

  /**
   * Generates an index of a book page and its children.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to export.
   *
   * @return array
   *   Return markup array.
   */

  public function content(NodeInterface $node) {
    if (!isset($node->book)) {
      return [
        '#type' => 'markup',
        '#markup' => $this->t("Not a book page, so nothing to export."),
      ];
    }
  
    // Get the node and subnodes in RTF format
    $rtf = $this->bookexportrtf_convert($node);

    /** 
      * TODO: this code creates a nice download but generates errors on the next visited page
      * header("Content-Type: application/rtf");
      * header("Content-Disposition: inline; filename=\"gele-boekje.rtf\"");
      * echo $rtf;
      */
    file_put_contents("sites/default/files/gele-boekje.rtf", $rtf);

    return [
      '#type' => 'markup',
      '#markup' => $this->t("<a href = '/~rork/drupal9/sites/default/files/gele-boekje.rtf'>download</a>"),
    ];
  }

  /**
   * Converts the book and it's subpages to RTF.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to export.
   *
   * @return variable
   *   Return the book in RTF format
   */

  private function bookexportrtf_convert(NodeInterface $node) {
    // Grab the contents of the book in HTML form
    $exported_book = $this->bookExport->bookExportHtml($node);
    $content = new Response($this->renderer->renderRoot($exported_book));

    /**
     * Prepare the HTML for processing  
     */

    // Remove everything before and after the HTML tags
    $content = preg_replace("|^[^<]+|", "", $content);
    $content = preg_replace("|>[^>]+$|", ">", $content);

    // Remove newlines
    $content = preg_replace("|[\r\n]|", "", $content);

    // Remove white-space between structural elements
    foreach (['td', 'p', 'li', 'div', 'h1', 'h2', 'h3', 'ol', 'ul', 'body', 'head', 'html'] as $element) {
      $content = preg_replace("|<\/".$element.">\s+<|", "</".$element."><", $content);
      $content = preg_replace("|>\s+<".$element."|", "><".$element, $content);  
    }
    $content = preg_replace("|-->\s+<|", "--><", $content);
    $content = preg_replace("|>\s+<!--|", "><!--", $content);
    // Remove white-space after the body
    $content = preg_replace("|<body>\s+|", "<body>", $content);

    /**
     * Get the style sheet(s), setup the font and color tables
     */

    $css = ["main" => []];  
    foreach (["sites/all/modules/bookexportrtf/css/bookexportrtf.rtf.css"] as $css_file) {
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
      }  
    }

    // set the default font
    if (array_key_exists("body", $this->bookexportrtf_css)) {
      if (array_key_exists("font-family", $this->bookexportrtf_css["body"])) {
        preg_match("|^([^,]+),?|", $this->bookexportrtf_css["body"]["font-family"], $r);
        $font = trim($r[1]);
        $font = preg_replace("|\"|", "", $font);
        $this->bookexportrtf_fonttbl[$font] = 0;
      }
    }

    // declare the colortable but no need to set a default
    $this->bookexportrtf_colortbl = [];

    /**
     * Get the html object
     */

    $html = str_get_html($content);

    /**
     * Set some of the books attributes
     */
    $toc = $html->find("h1");

    // The title of the book
    $this->bookexportrtf_book_title = $toc[0]->innertext;

    /**
    * Start with the footer. This is done to grab the index tables.
    * Footer
    * - index
    */

    $footer = "";

    // are there any index terms?
    $anchors = $html->find('a[name]');
    $terms = [];
    foreach ($anchors as $a) {
      $label = $a->name;
      if (preg_match("|^index|", $label)) {
        $label = substr($label, 5);
        array_push($terms, $label);
      }
    }
    sort($terms);

    if (count($terms) > 0) {
      // setup the section

      $style = $this->bookexportrtf_get_rtf_style_from_element($toc[0]);

      $footer .= $style[0];
      $footer .= "{\\headerl\\pard\\ql {\b ".$this->bookexportrtf_book_title."}\\par}\r\n";
      $footer .= "{\\headerr\\pard\\qr Index\\par}\r\n";
      $footer .= "{\\footerr\\pard\\qr \\chpgn \\par}\r\n";
      $footer .= "{\\footerl\\pard\\ql \\chpgn \\par}\r\n";
      $footer .= "{\\pard " . $style[1];
      $footer .= "{\\*\\bkmkstart chapterIndex}{\\*\\bkmkend chapterIndex}Index";
      $footer .= "\\par}\r\n";
      $footer .= "\\sect \\sbknone \\cols2\r\n";

      $cur_initial = "";

      $this->bookexpor_rtf_index_id = [];
      $i = 0;

      foreach ($terms as $t) {
        if (!isset($this->bookexpor_rtf_index_id[$t])) {
          $this->bookexpor_rtf_index_id[$t] = $i;
          $anchor = "index-" . $i ; 
          $i++;
  
          $initial = substr($t, 0, 1);
          if (is_numeric($initial)) {
            $initial = "#";
          }
          if ($initial != $cur_initial) {
            if ($cur_initial != "") {
              $footer .= "\\par}\r\n";
            }
            $footer .= "{\\pard\\fs28{\\b " . $initial . "\\b}\\par}\r\n";
            $footer .= "{\\pard\\ql ";
            $cur_initial = $initial;
          }
          $footer .= $t . " {\\field{\*\\fldinst PAGEREF ".$anchor."}}\\line\r\n";
        }
      }
      $footer .= "\\par}\r\n";
      $footer .= $style[2];
    }

    /** 
    * Then get the content.
    * This is done second because the font table might be appended during the
    * conversion process.
    *
    * The tough work is going to be done by bookexportrtf_traverse.
    */

    $elements = $html->find('html');
    $this->bookexportrtf_traverse($elements);

    // dumb the new code back to $content
    $content = $html;  

    // strip all remaining tags
    $content = strip_tags($content);

    /**
     * HEADER
     * - RTF header
     * - Front page
     * - Flyleaf containing URL and date of download
     * - Table of contents
     * - Start of first page
     */

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

    $header .= "\\vertdoc\\paperh16834\\paperw11909\r\n";
    $header .= "\\fet0\\facingp\\ftnbj\\ftnrstpg\\widowctrl\r\n";
    $header .= "\\plain\r\n";

    // front page
    if (1) {
      $bookrtf_front_page = ['value' => '<h3>'.$this->bookexportrtf_book_title.'</h3>', 'format' => 'full_html'];
      $bookrtf_front_page["value"] = "<html><body><h3>" . $bookrtf_front_page["value"] . "</body></html>";
      $title_html = str_get_html($bookrtf_front_page["value"]);
      $elements = $title_html->find('html');
      $this->bookexportrtf_traverse($elements);
      $title_html = strip_tags($title_html);
      $header .= $title_html;
    }
    // flyleaf
    if (1) {
      $header .= "\\sect\\sftnrstpg\r\n";
      $header .= "{\\pard\\qc";
      $header .= "{\\b " . $this->bookexportrtf_book_title . "}\\line\r\n";
      if (isset($this->bookexportrtf_base_url)) {
        $header .= $this->bookexportrtf_base_url;
      }
      $header .= "\\line\r\n\\line\r\n";
      $date = new DateTime();
      $header .= "Gegenereerd: " . date_format($date, "d-m-Y") . " \\par}\r\n";
    }

    // table of contents
    if (1) {
      $header .= "\\sect\\sftnrstpg\r\n";

      $header .= "{\\pard ";
      $style = $this->bookexportrtf_get_rtf_style_from_element($toc[0]);
      $header .=  $style[1];
      $header .= "Inhoud\r\n";
      $header .= "\\par}\r\n{\\pard {";

      // remove the first title as this should be the title of the book and
      // does not belong in the toc.
      $book_title_element = array_shift($toc);
      $book_title_element->outertext = "";
      foreach ($toc as $e) {
        // Assume title starts with the chapternumber
        $title = $e->innertext;
        preg_match("|^(\d+)\.\s|", $title, $match);
        $chapter = $match[1];

        $header .= "\\trowd";
        $header .= "\\cellx7000 \\cellx8309\r\n"; 
        $header .= "\\intbl\\pard " . $title . "\\cell";
        $header .= "\\qr{\\field{\*\\fldinst PAGEREF chapter";
        $header .= $chapter;
        $header .= "}}\\cell\\row\r\n";
      }

      $header .= "\\trowd";
      $header .= "\\cellx7000 \\cellx8309\r\n"; 
      $header .= "\\intbl\\pard Index\\cell";
      $header .= "\\qr{\\field{\*\\fldinst PAGEREF chapterIndex}}\\cell\\row\r\n";
      $header .= "}\\par}\r\n";
    }

    /**
     * Make the final document
     */
    $content = wordwrap("{" . $header . $content . $footer . "}", 80, "\r\n", TRUE);

    /**
     * Encode special characters as RTF
     */

    // extended ascii
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

    // html
    $content = preg_replace("|&amp;|", "&", $content);
    $content = preg_replace("|&deg;|", "\'b0", $content);
    $content = preg_replace("|&gt;|", ">", $content);
    $content = preg_replace("|&lt;|", "<", $content);
    $content = preg_replace("|&nbsp;|", " ", $content);
    $content = preg_replace("|&#039;|", "'", $content);

    // non breaking space
    $content = preg_replace("|\x{C2}\x{A0}|", " ", $content);

    return $content;
  }

  /**
   * HTML parsers may not spawn demons but if you use them to replace HTML tags
   * by RTF code they do attract gremlins as the parser gets in trouble with
   * nested tags (which occur a lot in HTML). Probably the parser is losing
   * the structure. This is solved by going through the tree and start
   * replacing tags at the branches working up to the main stem.
   *
   * @param elements 
   *   the basic $elements from which to start
   */
 
  private function bookexportrtf_traverse($elements) {
    foreach ($elements as $e) {
      if ($e->first_child()) {
        $children = $e->children();
        $this->bookexportrtf_traverse($children);
      }

      // no children anymore --> start changing tags
      $tag = $e->tag;

      switch($tag) {
        case 'a':
          // this could be either links or anchors
          if ($e->href) {
            // link --> replace with footnote
            $url = $e->href;
            $title = $e->innertext;

            // no use to add a footnote if the link and label are the same.
            if (preg_match("|^(https?://)?(mailto:)?" . $title . "/?$|", $url)) {
              $e->outertext = $title;
            }
            else {
              $e->outertext = $title . "{\\footnote \\pard {\\up6 \\chftn} " . $url . "}";
            }
          }
          else if ($e->name) {
            // replace anchors for the index, ignore others
            if (preg_match("|^index|", $e->name)) {
              $label = substr($e->name, 5);
              $anchor = "index-" . $this->bookexpor_rtf_index_id[$label]; 
              $e->outertext = "{\\*\\bkmkstart " . $anchor . "}{\\*\\bkmkend ".$anchor."}";
            }
          }
          break;

        case 'br':
          // add a tab before the newline otherwise justified last lines will
          // be justified while I want them left aligned.
          $e->outertext = "\\tab\\line\r\n";
          break;

       case 'div':
         // for div's I'm only interested in page-break-before and
         // page-break-after other style elements will be enherited by its
         // children

         $style = $this->bookexportrtf_get_rtf_style_from_element($e);
         $e->outertext = $style[0] . $e->innertext . $style[2];
          break;

        case 'h1':
          // start of a new chapter --> new page, right header contains chapter
          // title, bookmark for the table of contents
          $title = $e->innertext;
          $style = $this->bookexportrtf_get_rtf_style_from_element($e);
          $rtf = $style[0];

          $header_style = $this->bookexportrtf_get_rtf_style_from_selector(".header-left");
          $rtf .= "{\\headerl\\pard ". $header_style[1] . $this->bookexportrtf_book_title . "\\par}\r\n";
          $header_style = $this->bookexportrtf_get_rtf_style_from_selector(".header-right");
          $rtf .= "{\\headerr\\pard ". $header_style[1] . $title . "\\par}\r\n";
          $footer_style = $this->bookexportrtf_get_rtf_style_from_selector(".footer-left");
          $rtf .= "{\\footerl\\pard ". $footer_style[1] . "\\chpgn \\par}\r\n";
          $footer_style = $this->bookexportrtf_get_rtf_style_from_selector(".footer-right");
          $rtf .= "{\\footerr\\pard ". $footer_style[1] . "\\chpgn \\par}\r\n";

          // if the chapter starts with a number it should be in the index, add a bookmark for it
          if (preg_match("|^(\d+)\.\s|", $title, $match)) {
            $chapter = $match[1];
            $rtf .= "{\\*\\bkmkstart chapter".$chapter."}{\\*\\bkmkend chapter".$chapter."}\r\n";
          }
          $rtf .= "{\\pard\\keepn " . $style[1] . $title . "\\par}\r\n" . $style[2];

          $e->outertext = $rtf;
          break;

        case 'h2':
        case 'h3':
        case 'h4':
        case 'h5':
        case 'h6':
          $style = $this->bookexportrtf_get_rtf_style_from_element($e);
          $e->outertext = $style[0] . "{\\pard\\keepn " . $style[1] . $e->innertext . "\\par}\r\n" . $style[2];
          break;

        case 'head':
          $e->outertext = "";
          break;

        case 'i': 
          $e->outertext = "{\\i " . $e->innertext . "}";
          break;

        case 'img':
          $url = $e->src;

          // assume relative url
          if (isset($this->bookexportrtf_base_url) & substr($url, 0, 4) != "http") {
            $url = $this->bookexportrtf_base_url . $url;
          }

          $string = file_get_contents($url);

          $img = imagecreatefromstring($string);

          $width = imagesx($img);
          $height = imagesy($img);
          $ratio = $width/$height;

          // asume full page width A4 - margins = 11909 - 2x1800  = 8309 twips
          // TODO, add some scaleability here
          $picwidth = 8309;
          $picheight = round($picwidth / $ratio);
          $scalex = 100;
          $scaley = 100;

          $rtf = "{";
          $rtf .= "\\pard{\\pict\\picw" . $width;
          $rtf .= "\\pich" . $height;
          $rtf .= "\\picwgoal" . $picwidth;
          $rtf .= "\\pichgoal" . $picheight;
          $rtf .= "\\picscalex" . $scalex;
          $rtf .= "\\picscaley" . $scaley;

          // set image type
          if (substr($url, -4) == ".png") {
            $rtf .= "\pngblip\r\n";
          }
          else if (substr($url, -4) == ".jpg" or substr($url, -5) == ".jpeg") {
            $rtf .= "\jpegblip\r\n";
          }

          $hex = bin2hex($string);
          $hex = wordwrap($hex, 80, "\r\n", TRUE);

          $rtf .= $hex;
          $rtf .= "\r\n}\\par}\r\n";

          $e->outertext = $rtf;
          break;

        case 'li':
          /**
           * This might be a bit dirty but as I'm not going to make elaborate
           * list structures I feel confident working from li backwards and
           * strip out the list-tags later.
           */

          $depth = 0;
          $type = "ul";
          $number = 1;
          $last = 1;

          // Type, level
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
          // Item number
          $s = $e->prev_sibling();
          while($s) {
            if ($s->tag == "li") {
              $number++;
            }
            $s = $s->prev_sibling();
          }
          // Last item?
          $s = $e->next_sibling();
          while($s) {
            if ($s->tag == "li") {
              $last = 0;
              break;
            }
            $s = $s->next_sibling();
          }

          $rtf = "";
          // if the first item of a nested list close the current paragraph.
          // TODO: might want to check if the list is inside a paragraph and do
          // that anyway
          if ($depth > 1 & $number == 1) {
            $rtf .= "\\par}\r\n";
          }
          
          $style = $this->bookexportrtf_get_rtf_style_from_element($e);
          $rtf .= "{\\pard " . $style[1];

          $firstindent = -360;
          $lineindent = 720 * $depth;

          $rtf .= "\\fi" . $firstindent . "\\li". $lineindent;
          if ($type == "ul") {
            $rtf .= "\\bullet\\tab ";
          }
          else {
            $rtf .= " " . $array['id'] . ".\\tab ";
          }
          $rtf .= $e->innertext;

          // finish the paragraph unless it's the last item in a nested list
          // TODO this will give trouble if there's tekst after the nested list.
          // This text will be included in the last item of the nested list at
          // the moment.
          if ($last != 1 | $depth == 1) {
            $rtf .= "\\par}\r\n";
          }
          if ($depth == 1 & $last == 1) {
            // add some empty space after the list
            // TODO this creates an empty line, would be nicer if I could make
            // a configurable height.
            $rtf .= "{\\pard\\sa0\\par}\r\n"; 
          }
          $e->outertext = $rtf;
          break;

        case 'p':
          $style = $this->bookexportrtf_get_rtf_style_from_element($e);
          $e->outertext = "{\\pard " . $style[1] . $e->innertext . "\\par}\r\n";
          break;

          case 's':
          case 'del':
          case 'ins':
          case 'span':
            // remove the author information
            $class = $e->class;
            if ($class == "field field--name-title field--type-string field--label-hidden") {
              // label
              $e->outertext = "";
            }
            elseif ($class == "field field--name-uid field--type-entity-reference field--label-hidden") {
              // author
              $e->outertext = "";
            }
            elseif ($class == "field field--name-created field--type-created field--label-hidden") {
              // publication date
              $e->outertext = "";
            }
            else {
              $style = $this->bookexportrtf_get_rtf_style_from_element($e);
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
          /**
           * Tables are a little bit more complicated than lists. I do not feel
           * confident working backwards from cells. Better to store the whole
           * table and than put it down again.
           */

          $num_rows = 0;
          $num_cols = 0;
          $table;
          $colwidth = [];

          // retrieve table contents and some required specifications
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
              $table[$num_rows][$cur_cols]['style'] = $css;

              if (array_key_exists("width", $css)) {
                $colwidth[$cur_cols] = $this->bookexportrtf_convert_length($css["width"]);
              }

              // correct cur_cols for colspan.
              $cur_cols += $table[$num_rows][$cur_cols]['colspan']-1;
            }
            if ($cur_cols > $num_cols) {
              $num_cols = $cur_cols;
            }
          }

          /**
           * Calculate column width
           * 1. Determined width already defined
           * 2. Space out evenly over the remaining columns
           */

          $colright = [];
          $widthdefined = 0;
          $auto = 0;
          for ($col = 1; $col <= $num_cols; $col++) {
            if (array_key_exists($col, $colwidth)) {
              $widthdefined += $colwidth[$col];
            }
            else {
              $auto++;
            }
          }

          // standard pagewidth = 13909 - 2x1800 = 9309
          $autowidth = (9309 - $widthdefined)/$auto;

          $colleft = 0;
          for ($col = 1; $col <= $num_cols; $col++) {
            if (array_key_exists($col, $colwidth)) {
              $colleft += $colwidth[$col];
            }
            else {
              $colleft += $autowidth;
            }
            $colright[$col] = ceil($colleft);
          }

          // now we have the info start building it up again
          $rtf = "{";
          foreach ($table as $row) {
            $rtf .= "\\trowd\r\n";

            foreach ($row as $cell) {
              /**
               *TODO: why am I doing this here and not in css2rtf?
               * Answer: because that function is there to style paragraphs
               * not table cells.
               */

              // add the borders
              foreach (["border-top", "border-right", "border-bottom", "border-left"] as $border) {

                if (array_key_exists($border . "-width", $cell["style"])) {
                  $rtf .= "\\clbrdr" . substr($border, 7, 1);
                  $rtf .= "\\brdrw" . $this->bookexportrtf_convert_length($cell["style"][$border . "-width"]);
                  if (array_key_exists($border . "-style", $cell["style"])) {
                    switch(trim($cell["style"][$border . "-style"])) {
                      case "dotted":
                        $rtf .= "\\brdrdot ";
                        break;

                      case "dashed":
                        $rtf .= "\\brdrdash ";
                        break;

                      case "double":
                        $rtf .= "\\brdrdb ";
                        break;

                      case "hidden":
                      case "none":
                        $rtf .= "\\brdrnone ";
                        break;

                      default:
                        $rtf .= "\\brdrs ";
                        break;
                    }
                  }
                  else {
                    $rtf .= "\\brdrs ";
                  }
                }
              }

              if (array_key_exists("vertical-align", $cell["style"])) {
                switch (trim($cell["style"]["vertical-align"])) {
                  case "top":
                    $rtf .= "\\clvertalt";
                    break;
                  case "middle":
                    $rtf .= "\\clvertalc";
                    break;
                  case "bottom":
                    $rtf .= "\\clvertalb";
                    break;
                }
              }

              $rtf .= "\\cellx";
              $rtf .= $colright[$cell['col']+$cell['colspan']-1];
              $rtf .= "\r\n";
            }
            foreach ($row as $cell) {
              $style = $this->bookexportrtf_css2rtf($cell["style"]);
              $rtf .= "\\intbl{" . $style[1] . $cell['innertext'] . "}\\cell\r\n";
            }
            $rtf .= "\\row\r\n";
          }
          $rtf .= "}\r\n{\\pard\\sa0\\par}\r\n";

          $e->outertext = $rtf;
          break;

        case 'u':
          // switch to css to get the correct style (and color?);
          $css = $this->bookexportrtf_get_css_style_from_element($e);
          if (!array_key_exists('text-decoration', $css)) {
            $css['text-decoration'] = "underline";
          }
          else {
            if (!preg_match("|underline|", $css['text-decoration'])) {
              $css['text-decoration'] = $css['text-decoration'] . " underline";
            }
          }
          $style = $this->bookexportrtf_css2rtf($css);
          $e->outertext = "{" . $style[1] . $e->innertext . "}";
          break;
      }
    }
  }

  /**
   * Get the style for an HTML element
   *
   * @param e 
   *   an element from the html table
   *
   * @return array
   *   an array containing all relevant css properties
   */

  private function bookexportrtf_get_css_style_from_element($e) {
    // a list of inhereted css properties
    // most of these aren't used but keep them in
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

    $level = 0;

    // start the cascade
    while ($e) {
      // get css from the element attribute
      $style = $e->style;
      if ($style != '') {
        $style = ".attribute {" . $style . " }";
        $css_parser = new CssParser();
        $css_parser->load_string($style);
        $css_parser->parse();
        $my_css = $css_parser->parsed;

        foreach (array_keys($my_css['main']['.attribute']) as $property) {
          // inheritance by default
          if (!array_key_exists($property, $css) & ($level == 0 | array_key_exists($property, $css_inherit))) {
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

      # get css from the element id
      $id = '#' . $e->id;
      if (array_key_exists($id, $this->bookexportrtf_css)) {
        foreach (array_keys($this->bookexportrtf_css[$id]) as $property) {
          // inheritance by default
          if (!array_key_exists($property, $css) & ($level == 0 | array_key_exists($property, $css_inherit))) {
            $css[$property] = $this->bookexportrtf_css[$id][$property];
          }
          // inheritance by setting
          if (array_key_exists($property, $css)) {
            if (trim($css[$property]) == 'inherit') {
              $css[$property] = $this->bookexportrtf_css[$id][$property];
            }
          }
        }
      }

      # get css from the element class
      foreach (explode(' ', $e->class) as $class) {
        $class = "." . $class;
        if (array_key_exists($class, $this->bookexportrtf_css)) {
          foreach (array_keys($this->bookexportrtf_css[$class]) as $property) {
            // inheritance by default
            if (!array_key_exists($property, $css) & ($level == 0 | array_key_exists($property, $css_inherit))) {
              $css[$property] = $this->bookexportrtf_css[$class][$property];
            }
            // inheritance by setting
            if (array_key_exists($property, $css)) {
              if (trim($css[$property]) == 'inherit') {
                $css[$property] = $this->bookexportrtf_css[$class][$property];
              }
            }
          }
        }
      }

      # get css associated with the element
      $tag = $e->tag;
      if (array_key_exists($tag, $this->bookexportrtf_css)) {
        foreach (array_keys($this->bookexportrtf_css[$tag]) as $property) {
          // inheritance by default
          if (!array_key_exists($property, $css) & ($level == 0 | array_key_exists($property, $css_inherit))) {
            $css[$property] = $this->bookexportrtf_css[$tag][$property];
          }
          // inheritance by setting
          if (array_key_exists($property, $css)) {
            if (trim($css[$property]) == 'inherit') {
              $css[$property] = $this->bookexportrtf_css[$tag][$property];
            }
          }
        }
      }  
      $e = $e->parent();
      $level++;
    }

    return $css;
  }


  /**
   * Retrieve the RTF markup from an HTML element
   *
   * @param e 
   *   a HTML element
   *
   * @return array
   *   an array with the prefix, internal and suffix RTF markup
   */

  private function bookexportrtf_get_rtf_style_from_element($e) {
    return $this->bookexportrtf_css2rtf($this->bookexportrtf_get_css_style_from_element($e));
  }
  
  /**
   * Retrieve the RTF markup from an CSS selector
   *
   * @param selector 
   *   a CSS selector
   *
   * @return array
   *   an array with the prefix, internal and suffix RTF markup
   */

  private function bookexportrtf_get_rtf_style_from_selector($selector) {
    return $this->bookexportrtf_css2rtf($this->bookexportrtf_css[$selector]);
  }
  
  /**
   * Convert a CSS-array into the appropriate rtf style elements
   *
   * @param css 
   *   an array of css key-value pairs
   *
   * @return array
   *   an array with the prefix, internal and suffix RTF markup
   */

  private function bookexportrtf_css2rtf($css) {
    if (!is_array($css)) {
      return "";
    }

    // RTF can hold style elements before, within and after the blocks
    $rtf_prefix = "";
    $rtf_infix = "";
    $rtf_suffix = "";
  
    // use a bunch of if statements rather than switch to group tags
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
      // default is left so skip that
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
          break;

      }
    }
    if (array_key_exists('font-family', $css)) {
      // In css a family of fonts is given, if the first is not available the 
      // second is tried etc. RTF doesn't seem to support this so pick the first
      $r = [];
      preg_match("|^([^,]+),?|", $css['font-family'], $r);
      $font = trim($r[1]);
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
          break;
      }
    }
    if (array_key_exists('color', $css)) {
      $color = $this->bookexportrtf_convert_color($css['color']);
      if ($color != 0) {
        $rtf_infix .= "\\cf" . $color;
      }
    }
    if (array_key_exists('text-decoration', $css)) {
      // multiple values are accepted
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
                  break;
              }
            }
            else {
              $rtf_infix .= "\\ul";
            }
            break;

          case 'none':
            $rtf_infix .= "";
            break;
        }
      }
    }
    if (array_key_exists('text-decoration-color', $css)) {
      $rtf_infix .= "\\ulc" . $this->bookexportrtf_convert_color($css['text-decoration-color']);
    }
    if (array_key_exists('page-break-before', $css)) {
      if (trim($css['page-break-before']) == "always") {
          $rtf_prefix .= "\\sect\\sftnrstpg";
       }
    }
    if (array_key_exists('page-break-after', $css)) {
      if (trim($css['page-break-after']) == "always") {
          $rtf_suffix .= "\\sect\\sftnrstpg";
       }
    }

    // add a white space or newlines to prevent mixup with text
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
   * @param css 
   *   The value in CSS, this is a string with the value and unit
   *
   * @return string
   *   a string containing the RTF code of the provided color or the default
   *   color on error
   */

  private function bookexportrtf_convert_color($css) {
    $color = "";
    if (preg_match("|^rgb\((\d+),(\d+),(\d+)\)$|", trim($css) ,$r)) {
      $red = $r[1];
      $green = $r[2];
      $blue = $r[3];
      if ($red >=0 & $red <= 255 & $green >=0 & $green <= 255 & $blue >=0 & $blue <= 255) {
        $color = "\\red" . $red . "\\green" . $green . "\\blue" . $blue;
      }
    }
    if (preg_match("|^\#([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$|", trim($css), $r)) {
      $red = hexdec($r[1]);
      $green = hexdec($r[2]);
      $blue = hexdec($r[3]);
      if ($red >=0 & $red <= 255 & $green >=0 & $green <= 255 & $blue >=0 & $blue <= 255) {
        $color = "\\red" . $red . "\\green" . $green . "\\blue" . $blue;
      }
    }
    if (preg_match("|^\w+$|", trim($css))) {
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

      $css = strtolower(trim($css));
      if (array_key_exists($css, $css_color_names)) {
        $color = $css_color_names[$css];
      }
    } 

    if ($color == "") {
      return 0;
    }

    if (!array_key_exists($color, $this->bookexportrtf_colortbl)) {
       $this->bookexportrtf_colortbl[$color] = count($this->bookexportrtf_colortbl)+1;
    }
    return $this->bookexportrtf_colortbl[$color];  
  }

  /**
   * Convert CSS length to RTF's twips
   *
   * @param css 
   *   The value in CSS, this is a string with the value and unit
   */

  private function bookexportrtf_convert_length($css) {
    // check if css has the right format
    preg_match("|^(\d+\.?\d*)([a-zA-Z]+)$|", trim($css), $r);
    if (count($r) == 0) {
      return 0;
    }
    $css_value = $r[1];
    $css_unit = $r[2];

    // length
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
   * Converter from CSS font size to RTF's half points
   *
   * @param css 
   *   The value in CSS, this is a string with the value and unit
   */

  private function bookexportrtf_convert_font_size($css) {
    // check if css has the right format
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
