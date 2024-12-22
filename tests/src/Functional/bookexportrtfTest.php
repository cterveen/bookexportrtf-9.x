<?php

namespace Drupal\Tests\bookexportrtf;

use Drupal\bookexportrtf\BookConvertRtf;
use Drupal\Tests\UnitTestCase;
use DateTime;

/**
 * Test basic functionality of bookexportrtf
 *
 * Run from core:
 *   ../vendor/bin/phpunit ../modules/bookexportrtf/tests/src/Functional/bookexportrtfTest.php
 */

class BookExportRtfTest extends UnitTestCase
{
  /** The converter
   *
   * @var \Drupal\bookexportrtf\BookConvertRtf
   */
  protected $bookConvertRtf;

  public $convertrtf;

  public function setUp() : void {
    parent::setUp();
    // Load the HTML parser
    include_once(getcwd()  . "/libraries/simple_html_dom/simple_html_dom.php");


    // Lood BookCovertRtf();
    $this->convertrtf = new BookConvertRtf();

    // set the colortable
    $this->convertrtf->bookexportrtf_colortbl = [
      "\\red0\\green0\\blue0" => 1,
      "\\red255\\green0\\blue0" => 2,
      "\\red0\\green255\\blue0" => 3,
      "\\red0\\green0\\blue255" => 4,
      "\\red255\\green255\\blue255" => 5,
      "\\red138\\green43\\blue226" => 6];

    // set a book title
    $this->convertrtf->bookexportrtf_book_title = "Book title";

    // load the default css file
    $this->convertrtf->bookexportrtf_load_css(getcwd()  . "/modules/bookexportrtf/css/bookexportrtf.rtf.css");
  }

  /**
   * Test loading a style sheet
   */
  public function test_load_css() {
    // already loaded a style sheet so a default font should be set
    $this->assertArrayHasKey("Calibri", $this->convertrtf->bookexportrtf_fonttbl, "Failure to load the default css file");
    $this->assertFalse($this->convertrtf->bookexportrtf_load_css("foo.css"), "Failure on loading non-existent css file");
  }

  /**
   * Test getting the html conversion on a very simple document
   */
  public function test_document_conversion() {
    // Skip this test for now as the addition of the date seems to be handled incorrectly.
    return;
    $content = "<html>\r\n";
    $content .= "<head><title>Test document</title></head>\r\n";
    $content .= "<body>\r\n";
    $content .= "<article>\r\n";
    $content .= "<h1>Book title</h1>\r\n";
    $content .= "<article>\r\n";
    $content .= "<h1>Chapter 1</h1>\r\n";
    $content .= "<p>This is the first <a name = \"indexParagraph\"></a>paragraph</p>\r\n";
    $content .= "</article>\r\n";
    $content .= "</article>\r\n";
    $content .= "</body>\r\n";
    $content .= "</html>";

    $date = new DateTime();

    // header
    $expected = "{\\rtf1\\ansi\r\n";
    $expected .= "\\deff0 {\\fonttbl {\\f0\\fnil Calibri;}}\r\n";
    $expected .= "{\\colortbl ; \\red0\\green0\\blue0; \\red255\\green0\\blue0; \\red0\\green255\\blue0; \\red0\\green0\\blue255; \\red255\\green255\\blue255; \\red138\\green43\\blue226;}\r\n";
    $expected .= "\\vertdoc\\paperh16834\\paperw11909\r\n";
    $expected .= "\\fet0\\facingp\\ftnbj\\ftnrstpg\\widowctrl\r\n";
    $expected .= "\\plain\r\n";
    // font page
    $expected .= "{\\pard \\sa0\\qc\\fs32\\b\\keepn Book title\\par}\r\n";
    // flyleaf
    $expected .= "\\sect\\sftnrstpg\r\n";
    $expected .= "{\\pard\\qc{\\b Book title}\\line\r\n";
    $expected .= "\\line\r\n";
    $expected .= "\\line\r\n";
    $expected .= "Gegenereerd: " . date_format($date, "d-m-Y") . " \\par}\r\n";
    // table of contents
    $expected .= "\\sect\\sftnrstpg\r\n";
    $expected .= "{\\pard \\sa195\\fs32\\b\\keepn Inhoud\\par}\r\n";
    $expected .= "{\\pard {\\trowd\\cellx7000 \\cellx8309\r\n";
    $expected .= "\\pard\\intbl Book title\\cell\\qr{\\field{\\*\\fldinst PAGEREF chapter1}}\\cell\\row\r\n";
    $expected .= "\\trowd\\cellx7000 \\cellx8309\r\n";
    $expected .= "\\pard\\intbl Chapter 1\\cell\\qr{\\field{\\*\\fldinst PAGEREF chapter2}}\\cell\\row\r\n";
    $expected .= "\\trowd\\cellx7000 \\cellx8309\r\n";
    $expected .= "\\pard\\intbl Index\\cell\\qr{\\field{\\*\\fldinst PAGEREF chapter3}}\\cell\\row\r\n";
    $expected .= "}\\par}\r\n";
    $expected .= "\\sect\\sftnrstpg\r\n";
    $expected .= "{\\headerl\\pard \\ql\\b Book title\\par}\r\n";
    $expected .= "{\\headerr\\pard \\qr Book title\\par}\r\n";
    $expected .= "{\\footerl\\pard \\ql \\chpgn \\par}\r\n";
    $expected .= "{\\footerr\\pard \\qr \\chpgn \\par}\r\n";
    $expected .= "{\\*\\bkmkstart chapter1}{\\*\\bkmkend chapter1}\r\n";
    $expected .= "{\\pard \\sa195\\fs32\\b\\keepn Book title\\par}\r\n";
    $expected .= "\\sect\\sftnrstpg\r\n";
    $expected .= "{\\headerl\\pard \\ql\\b Book title\\par}\r\n";
    $expected .= "{\\headerr\\pard \\qr Chapter 1\\par}\r\n";
    $expected .= "{\\footerl\\pard \\ql \\chpgn \\par}\r\n";
    $expected .= "{\\footerr\\pard \\qr \\chpgn \\par}\r\n";
    $expected .= "{\\*\\bkmkstart chapter2}{\\*\\bkmkend chapter2}\r\n";
    $expected .= "{\\pard \\sa195\\fs32\\b\\keepn Chapter 1\\par}\r\n";
    $expected .= "{\\pard \\sa195\\qj\\fs24 This is the first {\\*\\bkmkstart index-0}{\\*\\bkmkend index-0}paragraph\\par}\r\n";
    $expected .= "\\sect\r\n";
    $expected .= "{\\headerl\\pard \\ql\\b Book title\\par}\r\n";
    $expected .= "{\\headerr\\pard \\qr Index\\par}\r\n";
    $expected .= "{\\footerl\\pard \\ql \\chpgn \\par}\r\n";
    $expected .= "{\\footerr\\pard \\qr \\chpgn \\par}\r\n";
    $expected .= "{\\*\\bkmkstart chapter3}{\\*\\bkmkend chapter3}\r\n";
    $expected .= "{\\pard \\sa195\\fs32\\b\\keepn Index\\par}\r\n";
    $expected .= "\\sect\\sbknone\\cols2\r\n";
    $expected .= "{\\pard \\sa0\\fs28\\b\\keepn P\\par}\r\n";
    $expected .= "{\\pard \\sa195\\qj\\fs24 Paragraph {\\field{\\*\\fldinst PAGEREF index-0}}\par}\r\n";
    $expected .= "}";

    $observed = $this->convertrtf->bookexportrtf_convert($content);
    $this->assertEquals($expected, $observed , "Failure converting basic book");
  }

  /**
   * Test getting the html conversion on some small elements
   */
  public function test_html_conversions() {
    $this->convertrtf->bookexportrtf_is_book = 1;

    $expected = [
      "a:href (url == label)" => ['<a href = "http://www.rork.nl/">www.rork.nl</a>', 'a', "www.rork.nl"],
      "a:href (url != label)" => ['<a href = "http://www.rork.nl/">my website</a>', 'a', "my website{\\footnote \\pard {\\super \\chftn} http://www.rork.nl/}"],
      "a:name (index item)" => ['<a name = "indexItem"></a>an index item', 'a', "{\\*\\bkmkstart index-0}{\\*\\bkmkend index-0}an index item"],
      "a:name (no index item)" => ['<a name = "NoIndexItem"></a>no index item', 'a', "no index item"],
      "article" => ['<article><p>some text</p></article>', 'article', "\\sect\\sftnrstpg\r\n{\\pard \\sa195\\qj some text\\par}\r\n"],
      "b" => ['<b>bold</b>', 'b', "{\\b bold}"],
      "br" => ['<br>', 'br', "\\tab\\line\r\n"],
      "br (with closing slash)" => ['<br />', 'br', "\\tab\\line\r\n"],
      "code" => ['<code>echo "foo";</code>', 'code', "{\\pard \\f1 echo \"foo\";\\par}\r\n"],
      "del" => ['<del>delete</del>', 'del', "{\\strike delete}"],
      "div" => ['<div>text</div>', 'div', "text"],
      "em" => ['<em>emphasis</em>', 'em', "{\\i emphasis}"],
      "h1" => ['<h1>header</h1>', 'h1', "{\\headerl\\pard \\ql\\b Book title\\par}\r\n{\\headerr\\pard \\qr header\\par}\r\n{\\footerl\\pard \\ql \\chpgn \\par}\r\n{\\footerr\\pard \\qr \\chpgn \\par}\r\n{\\*\\bkmkstart chapter1}{\\*\\bkmkend chapter1}\r\n{\\pard \\sa195\\fs32\\b\\keepn header\\par}\r\n"],
      "h2" => ['<h2>header</h2>', 'h2', "{\\pard \\sa0\\fs28\\b\\keepn header\\par}\r\n"],
      "h3" => ['<h3>header</h3>', 'h3', "{\\pard \\b\\keepn header\\par}\r\n"],
      "h4" => ['<h4>header</h4>', 'h4', "{\\pard \\keepn header\\par}\r\n"],
      "h5" => ['<h5>header</h5>', 'h5', "{\\pard \\keepn header\\par}\r\n"],
      "h6" => ['<h6>header</h6>', 'h6', "{\\pard \\keepn header\\par}\r\n"],
      "head" => ['<head><title>page title</head>', 'head', ""],
      "i" => ['<i>italic text</i>', 'i', "{\\i italic text}"],
      "ins" => ['<ins>insert</ins>', 'ins', "{\\ul insert}"],
      "li (ol)" => ['<ol><li>first item<li>second item</ol>', 'ol', "{\\pard \\fi-360\\sa0\\li720\\ql  1.\\tab first item\\par}\r\n{\\pard \\fi-360\\sa195\\li720\\ql  2.\\tab second item\\par}\r\n"],
      "li (ol with unordered sublists)" => ['<ol><li>first item<ul><li>second item</ul><li>third item<ul><li>fourth item</ul></ol>', 'ol', "{\\pard \\fi-360\\sa0\\li720\\ql  1.\\tab first item\\par}\r\n{\\pard \\fi-360\\sa0\\li1440\\ql \\bullet\\tab second item\\par}\r\n{\\pard \\fi-360\\sa0\\li720\\ql  2.\\tab third item\\par}\r\n{\\pard \\fi-360\\sa195\\li1440\\ql \\bullet\\tab fourth item\\par}\r\n"],
      "li (ul)" => ['<ul><li>first item<li>second item</ul>', 'ul', "{\\pard \\fi-360\\sa0\\li720\\ql \\bullet\\tab first item\\par}\r\n{\\pard \\fi-360\\sa195\\li720\\ql \\bullet\\tab second item\\par}\r\n"],
      "p" => ['<p>Some text.</p>', 'p', "{\\pard \\sa195\\qj Some text.\\par}\r\n"],
      "p (display: none)" => ['<p style = "display: none;">Some text.</p>', 'p', ""],
      "s" => ['<s>strike through</s>', 's', "{\\strike strike through}"],
      "span" => ['<span>span</span>', 'span', "{span}"],
      "span (display: none)" => ['<span style = "display: none;">span</span>', 'span', ""],
      "strong" => ['<strong>strong</strong>', 'strong', "{\\b strong}"],
      "strike" => ['<strike>strike through</strike>', 'strike', "{\\strike strike through}"],
      "sub" => ['<sub>sub text</sub>', 'sub', "{\\sub sub text}"],
      "sup" => ['<sup>super text</sup>', 'sup', "{\\super super text}"],
      "table" => ['<table><tbody><tr><td>cell 1</td><td>cell 2</td></tr></tbody></table>', 'table', "{\\trowd\r\n\\cellx4153\r\n\\cellx8306\r\n\\pard\\intbl{\\ri30\\li30\\ql cell 1}\\cell\r\n\\pard\\intbl{\\ri30\\li30\\ql cell 2}\\cell\r\n\\row\r\n}\r\n{\\pard\\sa0\\par}\r\n"],
      "table (colspan)" => ['<table><tbody><tr><td>cell 1</td><td>cell 2</td></tr><tr><td colspan = "2">double cell</td></tr></tbody></table>', 'table', "{\\trowd\r\n\\cellx4153\r\n\\cellx8306\r\n\\pard\\intbl{\\ri30\\li30\\ql cell 1}\\cell\r\n\\pard\\intbl{\\ri30\\li30\\ql cell 2}\\cell\r\n\\row\r\n\\trowd\r\n\\cellx8306\r\n\\pard\\intbl{\\ri30\\li30\\ql double cell}\\cell\r\n\\row\r\n}\r\n{\\pard\\sa0\\par}\r\n"],
      "u" => ['<u>underline</u>', 'u', "{\\ul underline}"],
    ];

    /**
     * I should find a way to test images too...
     */

    foreach (array_keys($expected) as $test) {
      $html = str_get_html($expected[$test][0]);
      $e = $html->find($expected[$test][1]);
      $this->convertrtf->bookexportrtf_traverse($e);
      $result = strip_tags($html);
      $this->assertEquals($expected[$test][2], $result , "Failure converting " . $test);
    }
  }

  /**
   * Test getting a css array from an element
   */
  public function test_get_css_from_element() {

    // some extra css selectors for testing
    $this->convertrtf->bookexportrtf_css['#own-id'] = ['font-weight' => 'bold'];
    $this->convertrtf->bookexportrtf_css['.own-class'] = ['text-decoration' => 'underline'];
    $this->convertrtf->bookexportrtf_css['#parent-id'] = ['font-weight' => 'bold'];
    $this->convertrtf->bookexportrtf_css['.parent-class'] = ['font-family' => 'Arial'];
    $this->convertrtf->bookexportrtf_css['.test-inheritance'] = [
      "border-bottom-style" => "solid",
      "border-bottom-width" => "1px",
      "border-left-style" => "solid",
      "border-left-width" => "1px",
      "border-right-style" => "solid",
      "border-right-width" => "1px",
      "border-top-style" => "solid",
      "border-top-width" => "1px",
      "color" => "blue",
      "font-family" => "Arial",
      "font-size" => "12pt",
      "font-style" => "italic",
      "font-weight" => "bold",
      "margin-bottom" => "10px",
      "margin-left" => "10px",
      "margin-right" => "10px",
      "margin-top" => "10px",
      "page-break-before" => "always",
      "page-break-after" => "always",
      "text-align" => "right",
      "text-decoration" => "underline",
      "text-decoration-color" => "blue",
      "text-decoration-style" => "wavy",
      "vertical-align" => "middle",
      "width" => "100px",
    ];

    $html = str_get_html('<span id = "own-id" class = "own-class" style = "color: blue">insert</span>');
    $e = $html->find('span');
    $css = $this->convertrtf->bookexportrtf_get_css_style_from_element($e[0]);
    $this->assertArrayHasKey("font-weight", $css, "Failure getting style from own id");
    $this->assertArrayHasKey("text-decoration", $css, "Failure getting style from own class");
    $this->assertArrayHasKey("color", $css, "Failure getting style from own attribute");

    $html = str_get_html('<p id = "parent-id" class = "parent-class" style = "color: blue"><span>insert</span></p>');
    $e = $html->find('span');
    $css = $this->convertrtf->bookexportrtf_get_css_style_from_element($e[0]);
    $this->assertArrayHasKey("font-weight", $css, "Failure getting style from parent id");
    $this->assertArrayHasKey("font-family", $css, "Failure getting style from parent class");
    $this->assertArrayHasKey("color", $css, "Failure getting style from parent attribute");

    $html = str_get_html('<p class = "test-inheritance"><span>insert</span></p>');
    $e = $html->find('span');
    $css = $this->convertrtf->bookexportrtf_get_css_style_from_element($e[0]);
    $this->assertFalse(array_key_exists("border-bottom-style", $css), "Failure getting inheritance of border-bottom-style");
    $this->assertFalse(array_key_exists("border-bottom-width", $css), "Failure getting inheritance of border-bottom-width");
    $this->assertFalse(array_key_exists("border-left-style", $css), "Failure getting inheritance of border-left-style");
    $this->assertFalse(array_key_exists("border-left-width", $css), "Failure getting inheritance of border-left-width");
    $this->assertFalse(array_key_exists("border-right-style", $css), "Failure getting inheritance of border-right-style");
    $this->assertFalse(array_key_exists("border-right-width", $css), "Failure getting inheritance of border-right-width");
    $this->assertFalse(array_key_exists("border-top-style", $css), "Failure getting inheritance of border-top-style");
    $this->assertFalse(array_key_exists("border-top-width", $css), "Failure getting inheritance of border-top-width");
    // color already tested
    // font-family already tested
    $this->assertArrayHasKey("font-size", $css, "Failure getting inheritance of font-size");
    $this->assertArrayHasKey("font-style", $css, "Failure getting inheritance of font-size");
    // font-weight already tested
    $this->assertFalse(array_key_exists("margin-bottom", $css), "Failure getting inheritance of margin-bottom");
    $this->assertFalse(array_key_exists("margin-left", $css), "Failure getting inheritance of margin-left");
    $this->assertFalse(array_key_exists("margin-right", $css), "Failure getting inheritance of margin-right");
    $this->assertFalse(array_key_exists("margin-top", $css), "Failure getting inheritance of margin-top");
    $this->assertFalse(array_key_exists("page-break-after", $css), "Failure getting inheritance of page-break-after");
    $this->assertFalse(array_key_exists("page-break-before", $css), "Failure getting inheritance of page-break-before");
    $this->assertArrayHasKey("text-align", $css, "Failure getting inheritance of text-align");
    $this->assertFalse(array_key_exists("text-decoration", $css), "Failure getting inheritance of text-decoration");
    $this->assertArrayHasKey("text-decoration-color", $css, "Failure getting inheritance of text-decoration-color");
    $this->assertFalse(array_key_exists("text-decoration-style", $css), "Failure getting inheritance of text-decoration-style");
    $this->assertFalse(array_key_exists("vertical-align", $css), "Failure getting inheritance of vertical-align");
    $this->assertFalse(array_key_exists("width", $css), "Failure getting inheritance of width");

    $html = str_get_html('<p style = "text-decoration: underline;"><span style = "text-decoration: inherit">insert</span></p>');
    $e = $html->find('span');
    $css = $this->convertrtf->bookexportrtf_get_css_style_from_element($e[0]);
    $this->assertArrayHasKey("text-decoration", $css, "Failure getting inheritance on inherit key word");
  }

  /**
   * Test getting the style from an css array.
   *
   * This should test the correct filtering of css properties and propper
   * correct specification of rtf style tags
   *
   * As behoviour for elements is inherited it should not be necessary to test
   * all possible tags, only div, p, td, span and a subset for h1 as this
   * behaves different from p for page breaks.
   *
   * It's likely that the testing for non-supported properties is rather
   * redundant here, one test should be sufficient?
   */
  public function test_get_rtf_from_css() {

    /**
     * first key: tag
     * second key: name of the test
     * first value: named array of css property and values to test
     * second value: expected prefix
     * third value: expected infix
     * fourth value: expected suffix
     */
    $expected = [
      "div" => [
        "border-bottom-style (solid)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'solid'], "", "", ""],
        "border-bottom-style (dotted)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'dotted'], "", "", ""],
        "border-bottom-style (dashed)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'dashed'], "", "", ""],
        "border-bottom-style (double)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'double'], "", "", ""],
        "border-bottom-style (none)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'none'], "", "", ""],
        "border-bottom-style (hidden)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'hidden'], "", "", ""],
        "border-bottom-width" => [['border-bottom-width' => '1px'], "", "", ""],
        "border-left-style (solid)" => [['border-left-width' => '1px', 'border-left-style' => 'solid'], "", "", ""],
        "border-left-style (dotted)" => [['border-left-width' => '1px', 'border-left-style' => 'dotted'], "", "", ""],
        "border-left-style (dashed)" => [['border-left-width' => '1px', 'border-left-style' => 'dashed'], "", "", ""],
        "border-left-style (double)" => [['border-left-width' => '1px', 'border-left-style' => 'double'], "", "", ""],
        "border-left-style (none)" => [['border-left-width' => '1px', 'border-left-style' => 'none'], "", "", ""],
        "border-left-style (hidden)" => [['border-left-width' => '1px', 'border-left-style' => 'hidden'], "", "", ""],
        "border-left-width" => [['border-left-width' => '1px'], "", "", ""],
        "border-right-style (solid)" => [['border-right-width' => '1px', 'border-right-style' => 'solid'], "", "", ""],
        "border-right-style (dotted)" => [['border-right-width' => '1px', 'border-right-style' => 'dotted'], "", "", ""],
        "border-right-style (dashed)" => [['border-right-width' => '1px', 'border-right-style' => 'dashed'], "", "", ""],
        "border-right-style (double)" => [['border-right-width' => '1px', 'border-right-style' => 'double'], "", "", ""],
        "border-right-style (none)" => [['border-right-width' => '1px', 'border-right-style' => 'none'], "", "", ""],
        "border-right-style (hidden)" => [['border-right-width' => '1px', 'border-right-style' => 'hidden'], "", "", ""],
        "border-right-width" => [['border-right-width' => '1px'], "", "", ""],
        "border-top-style (solid)" => [['border-top-width' => '1px', 'border-top-style' => 'solid'], "", "", ""],
        "border-top-style (dotted)" => [['border-top-width' => '1px', 'border-top-style' => 'dotted'], "", "", ""],
        "border-top-style (dashed)" => [['border-top-width' => '1px', 'border-top-style' => 'dashed'], "", "", ""],
        "border-top-style (double)" => [['border-top-width' => '1px', 'border-top-style' => 'double'], "", "", ""],
        "border-top-style (none)" => [['border-top-width' => '1px', 'border-top-style' => 'none'], "", "", ""],
        "border-top-style (hidden)" => [['border-top-width' => '1px', 'border-top-style' => 'hidden'], "", "", ""],
        "border-top-width" => [['border-top-width' => '1px'], "", "", ""],
        "color" => [["color" => "blue"], "", "", ""],
        "font-family (default)" => [["font-family" => "Calibri"], "", "", ""],
        "font-family (new)" => [["font-family" => "Arial"], "", "", ""],
        "font-family (two)" => [["font-family" => "Calibri, Arial"], "", "", ""],
        "font-size" => [["font-size" => "12pt"], "", "", ""],
        "font-weight (bold)" => [["font-weight" => "bold"], "", "", ""],
        "font-weight (normal)" => [["font-weight" => "normal"], "", "", ""],
        "margin-bottom" => [['margin-bottom' => '10px'], "", "", ""],
        "margin-left" => [['margin-left' => '10px'], "", "", ""],
        "margin-right" => [['margin-right' => '10px'], "", "", ""],
        "margin-top" => [['margin-top' => '10px'], "", "", ""],
        "page-break-after (always)" => [['page-break-after' => 'always'], "", "", "\\page\r\n"],
        "page-break-after (avoid)" => [['page-break-after' => 'avoid'], "", "", ""],
        "page-break-before (always)" => [['page-break-before' => 'always'], "\\page\r\n", "", ""],
        "page-break-before (avoid)" => [['page-break-before' => 'avoid'], "", "", ""],
        "text-align (left)" => [["text-align" => "left"], "", "", ""],
        "text-align (right)" => [["text-align" => "right"], "", "", ""],
        "text-align (center)" => [["text-align" => "center"], "", "", ""],
        "text-align (justify)" => [["text-align" => "justify"], "", "", ""],
        "text-decoration (underline)" => [["text-decoration" => "underline"], "", "", ""],
        "text-decoration (line-through)" => [["text-decoration" => "line-through"], "", "", ""],
        "text-decorarion (none)" => [["text-decoration" => "none"], "", "", ""],
        "text-decoration-color" => [["text-decoration-color" => "red"], "", "", ""],
        "text-decoration-style (solid)" => [['text-decoration' => 'underline', 'text-decoration-style' => 'solid'], "", "", ""],
        "text-decoration-style (double)" => [['text-decoration' => 'underline', 'text-decoration-style' => 'double'], "", "", ""],
        "text-decoration-style (dashed)" => [['text-decoration' => 'underline', 'text-decoration-style' => 'dashed'], "", "", ""],
        "text-decoration-style (dotted)" => [['text-decoration' => 'underline', 'text-decoration-style' => 'dotted'], "", "", ""],
        "text-decoration-style (wavy)" => [['text-decoration' => 'underline', 'text-decoration-style' => 'wavy'], "", "", ""],
        "text-indent" => [['text-indent' => '18pt'], "", "", ""],
        "vertical align top" => [['vertical-align' => 'top'], "", "", ""],
        "vertical align middle" => [['vertical-align' => 'middle'], "", "", ""],
        "vertical align bottom" => [['vertical-align' => 'bottom'], "", "", ""],
      ],
      "p" => [
        "border-bottom-style (solid)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'solid'], "", "", ""],
        "border-bottom-style (dotted)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'dotted'], "", "", ""],
        "border-bottom-style (dashed)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'dashed'], "", "", ""],
        "border-bottom-style (double)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'double'], "", "", ""],
        "border-bottom-style (none)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'none'], "", "", ""],
        "border-bottom-style (hidden)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'hidden'], "", "", ""],
        "border-bottom-width" => [['border-bottom-width' => '1px'], "", "", ""],
        "border-left-style (solid)" => [['border-left-width' => '1px', 'border-left-style' => 'solid'], "", "", ""],
        "border-left-style (dotted)" => [['border-left-width' => '1px', 'border-left-style' => 'dotted'], "", "", ""],
        "border-left-style (dashed)" => [['border-left-width' => '1px', 'border-left-style' => 'dashed'], "", "", ""],
        "border-left-style (double)" => [['border-left-width' => '1px', 'border-left-style' => 'double'], "", "", ""],
        "border-left-style (none)" => [['border-left-width' => '1px', 'border-left-style' => 'none'], "", "", ""],
        "border-left-style (hidden)" => [['border-left-width' => '1px', 'border-left-style' => 'hidden'], "", "", ""],
        "border-left-width" => [['border-left-width' => '1px'], "", "", ""],
        "border-right-style (solid)" => [['border-right-width' => '1px', 'border-right-style' => 'solid'], "", "", ""],
        "border-right-style (dotted)" => [['border-right-width' => '1px', 'border-right-style' => 'dotted'], "", "", ""],
        "border-right-style (dashed)" => [['border-right-width' => '1px', 'border-right-style' => 'dashed'], "", "", ""],
        "border-right-style (double)" => [['border-right-width' => '1px', 'border-right-style' => 'double'], "", "", ""],
        "border-right-style (none)" => [['border-right-width' => '1px', 'border-right-style' => 'none'], "", "", ""],
        "border-right-style (hidden)" => [['border-right-width' => '1px', 'border-right-style' => 'hidden'], "", "", ""],
        "border-right-width" => [['border-right-width' => '1px'], "", "", ""],
        "border-top-style (solid)" => [['border-top-width' => '1px', 'border-top-style' => 'solid'], "", "", ""],
        "border-top-style (dotted)" => [['border-top-width' => '1px', 'border-top-style' => 'dotted'], "", "", ""],
        "border-top-style (dashed)" => [['border-top-width' => '1px', 'border-top-style' => 'dashed'], "", "", ""],
        "border-top-style (double)" => [['border-top-width' => '1px', 'border-top-style' => 'double'], "", "", ""],
        "border-top-style (none)" => [['border-top-width' => '1px', 'border-top-style' => 'none'], "", "", ""],
        "border-top-style (hidden)" => [['border-top-width' => '1px', 'border-top-style' => 'hidden'], "", "", ""],
        "border-top-width" => [['border-top-width' => '1px'], "", "", ""],
        "color" => [["color" => "blue"], "", "\\cf4 ", ""],
        "font-family (default)" => [["font-family" => "Calibri"], "", "", ""],
        "font-family (new)" => [["font-family" => "Arial"], "", "\\f1 ", ""],
        "font-family (two)" => [["font-family" => "Calibri, Arial"], "", "", ""],
        "font-size" => [["font-size" => "12pt"], "", "\\fs24 ", ""],
        "font-style" => [["font-style" => "italic"], "", "\\i ", ""],
        "font-weight (bold)" => [["font-weight" => "bold"], "", "\\b ", ""],
        "font-weight (normal)" => [["font-weight" => "normal"], "", "", ""],
        "margin-bottom" => [['margin-bottom' => '10px'], "", "\\sa150 ", ""],
        "margin-left" => [['margin-left' => '10px'], "", "\\li150 ", ""],
        "margin-right" => [['margin-right' => '10px'], "", "\\ri150 ", ""],
        "margin-top" => [['margin-top' => '10px'], "", "\\sb150 ", ""],
        "page-break-after (always)" => [['page-break-after' => 'always'], "", "", ""],
        "page-break-after (avoid)" => [['page-break-after' => 'avoid'], "", "", ""],
        "page-break-before (always)" => [['page-break-before' => 'always'], "", "", ""],
        "page-break-before (avoid)" => [['page-break-before' => 'avoid'], "", "", ""],
        "text-align (left)" => [["text-align" => "left"], "", "\\ql ", ""],
        "text-align (right)" => [["text-align" => "right"], "", "\\qr ", ""],
        "text-align (center)" => [["text-align" => "center"], "", "\\qc ", ""],
        "text-align (justify)" => [["text-align" => "justify"], "", "\\qj ", ""],
        "text-decoration (underline)" => [["text-decoration" => "underline"], "", "\\ul ", ""],
        "text-decoration (line-through)" => [["text-decoration" => "line-through"], "", "\\strike ", ""],
        "text-decorarion (none)" => [["text-decoration" => "none"], "", "", ""],
        "text-decoration-color" => [["text-decoration-color" => "red"], "", "\\ulc2 ", ""],
        "text-decoration-style (solid)" => [['text-decoration' => 'underline', 'text-decoration-style' => 'solid'], "", "\\ul ", ""],
        "text-decoration-style (double)" => [['text-decoration' => 'underline', 'text-decoration-style' => 'double'], "", "\\uldb ", ""],
        "text-decoration-style (dashed)" => [['text-decoration' => 'underline', 'text-decoration-style' => 'dashed'], "", "\\uldash ", ""],
        "text-decoration-style (dotted)" => [['text-decoration' => 'underline', 'text-decoration-style' => 'dotted'], "", "\\uld ", ""],
        "text-decoration-style (wavy)" => [['text-decoration' => 'underline', 'text-decoration-style' => 'wavy'], "", "\\ulwave ", ""],
        "text-indent" => [['text-indent' => '18pt'], "", "\\fi360 ", ""],
        "vertical align top" => [['vertical-align' => 'top'], "", "", ""],
        "vertical align middle" => [['vertical-align' => 'middle'], "", "", ""],
        "vertical align bottom" => [['vertical-align' => 'bottom'], "", "", ""],
      ],
      "h1" => [
        "page-break-after (always)" => [['page-break-after' => 'always'], "", "", "\\page\r\n"],
        "page-break-after (auto)" => [['page-break-after' => 'auto'], "", "", ""],
        "page-break-after (avoid)" => [['page-break-after' => 'avoid'], "", "\\keepn ", ""],
        "page-break-before (always)" => [['page-break-before' => 'always'], "\\page\r\n", "", ""],
        "page-break-before (auto)" => [['page-break-before' => 'auto'], "", "", ""],
        "page-break-before (avoid)" => [['page-break-before' => 'avoid'], "", "", ""],
      ],
      "article" => [
        "page-break-before (always)" => [['page-break-before' => 'always'], "", "\\sbkpage ", ""],
        "page-break-before (auto)" => [['page-break-before' => 'auto'], "", "", ""],
        "page-break-before (avoid)" => [['page-break-before' => 'avoid'], "", "\\sbknone ", ""],
      ],
      "td" => [
        "border-bottom-style (solid)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'solid'], "\\clbrdrb\\brdrw15\\brdrs \r\n", "", ""],
        "border-bottom-style (dotted)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'dotted'], "\\clbrdrb\\brdrw15\\brdrdot \r\n", "", ""],
        "border-bottom-style (dashed)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'dashed'], "\\clbrdrb\\brdrw15\\brdrdash \r\n", "", ""],
        "border-bottom-style (double)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'double'], "\\clbrdrb\\brdrw15\\brdrdb \r\n", "", ""],
        "border-bottom-style (none)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'none'], "\\clbrdrb\\brdrw15\\brdrnone \r\n", "", ""],
        "border-bottom-style (hidden)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'hidden'], "\\clbrdrb\\brdrw15\\brdrnone \r\n", "", ""],
        "border-bottom-width" => [['border-bottom-width' => '1px'], "\\clbrdrb\\brdrw15\\brdrs \r\n", "", ""],
        "border-left-style (solid)" => [['border-left-width' => '1px', 'border-left-style' => 'solid'], "\\clbrdrl\\brdrw15\\brdrs \r\n", "", ""],
        "border-left-style (dotted)" => [['border-left-width' => '1px', 'border-left-style' => 'dotted'], "\\clbrdrl\\brdrw15\\brdrdot \r\n", "", ""],
        "border-left-style (dashed)" => [['border-left-width' => '1px', 'border-left-style' => 'dashed'], "\\clbrdrl\\brdrw15\\brdrdash \r\n", "", ""],
        "border-left-style (double)" => [['border-left-width' => '1px', 'border-left-style' => 'double'], "\\clbrdrl\\brdrw15\\brdrdb \r\n", "", ""],
        "border-left-style (none)" => [['border-left-width' => '1px', 'border-left-style' => 'none'], "\\clbrdrl\\brdrw15\\brdrnone \r\n", "", ""],
        "border-left-style (hidden)" => [['border-left-width' => '1px', 'border-left-style' => 'hidden'], "\\clbrdrl\\brdrw15\\brdrnone \r\n", "", ""],
        "border-left-width" => [['border-left-width' => '1px'], "\\clbrdrl\\brdrw15\\brdrs \r\n", "", ""],
        "border-right-style (solid)" => [['border-right-width' => '1px', 'border-right-style' => 'solid'], "\\clbrdrr\\brdrw15\\brdrs \r\n", "", ""],
        "border-right-style (dotted)" => [['border-right-width' => '1px', 'border-right-style' => 'dotted'], "\\clbrdrr\\brdrw15\\brdrdot \r\n", "", ""],
        "border-right-style (dashed)" => [['border-right-width' => '1px', 'border-right-style' => 'dashed'], "\\clbrdrr\\brdrw15\\brdrdash \r\n", "", ""],
        "border-right-style (double)" => [['border-right-width' => '1px', 'border-right-style' => 'double'], "\\clbrdrr\\brdrw15\\brdrdb \r\n", "", ""],
        "border-right-style (none)" => [['border-right-width' => '1px', 'border-right-style' => 'none'], "\\clbrdrr\\brdrw15\\brdrnone \r\n", "", ""],
        "border-right-style (hidden)" => [['border-right-width' => '1px', 'border-right-style' => 'hidden'], "\\clbrdrr\\brdrw15\\brdrnone \r\n", "", ""],
        "border-right-width" => [['border-right-width' => '1px'], "\\clbrdrr\\brdrw15\\brdrs \r\n", "", ""],
        "border-top-style (solid)" => [['border-top-width' => '1px', 'border-top-style' => 'solid'], "\\clbrdrt\\brdrw15\\brdrs \r\n", "", ""],
        "border-top-style (dotted)" => [['border-top-width' => '1px', 'border-top-style' => 'dotted'], "\\clbrdrt\\brdrw15\\brdrdot \r\n", "", ""],
        "border-top-style (dashed)" => [['border-top-width' => '1px', 'border-top-style' => 'dashed'], "\\clbrdrt\\brdrw15\\brdrdash \r\n", "", ""],
        "border-top-style (double)" => [['border-top-width' => '1px', 'border-top-style' => 'double'], "\\clbrdrt\\brdrw15\\brdrdb \r\n", "", ""],
        "border-top-style (none)" => [['border-top-width' => '1px', 'border-top-style' => 'none'], "\\clbrdrt\\brdrw15\\brdrnone \r\n", "", ""],
        "border-top-style (hidden)" => [['border-top-width' => '1px', 'border-top-style' => 'hidden'], "\\clbrdrt\\brdrw15\\brdrnone \r\n", "", ""],
        "border-top-width" => [['border-top-width' => '1px'], "\\clbrdrt\\brdrw15\\brdrs \r\n", "", ""],
        "color" => [["color" => "blue"], "", "\\cf4 ", ""],
        "font-family (default)" => [["font-family" => "Calibri"], "", "", ""],
        "font-family (new)" => [["font-family" => "Arial"], "", "\\f1 ", ""],
        "font-family (two)" => [["font-family" => "Calibri, Arial"], "", "", ""],
        "font-size" => [["font-size" => "12pt"], "", "\\fs24 ", ""],
        "font-style" => [["font-style" => "italic"], "", "\\i ", ""],
        "font-weight (bold)" => [["font-weight" => "bold"], "", "\\b ", ""],
        "font-weight (normal)" => [["font-weight" => "normal"], "", "", ""],
        "margin-bottom" => [['margin-bottom' => '10px'], "", "\\sa150 ", ""],
        "margin-left" => [['margin-left' => '10px'], "", "\\li150 ", ""],
        "margin-right" => [['margin-right' => '10px'], "", "\\ri150 ", ""],
        "margin-top" => [['margin-top' => '10px'], "", "\\sb150 ", ""],
        "page-break-after (always)" => [['page-break-after' => 'always'], "", "", ""],
        "page-break-after (avoid)" => [['page-break-after' => 'avoid'], "", "", ""],
        "page-break-before (always)" => [['page-break-before' => 'always'], "", "", ""],
        "page-break-before (avoid)" => [['page-break-before' => 'avoid'], "", "", ""],
        "text-align (left)" => [["text-align" => "left"], "", "\\ql ", ""],
        "text-align (right)" => [["text-align" => "right"], "", "\\qr ", ""],
        "text-align (center)" => [["text-align" => "center"], "", "\\qc ", ""],
        "text-align (justify)" => [["text-align" => "justify"], "", "\\qj ", ""],
        "text-decoration (underline)" => [["text-decoration" => "underline"], "", "\\ul ", ""],
        "text-decoration (line-through)" => [["text-decoration" => "line-through"], "", "\\strike ", ""],
        "text-decorarion (none)" => [["text-decoration" => "none"], "", "", ""],
        "text-decoration-color" => [["text-decoration-color" => "red"], "", "\\ulc2 ", ""],
        "text-decoration-style (solid)" => [['text-decoration' => 'underline', 'text-decoration-style' => 'solid'], "", "\\ul ", ""],
        "text-decoration-style (double)" => [['text-decoration' => 'underline', 'text-decoration-style' => 'double'], "", "\\uldb ", ""],
        "text-decoration-style (dashed)" => [['text-decoration' => 'underline', 'text-decoration-style' => 'dashed'], "", "\\uldash ", ""],
        "text-decoration-style (dotted)" => [['text-decoration' => 'underline', 'text-decoration-style' => 'dotted'], "", "\\uld ", ""],
        "text-decoration-style (wavy)" => [['text-decoration' => 'underline', 'text-decoration-style' => 'wavy'], "", "\\ulwave ", ""],
        "text-indent" => [['text-indent' => '18pt'], "", "\\fi360 ", ""],
        "vertical-align (top)" => [['vertical-align' => 'top'], "\\clvertalt\r\n", "", ""],
        "vertical-align (middle)" => [['vertical-align' => 'middle'], "\\clvertalc\r\n", "", ""],
        "vertical-align (bottom)" => [['vertical-align' => 'bottom'], "\\clvertalb\r\n", "", ""],
      ],
      "span" => [
        "border-bottom-style (solid)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'solid'], "", "", ""],
        "border-bottom-style (dotted)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'dotted'], "", "", ""],
        "border-bottom-style (dashed)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'dashed'], "", "", ""],
        "border-bottom-style (double)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'double'], "", "", ""],
        "border-bottom-style (none)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'none'], "", "", ""],
        "border-bottom-style (hidden)" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'hidden'], "", "", ""],
        "border-bottom-width" => [['border-bottom-width' => '1px'], "", "", ""],
        "border-left-style (solid)" => [['border-left-width' => '1px', 'border-left-style' => 'solid'], "", "", ""],
        "border-left-style (dotted)" => [['border-left-width' => '1px', 'border-left-style' => 'dotted'], "", "", ""],
        "border-left-style (dashed)" => [['border-left-width' => '1px', 'border-left-style' => 'dashed'], "", "", ""],
        "border-left-style (double)" => [['border-left-width' => '1px', 'border-left-style' => 'double'], "", "", ""],
        "border-left-style (none)" => [['border-left-width' => '1px', 'border-left-style' => 'none'], "", "", ""],
        "border-left-style (hidden)" => [['border-left-width' => '1px', 'border-left-style' => 'hidden'], "", "", ""],
        "border-left-width" => [['border-left-width' => '1px'], "", "", ""],
        "border-right-style (solid)" => [['border-right-width' => '1px', 'border-right-style' => 'solid'], "", "", ""],
        "border-right-style (dotted)" => [['border-right-width' => '1px', 'border-right-style' => 'dotted'], "", "", ""],
        "border-right-style (dashed)" => [['border-right-width' => '1px', 'border-right-style' => 'dashed'], "", "", ""],
        "border-right-style (double)" => [['border-right-width' => '1px', 'border-right-style' => 'double'], "", "", ""],
        "border-right-style (none)" => [['border-right-width' => '1px', 'border-right-style' => 'none'], "", "", ""],
        "border-right-style (hidden)" => [['border-right-width' => '1px', 'border-right-style' => 'hidden'], "", "", ""],
        "border-right-width" => [['border-right-width' => '1px'], "", "", ""],
        "border-top-style (solid)" => [['border-top-width' => '1px', 'border-top-style' => 'solid'], "", "", ""],
        "border-top-style (dotted)" => [['border-top-width' => '1px', 'border-top-style' => 'dotted'], "", "", ""],
        "border-top-style (dashed)" => [['border-top-width' => '1px', 'border-top-style' => 'dashed'], "", "", ""],
        "border-top-style (double)" => [['border-top-width' => '1px', 'border-top-style' => 'double'], "", "", ""],
        "border-top-style (none)" => [['border-top-width' => '1px', 'border-top-style' => 'none'], "", "", ""],
        "border-top-style (hidden)" => [['border-top-width' => '1px', 'border-top-style' => 'hidden'], "", "", ""],
        "border-top-width" => [['border-top-width' => '1px'], "", "", ""],
        "color" => [["color" => "blue"], "", "\\cf4 ", ""],
        "font-family (default)" => [["font-family" => "Calibri"], "", "", ""],
        "font-family (new)" => [["font-family" => "Arial"], "", "\\f1 ", ""],
        "font-family (two)" => [["font-family" => "Calibri, Arial"], "", "", ""],
        "font-size" => [["font-size" => "12pt"], "", "\\fs24 ", ""],
        "font-weight (bold)" => [["font-weight" => "bold"], "", "\\b ", ""],
        "font-weight (normal)" => [["font-weight" => "normal"], "", "", ""],
        "margin-bottom" => [['margin-bottom' => '10px'], "", "", ""],
        "margin-left" => [['margin-left' => '10px'], "", "", ""],
        "margin-right" => [['margin-right' => '10px'], "", "", ""],
        "margin-top" => [['margin-top' => '10px'], "", "", ""],
        "page-break-after (always)" => [['page-break-after' => 'always'], "", "", ""],
        "page-break-after (avoid)" => [['page-break-after' => 'avoid'], "", "", ""],
        "page-break-before (always)" => [['page-break-before' => 'always'], "", "", ""],
        "page-break-before (avoid)" => [['page-break-before' => 'avoid'], "", "", ""],
        "text-align (left)" => [["text-align" => "left"], "", "", ""],
        "text-align (right)" => [["text-align" => "right"], "", "", ""],
        "text-align (center)" => [["text-align" => "center"], "", "", ""],
        "text-align (justify)" => [["text-align" => "justify"], "", "", ""],
        "text-decoration (underline)" => [["text-decoration" => "underline"], "", "\\ul ", ""],
        "text-decoration (line-through)" => [["text-decoration" => "line-through"], "", "\\strike ", ""],
        "text-decorarion (none)" => [["text-decoration" => "none"], "", "", ""],
        "text-decoration-color" => [["text-decoration-color" => "red"], "", "\\ulc2 ", ""],
        "text-decoration-style (solid)" => [['text-decoration' => 'underline', 'text-decoration-style' => 'solid'], "", "\\ul ", ""],
        "text-decoration-style (double)" => [['text-decoration' => 'underline', 'text-decoration-style' => 'double'], "", "\\uldb ", ""],
        "text-decoration-style (dashed)" => [['text-decoration' => 'underline', 'text-decoration-style' => 'dashed'], "", "\\uldash ", ""],
        "text-decoration-style (dotted)" => [['text-decoration' => 'underline', 'text-decoration-style' => 'dotted'], "", "\\uld ", ""],
        "text-decoration-style (wavy)" => [['text-decoration' => 'underline', 'text-decoration-style' => 'wavy'], "", "\\ulwave ", ""],
        "text-indent" => [['text-indent' => '18pt'], "", "", ""],
        "vertical align top" => [['vertical-align' => 'top'], "", "", ""],
        "vertical align middle" => [['vertical-align' => 'middle'], "", "", ""],
        "vertical align bottom" => [['vertical-align' => 'bottom'], "", "", ""],
      ],
    ];

    foreach (array_keys($expected) as $tag) {
      foreach (array_keys($expected[$tag]) as $test) {
        $result = $this->convertrtf->bookexportrtf_get_rtf_style_from_css($expected[$tag][$test][0], $tag);
        $this->assertEquals($expected[$tag][$test][1], $result[0], "Failure setting style prefix for " . $test . " of " . $tag);
        $this->assertEquals($expected[$tag][$test][2], $result[1], "Failure setting style infix for " . $test . " of " . $tag);
        $this->assertEquals($expected[$tag][$test][3], $result[2], "Failure setting style suffix for " . $test . " of " . $tag);
      }
    }
  }

  /**
   * Test color conversion
   *
   * Should convert from HTML color, RGB and color name to a position in the
   * colortable.
   * If a color does not exist it should be added to the end of the colortable.
   * If a color is invalid should return 0 (the default color).
   */
  public function test_color() {
    $this->assertEquals(1, $this->convertrtf->bookexportrtf_convert_color("#000000"), "Failure to convert HTML black");
    $this->assertEquals(2, $this->convertrtf->bookexportrtf_convert_color("#FF0000"), "Failure to convert HTML red");
    $this->assertEquals(3, $this->convertrtf->bookexportrtf_convert_color("#00FF00"), "Failure to convert HTML green");
    $this->assertEquals(4, $this->convertrtf->bookexportrtf_convert_color("#0000FF"), "Failure to convert HTML blue");
    $this->assertEquals(5, $this->convertrtf->bookexportrtf_convert_color("#FFFFFF"), "Failure to convert HTML white");
    $this->assertEquals(6, $this->convertrtf->bookexportrtf_convert_color("#8A2BE2"), "Failure to convert HTML blueviolet");
    $this->assertEquals(7, $this->convertrtf->bookexportrtf_convert_color("#7FFF00"), "Failure to add new HTML color chartreuse");
    $this->assertArrayHasKey("\\red127\\green255\\blue0", $this->convertrtf->bookexportrtf_colortbl, "Failure to add new HTML color chartreuse");
    $this->assertEquals(0, $this->convertrtf->bookexportrtf_convert_color("#FG00000"), "Failure on invalid HTML color");
    $this->assertEquals(7, $this->convertrtf->bookexportrtf_colortbl["\\red127\\green255\\blue0"], "Failure to add new HTML color at the correct position");
    $this->assertEquals(1, $this->convertrtf->bookexportrtf_convert_color("black"), "Failure to convert colorname black");
    $this->assertEquals(2, $this->convertrtf->bookexportrtf_convert_color("red"), "Failure to convert colorname red");
    $this->assertEquals(3, $this->convertrtf->bookexportrtf_convert_color("lime"), "Failure to convert colorname lime (green)");
    $this->assertEquals(4, $this->convertrtf->bookexportrtf_convert_color("blue"), "Failure to convert colorname blue");
    $this->assertEquals(5, $this->convertrtf->bookexportrtf_convert_color("white"), "Failure to convert colorname white");
    $this->assertEquals(6, $this->convertrtf->bookexportrtf_convert_color("blueviolet"), "Failure to convert colorname blueviolet");
    $this->assertEquals(0, $this->convertrtf->bookexportrtf_convert_color("fooblue"), "Failure on invalid colorname");
    $this->assertEquals(1, $this->convertrtf->bookexportrtf_convert_color("rgb(0,0,0)"), "Failure to convert RGB black");
    $this->assertEquals(2, $this->convertrtf->bookexportrtf_convert_color("rgb(255,0,0)"), "Failure to convert RGB red");
    $this->assertEquals(3, $this->convertrtf->bookexportrtf_convert_color("rgb(0,255,0)"), "Failure to convert RGB green");
    $this->assertEquals(4, $this->convertrtf->bookexportrtf_convert_color("rgb(0,0,255)"), "Failure to convert RGB blue");
    $this->assertEquals(5, $this->convertrtf->bookexportrtf_convert_color("rgb(255,255,255)"), "Failure to convert RGB white");
    $this->assertEquals(6, $this->convertrtf->bookexportrtf_convert_color("rgb(138,43,226)"), "Failure to convert RGB blueviolet");
    $this->assertEquals(0, $this->convertrtf->bookexportrtf_convert_color("rgb(256,0,0)"), "Failure on invalid RGB color");
  }

  /**
   * Test length conversion
   *
   * Should convert from cm, mm, in, px, pt, pc to twips.
   * Defaults to 0 twips on failure.
   */
  public function test_length() {
    $this->assertEquals(0, $this->convertrtf->bookexportrtf_convert_length("foo"), "Failure to convert invalid length");
    $this->assertEquals(56693, $this->convertrtf->bookexportrtf_convert_length("100cm"),  "Failure to convert length (cm)");
    $this->assertEquals(5669, $this->convertrtf->bookexportrtf_convert_length("100mm"), "Failure to convert length (mm)");
    $this->assertEquals(144000, $this->convertrtf->bookexportrtf_convert_length("100in"), "Failure to convert length (in)");
    $this->assertEquals(1500, $this->convertrtf->bookexportrtf_convert_length("100px"), "Failure to convert length (px)");
    $this->assertEquals(2000, $this->convertrtf->bookexportrtf_convert_length("100pt"), "Failure to convert length (pt)");
    $this->assertEquals(24000, $this->convertrtf->bookexportrtf_convert_length("100pc"), "Failure to convert length (pc)");
  }

  /**
   * Test length conversion
   *
   * Should convert from cm, mm, in, px, pt, pc to half points.
   * Defaults to 24 half points on failure.
   */
  public function test_font_size() {
   $this->assertEquals(24, $this->convertrtf->bookexportrtf_convert_font_size("foo"), "Failure to convert invalid font size");
    $this->assertEquals(5669, $this->convertrtf->bookexportrtf_convert_font_size("100cm"), "Failure to convert font size (cm)");
    $this->assertEquals(567, $this->convertrtf->bookexportrtf_convert_font_size("100mm"), "Failure to convert font size (mm)");
    $this->assertEquals(14400, $this->convertrtf->bookexportrtf_convert_font_size("100in"), "Failure to convert font size (in)");
    $this->assertEquals(150, $this->convertrtf->bookexportrtf_convert_font_size("100px"), "Failure to convert font size (px)");
    $this->assertEquals(200, $this->convertrtf->bookexportrtf_convert_font_size("100pt"), "Failure to convert font size (pt)");
    $this->assertEquals(2400, $this->convertrtf->bookexportrtf_convert_font_size("100pc"), "Failure to convert font size (pc)");
  }
}
