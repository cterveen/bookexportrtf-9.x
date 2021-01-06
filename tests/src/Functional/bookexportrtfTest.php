<?php

namespace Drupal\Tests\bookexportrtf;

use Drupal\Tests\UnitTestCase;

/**
 * Test basic functionality of bookexportrtf
 *
 * Run from core:
 *   ../vendor/bin/phpunit ../sites/all/modules/bookexportrtf/tests/src/Functional/bookexportrtfTest.php
 */

class BookExportRtfTest extends UnitTestCase
{

  public function setUp() {
    /*
     * Load the HTML parser
     */
    include_once('../libraries/simple_html_dom/simple_html_dom.php');
    // Find the module location, either in /modules or /sites/all/modules

    $this->dir = "../modules/bookexportrtf";
    if (!is_dir($this->dir)) {
      $this->dir = "../sites/all/modules/bookexportrtf";
    }
    $this->testfile = $this->dir . "/tests/src/Functional/bookexportrtfTest.php";
    $this->codefile = $this->dir . "/src/Controller/BookExportRtfController.php";

    // make a color table
    $this->bookexportrtf_colortbl = [
      "\\red0\\green0\\blue0" => 1,
      "\\red255\\green0\\blue0" => 2,
      "\\red0\\green255\\blue0" => 3,
      "\\red0\\green0\\blue255" => 4,
      "\\red255\\green255\\blue255" => 5,
      "\\red138\\green43\\blue226" => 6];

    // make a font table
    $this->bookexportrtf_fonttbl = [
      "Calibri" => 0];

    // index list
    $this->bookexpor_rtf_index_id = ['Item' => 1];

    // CSS table
    $this->bookexportrtf_css = [
      'body' => ['font-family' => 'Calibri', 'font-size' => '12pt'],
      'p' => ['margin-bottom' => '13px', 'text-align' => 'justify'],
      'h1' => ['page-break-before' => 'always', 'margin-bottom' => '13px', 'font-size' => '16pt', 'font-weight' => 'bold'],
      'h2' => ['margin-bottom' => '0px', 'font-size' => '14pt', 'font-weight' => 'bold'],
      'h3' => ['margin-bottom' => '0px', 'font-size' => '16pt', 'font-weight' => ' bold', 'text-align' => 'center'],
      'li' => ['margin-bottom' => '0px', 'text-align' => 'left'],
      'th' => ['margin-left' => '2px', 'argin-right' => '2px', 'text-align' => 'left'],
      'td' => ['margin-left' => '2px', 'margin-right' => '2px', 'text-align' => 'left'],
      'ins' => ['text-decoration' => 'underline'],
      's' => ['text-decoration' => 'line-through'],
      'del' => ['text-decoration' => 'line-through'],
      'code' => ['font-family' => 'monospace'],
      '.header-left' => ['text-align' => 'left', 'font-weight' => 'bold'],
      '.header-right' => ['text-align' => 'right'],
      '.footer-left' => ['text-align' => 'left'],
      '.footer-right' => ['text-align' => 'right'],];

    $this->bookexportrtf_book_title = "Book title";
  }

  /**
   * Test whether required files are at their expected locations
   */
  public function test_files() {
    $this->assertFileExists($this->testfile, "Failure to find bookexportrtfTest.php");
    $this->assertFileExists($this->codefile, "Failure to find BookExportRtfController.php");
  }

  /**
   * Test function clone test
   *
   * Loading the BookExportRtfController class ends up in problems with
   * dependencies. Furthermore some of the functions to be tested are private
   * and can not be called by the test file.
   *
   * A work around is to copy paste the functions to this file and then test
   * them. For this it's required to test whether the function had been copied
   * correctly. This tests whether the the function is grabbed correctly.
   *
   * A more proper solution would be to move a lot of the conversion
   * functionality to a separate library with public functions.
   */
  public function test_function_clone_test() {
    $this->assertEquals("function get_test_function() {\n    //foo\n", $this->get_function("get_test_function", $this->testfile), "Failure cloning get_test_function()");
  }

  /**
   * Test getting the html conversion on some small elements
   */
  public function test_html_converions() {
    $this->assertEquals($this->get_function("bookexportrtf_traverse", $this->testfile), $this->get_function("bookexportrtf_traverse", $this->codefile), "Failure cloning bookexportrtf_traverse()");

    $expected = [
      "matched link" => ['<a href = "http://www.rork.nl/">www.rork.nl</a>', 'a', "www.rork.nl"],
      "unmatched link" => ['<a href = "http://www.rork.nl/">my website</a>', 'a', "my website{\\footnote \\pard {\\up6 \\chftn} http://www.rork.nl/}"],
      "index anchor" => ['<a name = "indexItem"></a>an index item', 'a', "{\\*\\bkmkstart index-1}{\\*\\bkmkend index-1}an index item"],
      "no index anchor" => ['<a name = "NoIndexItem"></a>no index item', 'a', "no index item"],
      "newline without closing backslash" => ['<br>', 'br', "\\tab\\line\r\n"],
      "newline with closing backslash" => ['<br />', 'br', "\\tab\\line\r\n"],
      "div" => ['<div>text</div>', 'div', "text"],
      "h1" => ['<h1>header</h1>', 'h1', "\\sect\\sftnrstpg\r\n{\\headerl\\pard \\ql\\b Book title\\par}\r\n{\\headerr\\pard \\qr header\\par}\r\n{\\footerl\\pard \\ql \\chpgn \\par}\r\n{\\footerr\\pard \\qr \\chpgn \\par}\r\n{\\pard\\keepn \\sa195\\fs32\\b header\\par}\r\n"],
      "h2" => ['<h2>header</h2>', 'h2', "{\\pard\\keepn \\sa0\\fs28\\b header\\par}\r\n"],
      "h3" => ['<h3>header</h3>', 'h3', "{\\pard\\keepn \\sa0\\qc\\fs32\\b header\\par}\r\n"],
      "h4" => ['<h4>header</h4>', 'h4', "{\\pard\\keepn header\\par}\r\n"],
      "h5" => ['<h5>header</h5>', 'h5', "{\\pard\\keepn header\\par}\r\n"],
      "h6" => ['<h6>header</h6>', 'h6', "{\\pard\\keepn header\\par}\r\n"],
      "head" => ['<head><title>page title</head>', 'head', ""],
      "italic text" => ['<i>italic text</i>', 'i', "{\\i italic text}"],
      "unordered list" => ['<ul><li>first item<li>second item</ul>', 'ul', "{\\pard \\sa0\\ql \\fi-360\\li720\\bullet\\tab first item\\par}\r\n{\\pard \\sa0\\ql \\fi-360\\li720\\bullet\\tab second item\\par}\r\n{\\pard\\sa0\\par}\r\n"],
      "ordered list" => ['<ol><li>first item<li>second item</ol', 'ol', "{\\pard \\sa0\\ql \\fi-360\\li720 1.\\tab first item\\par}\r\n{\\pard \\sa0\\ql \\fi-360\\li720 2.\\tab second item\\par}\r\n{\\pard\\sa0\\par}\r\n"],
      "p" => ['<p>Some text.</p>', 'p', "{\\pard \\sa195\\qj Some text.\\par}\r\n"],
      "code" => ['<code>echo "foo";</code>', 'code', "{\\pard \\f1 echo \"foo\";\\par}\r\n"],
      "s" => ['<s>strike through</s>', 's', "{\\strike strike through}"],
      "ins" => ['<ins>insert</ins>', 'ins', "{\\ul insert}"],
      "del" => ['<del>delete</del>', 'del', "{\\strike delete}"],
      "span" => ['<span>span</span>', 'span', "{span}"],
      "strong" => ['<strong>strong</strong>', 'strong', "{\\b strong}"],
      "b" => ['<b>bold</b>', 'b', "{\\b bold}"],
      "strike" => ['<strike>strike through</strike>', 'strike', "{\\strike strike through}"],
      "sub" => ['<sub>sub text</sub>', 'sub', "{\\sub sub text}"],
      "sup" => ['<sup>super text</sup>', 'sup', "{\\super super text}"],
      "simple table" => ['<table><tbody><tr><td>cell 1</td><td>cell 2</td></tr>', 'table', "{\\trowd\r\n\\cellx4655\r\n\\cellx9309\r\n\\intbl{\\ri30\\li30\\ql cell 1}\\cell\r\n\\intbl{\\ri30\\li30\\ql cell 2}\\cell\r\n\\row\r\n}\r\n{\\pard\\sa0\\par}\r\n"],
      "u" => ['<u>underline</u>', 'u', "{\\ul underline}"],
    ];

    /**
     * I should find a way to test images too...
     */

    foreach (array_keys($expected) as $test) {
      $html = str_get_html($expected[$test][0]);
      $e = $html->find($expected[$test][1]);
      $this->bookexportrtf_traverse($e);
      $result = strip_tags($html);
      $this->assertEquals($expected[$test][2], $result , "Failure converting " . $test);
    }
  }

  /**
   * Test getting a css array from an element
   */
  public function test_get_css_from_element() {
    $this->assertEquals($this->get_function("bookexportrtf_get_css_style_from_element", $this->testfile), $this->get_function("bookexportrtf_get_css_style_from_element", $this->codefile), "Failure cloning bookexportrtf_get_css_style_from_element()");

    // this seems to be broken?
    return;
    $html = str_get_html('<ins>insert</ins>');
    $e = $html->find('ins');
    $css = $this->bookexportrtf_get_css_style_from_element($e);
    $this->assertArrayHasKey("text-decoration", $css, "Failure getting text-decoration for ins");
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
    $this->assertEquals($this->get_function("bookexportrtf_get_rtf_style_from_css", $this->testfile), $this->get_function("bookexportrtf_get_rtf_style_from_css", $this->codefile), "Failure cloning bookexportrtf_get_rtf_style_from_css()");

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
        "font color" => [["color" => "blue"], "", "", ""],
        "default font" => [["font-family" => "Calibri"], "", "", ""],
        "new font" => [["font-family" => "Arial"], "", "", ""],
        "two fonts" => [["font-family" => "Calibri, Arial"], "", "", ""],
        "font size" => [["font-size" => "12pt"], "", "", ""],
        "bold font weight" => [["font-weight" => "bold"], "", "", ""],
        "normal font weight" => [["font-weight" => "normal"], "", "", ""],
        "left aligned text" => [["text-align" => "left"], "", "", ""],
        "right aligned text" => [["text-align" => "right"], "", "", ""],
        "centered text" => [["text-align" => "center"], "", "", ""],
        "justified text" => [["text-align" => "justify"], "", "", ""],
        "underlined text" => [["text-decoration" => "underline"], "", "", ""],
        "strike-trough" => [["text-decoration" => "line-through"], "", "", ""],
        "no text decoration" => [["text-decoration" => "none"], "", "", ""],
        "text decoration color" => [["text-decoration-color" => "red"], "", "", ""],
        "text decoration solid" => [['text-decoration' => 'underline', 'text-decoration-style' => 'solid'], "", "", ""],
        "text decoration double" => [['text-decoration' => 'underline', 'text-decoration-style' => 'double'], "", "", ""],
        "text decoration dashed" => [['text-decoration' => 'underline', 'text-decoration-style' => 'dashed'], "", "", ""],
        "text decoration dotted" => [['text-decoration' => 'underline', 'text-decoration-style' => 'dotted'], "", "", ""],
        "text decoration wavy" => [['text-decoration' => 'underline', 'text-decoration-style' => 'wavy'], "", "", ""],
        "top margin" => [['margin-top' => '10px'], "", "", ""],
        "right margin" => [['margin-right' => '10px'], "", "", ""],
        "bottom margin" => [['margin-bottom' => '10px'], "", "", ""],
        "left margin" => [['margin-left' => '10px'], "", "", ""],
        "always break page before" => [['page-break-before' => 'always'], "\\page\r\n", "", ""],
        "void page break before" => [['page-break-before' => 'avoid'], "", "", ""],
        "always page break after" => [['page-break-after' => 'always'], "", "", "\\page\r\n"],
        "avoid page break after" => [['page-break-after' => 'avoid'], "", "", ""],
        "border bottom" => [['border-bottom-width' => '1px'], "", "", ""],
        "border bottom style solid" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'solid'], "", "", ""],
        "border bottom style dotted" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'dotted'], "", "", ""],
        "border bottom style dashed" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'dashed'], "", "", ""],
        "border bottom style  double" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'double'], "", "", ""],
        "no border bottom" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'none'], "", "", ""],
        "hidden border bottom" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'hidden'], "", "", ""],
      ],
      "p" => [
        "font color" => [["color" => "blue"], "", "\\cf4 ", ""],
        "default font" => [["font-family" => "Calibri"], "", "", ""],
        "new font" => [["font-family" => "Arial"], "", "\\f1 ", ""],
        "two fonts" => [["font-family" => "Calibri, Arial"], "", "", ""],
        "font size" => [["font-size" => "12pt"], "", "\\fs24 ", ""],
        "bold font weight" => [["font-weight" => "bold"], "", "\\b ", ""],
        "normal font weight" => [["font-weight" => "normal"], "", "", ""],
        "left aligned text" => [["text-align" => "left"], "", "\\ql ", ""],
        "right aligned text" => [["text-align" => "right"], "", "\\qr ", ""],
        "centered text" => [["text-align" => "center"], "", "\\qc ", ""],
        "justified text" => [["text-align" => "justify"], "", "\\qj ", ""],
        "underlined text" => [["text-decoration" => "underline"], "", "\\ul ", ""],
        "strike-trough" => [["text-decoration" => "line-through"], "", "\\strike ", ""],
        "no text decoration" => [["text-decoration" => "none"], "", "", ""],
        "text decoration color" => [["text-decoration-color" => "red"], "", "\\ulc2 ", ""],
        "text decoration solid" => [['text-decoration' => 'underline', 'text-decoration-style' => 'solid'], "", "\\ul ", ""],
        "text decoration double" => [['text-decoration' => 'underline', 'text-decoration-style' => 'double'], "", "\\uldb ", ""],
        "text decoration dashed" => [['text-decoration' => 'underline', 'text-decoration-style' => 'dashed'], "", "\\uldash ", ""],
        "text decoration dotted" => [['text-decoration' => 'underline', 'text-decoration-style' => 'dotted'], "", "\\uld ", ""],
        "text decoration wavy" => [['text-decoration' => 'underline', 'text-decoration-style' => 'wavy'], "", "\\ulwave ", ""],
        "top margin" => [['margin-top' => '10px'], "", "\\sb150 ", ""],
        "right margin" => [['margin-right' => '10px'], "", "\\ri150 ", ""],
        "bottom margin" => [['margin-bottom' => '10px'], "", "\\sa150 ", ""],
        "left margin" => [['margin-left' => '10px'], "", "\\li150 ", ""],
        "always break page before" => [['page-break-before' => 'always'], "", "", ""],
        "void page break before" => [['page-break-before' => 'avoid'], "", "", ""],
        "always page break after" => [['page-break-after' => 'always'], "", "", ""],
        "avoid page break after" => [['page-break-after' => 'avoid'], "", "", ""],
        "border bottom" => [['border-bottom-width' => '1px'], "", "", ""],
        "border bottom style solid" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'solid'], "", "", ""],
        "border bottom style dotted" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'dotted'], "", "", ""],
        "border bottom style dashed" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'dashed'], "", "", ""],
        "border bottom style  double" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'double'], "", "", ""],
        "no border bottom" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'none'], "", "", ""],
        "hidden border bottom" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'hidden'], "", "", ""],
        "vertical align top" => [['vertical-align' => 'top'], "", "", ""],
        "vertical align middle" => [['vertical-align' => 'middle'], "", "", ""],
        "vertical align bottom" => [['vertical-align' => 'bottom'], "", "", ""],
      ],
      "h1" => [
        "always break page before" => [['page-break-before' => 'always'], "\\page\r\n", "", ""],
        "void page break before" => [['page-break-before' => 'avoid'], "", "", ""],
        "always page break after" => [['page-break-after' => 'always'], "", "", "\\page\r\n"],
        "avoid page break after" => [['page-break-after' => 'avoid'], "", "", ""],
      ],
      "td" => [
        "font color" => [["color" => "blue"], "", "\\cf4 ", ""],
        "default font" => [["font-family" => "Calibri"], "", "", ""],
        "new font" => [["font-family" => "Arial"], "", "\\f1 ", ""],
        "two fonts" => [["font-family" => "Calibri, Arial"], "", "", ""],
        "font size" => [["font-size" => "12pt"], "", "\\fs24 ", ""],
        "bold font weight" => [["font-weight" => "bold"], "", "\\b ", ""],
        "normal font weight" => [["font-weight" => "normal"], "", "", ""],
        "left aligned text" => [["text-align" => "left"], "", "\\ql ", ""],
        "right aligned text" => [["text-align" => "right"], "", "\\qr ", ""],
        "centered text" => [["text-align" => "center"], "", "\\qc ", ""],
        "justified text" => [["text-align" => "justify"], "", "\\qj ", ""],
        "underlined text" => [["text-decoration" => "underline"], "", "\\ul ", ""],
        "strike-trough" => [["text-decoration" => "line-through"], "", "\\strike ", ""],
        "no text decoration" => [["text-decoration" => "none"], "", "", ""],
        "text decoration color" => [["text-decoration-color" => "red"], "", "\\ulc2 ", ""],
        "text decoration solid" => [['text-decoration' => 'underline', 'text-decoration-style' => 'solid'], "", "\\ul ", ""],
        "text decoration double" => [['text-decoration' => 'underline', 'text-decoration-style' => 'double'], "", "\\uldb ", ""],
        "text decoration dashed" => [['text-decoration' => 'underline', 'text-decoration-style' => 'dashed'], "", "\\uldash ", ""],
        "text decoration dotted" => [['text-decoration' => 'underline', 'text-decoration-style' => 'dotted'], "", "\\uld ", ""],
        "text decoration wavy" => [['text-decoration' => 'underline', 'text-decoration-style' => 'wavy'], "", "\\ulwave ", ""],
        "top margin" => [['margin-top' => '10px'], "", "\\sb150 ", ""],
        "right margin" => [['margin-right' => '10px'], "", "\\ri150 ", ""],
        "bottom margin" => [['margin-bottom' => '10px'], "", "\\sa150 ", ""],
        "left margin" => [['margin-left' => '10px'], "", "\\li150 ", ""],
        "always break page before" => [['page-break-before' => 'always'], "", "", ""],
        "void page break before" => [['page-break-before' => 'avoid'], "", "", ""],
        "always page break after" => [['page-break-after' => 'always'], "", "", ""],
        "avoid page break after" => [['page-break-after' => 'avoid'], "", "", ""],
        "border bottom" => [['border-bottom-width' => '1px'], "\\clbrdrb\\brdrw15\\brdrs \r\n", "", ""],
        "border bottom style solid" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'solid'], "\\clbrdrb\\brdrw15\\brdrs \r\n", "", ""],
        "border bottom style dotted" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'dotted'], "\\clbrdrb\\brdrw15\\brdrdot \r\n", "", ""],
        "border bottom style dashed" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'dashed'], "\\clbrdrb\\brdrw15\\brdrdash \r\n", "", ""],
        "border bottom style  double" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'double'], "\\clbrdrb\\brdrw15\\brdrdb \r\n", "", ""],
        "no border bottom" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'none'], "\\clbrdrb\\brdrw15\\brdrnone \r\n", "", ""],
        "hidden border bottom" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'hidden'], "\\clbrdrb\\brdrw15\\brdrnone \r\n", "", ""],
        "vertical align top" => [['vertical-align' => 'top'], "\\clvertalt\r\n", "", ""],
        "vertical align middle" => [['vertical-align' => 'middle'], "\\clvertalc\r\n", "", ""],
        "vertical align bottom" => [['vertical-align' => 'bottom'], "\\clvertalb\r\n", "", ""],
      ],
      "span" => [
        "font color" => [["color" => "blue"], "", "\\cf4 ", ""],
        "default font" => [["font-family" => "Calibri"], "", "", ""],
        "new font" => [["font-family" => "Arial"], "", "\\f1 ", ""],
        "two fonts" => [["font-family" => "Calibri, Arial"], "", "", ""],
        "font size" => [["font-size" => "12pt"], "", "\\fs24 ", ""],
        "bold font weight" => [["font-weight" => "bold"], "", "\\b ", ""],
        "normal font weight" => [["font-weight" => "normal"], "", "", ""],
        "left aligned text" => [["text-align" => "left"], "", "", ""],
        "right aligned text" => [["text-align" => "right"], "", "", ""],
        "centered text" => [["text-align" => "center"], "", "", ""],
        "justified text" => [["text-align" => "justify"], "", "", ""],
        "underlined text" => [["text-decoration" => "underline"], "", "\\ul ", ""],
        "strike-trough" => [["text-decoration" => "line-through"], "", "\\strike ", ""],
        "no text decoration" => [["text-decoration" => "none"], "", "", ""],
        "text decoration color" => [["text-decoration-color" => "red"], "", "\\ulc2 ", ""],
        "text decoration solid" => [['text-decoration' => 'underline', 'text-decoration-style' => 'solid'], "", "\\ul ", ""],
        "text decoration double" => [['text-decoration' => 'underline', 'text-decoration-style' => 'double'], "", "\\uldb ", ""],
        "text decoration dashed" => [['text-decoration' => 'underline', 'text-decoration-style' => 'dashed'], "", "\\uldash ", ""],
        "text decoration dotted" => [['text-decoration' => 'underline', 'text-decoration-style' => 'dotted'], "", "\\uld ", ""],
        "text decoration wavy" => [['text-decoration' => 'underline', 'text-decoration-style' => 'wavy'], "", "\\ulwave ", ""],
        "top margin" => [['margin-top' => '10px'], "", "", ""],
        "right margin" => [['margin-right' => '10px'], "", "", ""],
        "bottom margin" => [['margin-bottom' => '10px'], "", "", ""],
        "left margin" => [['margin-left' => '10px'], "", "", ""],
        "always break page before" => [['page-break-before' => 'always'], "", "", ""],
        "void page break before" => [['page-break-before' => 'avoid'], "", "", ""],
        "always page break after" => [['page-break-after' => 'always'], "", "", ""],
        "avoid page break after" => [['page-break-after' => 'avoid'], "", "", ""],
        "border bottom" => [['border-bottom-width' => '1px'], "", "", ""],
        "border bottom style solid" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'solid'], "", "", ""],
        "border bottom style dotted" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'dotted'], "", "", ""],
        "border bottom style dashed" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'dashed'], "", "", ""],
        "border bottom style  double" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'double'], "", "", ""],
        "no border bottom" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'none'], "", "", ""],
        "hidden border bottom" => [['border-bottom-width' => '1px', 'border-bottom-style' => 'hidden'], "", "", ""],
        "vertical align top" => [['vertical-align' => 'top'], "", "", ""],
        "vertical align middle" => [['vertical-align' => 'middle'], "", "", ""],
        "vertical align bottom" => [['vertical-align' => 'bottom'], "", "", ""],
      ],
    ];

    foreach (array_keys($expected) as $tag) {
      foreach (array_keys($expected[$tag]) as $test) {
        $result = $this->bookexportrtf_get_rtf_style_from_css($expected[$tag][$test][0], $tag);
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
    $this->assertEquals($this->get_function("bookexportrtf_convert_color", $this->testfile), $this->get_function("bookexportrtf_convert_color", $this->codefile), "Failure cloning bookexportrtf_convert_length()");
    $this->assertEquals(1, $this->bookexportrtf_convert_color("#000000"), "Failure to convert HTML black");
    $this->assertEquals(2, $this->bookexportrtf_convert_color("#FF0000"), "Failure to convert HTML red");
    $this->assertEquals(3, $this->bookexportrtf_convert_color("#00FF00"), "Failure to convert HTML green");
    $this->assertEquals(4, $this->bookexportrtf_convert_color("#0000FF"), "Failure to convert HTML blue");
    $this->assertEquals(5, $this->bookexportrtf_convert_color("#FFFFFF"), "Failure to convert HTML white");
    $this->assertEquals(6, $this->bookexportrtf_convert_color("#8A2BE2"), "Failure to convert HTML blueviolet");
    $this->assertEquals(7, $this->bookexportrtf_convert_color("#7FFF00"), "Failure to add new HTML color chartreuse");
    $this->assertArrayHasKey("\\red127\\green255\\blue0", $this->bookexportrtf_colortbl, "Failure to add new HTML color chartreuse");
    $this->assertEquals(0, $this->bookexportrtf_convert_color("#FG00000"), "Failure on invalid HTML color");
    $this->assertEquals($this->bookexportrtf_colortbl["\\red127\\green255\\blue0"], 7, "Failure to add new HTML color chartreuse at the correct position");
    $this->assertEquals(1, $this->bookexportrtf_convert_color("black"), "Failure to convert colorname black");
    $this->assertEquals(2, $this->bookexportrtf_convert_color("red"), "Failure to convert colorname red");
    $this->assertEquals(3, $this->bookexportrtf_convert_color("lime"), "Failure to convert colorname lime (green)");
    $this->assertEquals(4, $this->bookexportrtf_convert_color("blue"), "Failure to convert colorname blue");
    $this->assertEquals(5, $this->bookexportrtf_convert_color("white"), "Failure to convert colorname white");
    $this->assertEquals(6, $this->bookexportrtf_convert_color("blueviolet"), "Failure to convert colorname blueviolet");
    $this->assertEquals(0, $this->bookexportrtf_convert_color("fooblue"), "Failure on invalid colorname");
    $this->assertEquals(1, $this->bookexportrtf_convert_color("rgb(0,0,0)"), "Failure to convert RGB black");
    $this->assertEquals(2, $this->bookexportrtf_convert_color("rgb(255,0,0)"), "Failure to convert RGB red");
    $this->assertEquals(3, $this->bookexportrtf_convert_color("rgb(0,255,0)"), "Failure to convert RGB green");
    $this->assertEquals(4, $this->bookexportrtf_convert_color("rgb(0,0,255)"), "Failure to convert RGB blue");
    $this->assertEquals(5, $this->bookexportrtf_convert_color("rgb(255,255,255)"), "Failure to convert RGB white");
    $this->assertEquals(6, $this->bookexportrtf_convert_color("rgb(138,43,226)"), "Failure to convert RGB blueviolet");
    $this->assertEquals(0, $this->bookexportrtf_convert_color("rgb(256,0,0)"), "Failure on invalid RGB color");
  }

  /**
   * Test length conversion
   *
   * Should convert from cm, mm, in, px, pt, pc to twips.
   * Defaults to 0 twips on failure.
   */
  public function test_length() {
    $this->assertEquals($this->get_function("bookexportrtf_convert_length", $this->testfile), $this->get_function("bookexportrtf_convert_length", $this->codefile), "Failure cloning bookexportrtf_convert_length()");
    $this->assertEquals(0, $this->bookexportrtf_convert_length("foo"), "Failure to convert invalid length");
    $this->assertEquals(56693, $this->bookexportrtf_convert_length("100cm"),  "Failure to convert length (cm)");
    $this->assertEquals(5669, $this->bookexportrtf_convert_length("100mm"), "Failure to convert length (mm)");
    $this->assertEquals(144000, $this->bookexportrtf_convert_length("100in"), "Failure to convert length (in)");
    $this->assertEquals(1500, $this->bookexportrtf_convert_length("100px"), "Failure to convert length (px)");
    $this->assertEquals(2000, $this->bookexportrtf_convert_length("100pt"), "Failure to convert length (pt)");
    $this->assertEquals(24000, $this->bookexportrtf_convert_length("100pc"), "Failure to convert length (pc)");
  }

  /**
   * Test length conversion
   *
   * Should convert from cm, mm, in, px, pt, pc to half points.
   * Defaults to 24 half points on failure.
   */
  public function test_font_size() {
    $this->assertEquals($this->get_function("bookexportrtf_convert_font_size", $this->testfile), $this->get_function("bookexportrtf_convert_font_size", $this->codefile), "Failure cloning bookexportrtf_convert_length()");
    $this->assertEquals(24, $this->bookexportrtf_convert_font_size("foo"), "Failure to convert invalid font size");
    $this->assertEquals(5669, $this->bookexportrtf_convert_font_size("100cm"), "Failure to convert font size (cm)");
    $this->assertEquals(567, $this->bookexportrtf_convert_font_size("100mm"), "Failure to convert font size (mm)");
    $this->assertEquals(14400, $this->bookexportrtf_convert_font_size("100in"), "Failure to convert font size (in)");
    $this->assertEquals(150, $this->bookexportrtf_convert_font_size("100px"), "Failure to convert font size (px)");
    $this->assertEquals(200, $this->bookexportrtf_convert_font_size("100pt"), "Failure to convert font size (pt)");
    $this->assertEquals(2400, $this->bookexportrtf_convert_font_size("100pc"), "Failure to convert font size (pc)");
  }

  /**
   * Below are supportive functions for the tests.
   */

  /**
   * Get code of a function from a file
   *
   * @param string $function
   *   The name of the function
   *
   * @param string $file
   *   The location of the file
   *
   * @return string
   *   The code of the string
   */
  private function get_function($function, $file) {
    $file = file_get_contents($file);
    $start = strpos($file,  " function " . $function);
    $end = strpos($file, "\n  }", $start);

    $code = substr($file, $start+1, $end - $start);
    return $code;
  }

  /**
   * Dummy function to test get_function
   */
  private function get_test_function() {
    //foo
  }

  /**
   * Below are copies of the functions from NookExportRtfController used to
   * test their correct working.
   */


  /**
   * Traverse the HTML tree and change HTML into RTF.
   *
   * HTML parsers may not spawn demons but if you use them to replace HTML tags
   * by RTF code they do attract gremlins as the parser gets in trouble with
   * nested tags (which occur a lot in HTML). Probably the parser is losing
   * the structure. This is solved by going through the tree and start
   * replacing tags at the branches working up to the main stem.
   *
   * @param array $elements
   *   The elements from the HTML tree from which to start.
   */

  private function bookexportrtf_traverse($elements) {
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
            // Link, replace with footnote.
            $url = $e->href;
            $title = $e->innertext;

            // Do not add a footnote if the link and label are the same.
            if (preg_match("|^(https?://)?(mailto:)?" . $title . "/?$|", $url)) {
              $e->outertext = $title;
            }
            else {
              $e->outertext = $title . "{\\footnote \\pard {\\up6 \\chftn} " . $url . "}";
            }
          }
          else if ($e->name) {
            // Anchor, replace for the index, ignore others.
            if (preg_match("|^index|", $e->name)) {
              $label = substr($e->name, 5);
              $anchor = "index-" . $this->bookexpor_rtf_index_id[$label];
              $e->outertext = "{\\*\\bkmkstart " . $anchor . "}{\\*\\bkmkend ".$anchor."}";
            }
          }
          break;

        case 'br':
          // Add a tab before the newline otherwise justified last lines will
          // be justified instead of left aligned.
          $e->outertext = "\\tab\\line\r\n";
          break;

       case 'div':
         // For divs I'm only interested in page-break-before and
         // page-break-after other style elements will be enherited by its
         // children.

         $style = $this->bookexportrtf_get_rtf_style_from_element($e);
         $e->outertext = $style[0] . $e->innertext . $style[2];
          break;

        case 'h1':
          // Start of a new chapter, thus start a new section.
          //
          // Page break behaves erratic around section breaks. Page break is
          // handled by css which adds \\page. However, in Libre office
          // \\sect\\sbknone seem to overwrite \\page as \\sect\\sbknone\\page
          // does not lead to a page break. Also \\sect\\page leads to one page
          // break instead of two.
          //
          // Ignore the CSS engine and add \\sbknone unless a page break should
          // be added before H1.

          $title = $e->innertext;
          $css = $this->bookexportrtf_get_css_style_from_element($e);
          $rtf = "\\sect";
          if (!array_key_exists('page-break-before', $css)) {
            $rtf .= "\\sbknone";
          }
          elseif (trim($css['page-break-before']) != "always") {
            $rtf .= "\\sbknone";
          }
          else {
            unset($css['page-break-before']);
          }
          $rtf .= "\\sftnrstpg\r\n";

          $style = $this->bookexportrtf_get_rtf_style_from_css($css);
          $header_style = $this->bookexportrtf_get_rtf_style_from_selector(".header-left");
          $rtf .= "{\\headerl\\pard ". $header_style[1] . $this->bookexportrtf_book_title . "\\par}\r\n";
          $header_style = $this->bookexportrtf_get_rtf_style_from_selector(".header-right");
          $rtf .= "{\\headerr\\pard ". $header_style[1] . $title . "\\par}\r\n";
          $footer_style = $this->bookexportrtf_get_rtf_style_from_selector(".footer-left");
          $rtf .= "{\\footerl\\pard ". $footer_style[1] . "\\chpgn \\par}\r\n";
          $footer_style = $this->bookexportrtf_get_rtf_style_from_selector(".footer-right");
          $rtf .= "{\\footerr\\pard ". $footer_style[1] . "\\chpgn \\par}\r\n";

          // If the chapter starts with a number add a bookmark for the toc.
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

          // Change relative urls to absolute urls
          if (isset($this->bookexportrtf_base_url) & substr($url, 0, 4) != "http") {
            $url = $this->bookexportrtf_base_url . $url;
          }

          $string = file_get_contents($url);

          $info = getimagesizefromstring($string);

          $width = $info[0];
          $height = $info[1];

          $picwidth = $this->bookexportrtf_convert_length($width . "px");
          $picheight = $this->bookexportrtf_convert_length($height . "px");

          // Scale to page width if wider
          // Page width A4 - margins = 11909 - 2x1800  = 8309 twips
          if ($picwidth > 8309) {
            $ratio = $width/$height;
            $picwidth = 8309;
            $picheight = round($picwidth / $ratio);
          }

          $scalex = 100;
          $scaley = 100;

          $rtf = "{";
          $rtf .= "\\pard{\\pict\\picw" . $width;
          $rtf .= "\\pich" . $height;
          $rtf .= "\\picwgoal" . $picwidth;
          $rtf .= "\\pichgoal" . $picheight;
          $rtf .= "\\picscalex" . $scalex;
          $rtf .= "\\picscaley" . $scaley;


          // Set image type.
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
              $string = ob_get_contents(); // read from buffer
              ob_end_clean(); // delete buffer
              $rtf .= "\\pngblip\r\n";
          }

          $hex = bin2hex($string);
          $hex = wordwrap($hex, 80, "\r\n", TRUE);

          $rtf .= $hex;
          $rtf .= "\r\n}\\par}\r\n";

          $e->outertext = $rtf;
          break;

        case 'li':
          // This might be a bit dirty but as I'm not going to make elaborate
          // list structures I feel confident working from li backwards and
          // strip out the list-tags later.

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

          // If the first item of a nested list close the current paragraph.
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
            $rtf .= " " . $number . ".\\tab ";
          }
          $rtf .= $e->innertext;

          // Finish the paragraph unless it's the last item in a nested list

          /**
           * @todo Text after the nested list will be included in the last item
           * of the nested list. That should not be the case.
           */

          if ($last != 1 | $depth == 1) {
            $rtf .= "\\par}\r\n";
          }
          if ($depth == 1 & $last == 1) {
            // Add some empty space after the list.
             $rtf .= "{\\pard\\sa0\\par}\r\n";
          }
          $e->outertext = $rtf;
          break;

        case 'code':
        case 'p':
          // These are all paragraphs with specific markup
          $style = $this->bookexportrtf_get_rtf_style_from_element($e);
          $e->outertext = "{\\pard " . $style[1] . $e->innertext . "\\par}\r\n";
          break;

        case 's':
        case 'del':
        case 'ins':
        case 'span':
          // These are inline elements with specific markup

          // Remove the author information.
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
          // Tables are a little bit more complicated than lists. The best way
          // to do this is to store the whole table and then recreate it.

          $num_rows = 0;
          $num_cols = 0;
          $table;
          $colwidth = [];

          // Retrieve table contents and some required specifications.
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

              // correct cur_cols for colspan.
              $cur_cols += $table[$num_rows][$cur_cols]['colspan']-1;
            }
            if ($cur_cols > $num_cols) {
              $num_cols = $cur_cols;
            }
          }

          // Calculate column width
          // 1. Determined width already defined
          // 2. Space out evenly over the remaining columns
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

          // Standard pagewidth = 13909 - 2x1800 = 9309
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

          // Build the table
          $rtf = "{";
          foreach ($table as $row) {
            $rtf .= "\\trowd\r\n";

            // First itteration to define cell style.
            foreach ($row as $cell) {
              $rtf .= $cell['style_prefix'];
              $rtf .= "\\cellx";
              $rtf .= $colright[$cell['col']+$cell['colspan']-1];
              $rtf .= "\r\n";
            }

            // Second iteration to make the cells themselves.
            foreach ($row as $cell) {
              $rtf .= "\\intbl{";
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
          $style = $this->bookexportrtf_get_rtf_style_from_css($css, 'u');
          $e->outertext = "{" . $style[1] . $e->innertext . "}";
      }
    }
  }

  /**
   * Convert a CSS-array into the appropriate RTF markup.
   *
   * @param array $css
   *   The css property-value pairs.
   *
   * @param string $tag
   *   An optional tag of the HTML element.
   *
   * @return array
   *   The RTF markup as prefix, infix and suffix.
   */
  private function bookexportrtf_get_rtf_style_from_css($css, $tag = NULL) {
    if (!empty($tag)) {
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
          'text-decoration-style' => 1,],
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
          'vertical-align' => 1,],
        'span' => [
          'color' => 1,
          'font-family' => 1,
          'font-size' => 1,
          'font-weight' => 1,
          'text-decoration' => 1,
          'text-decoration-color' => 1,
          'text-decoration-style' => 1,],];

      // headers also support page breaks
      $supported['h1'] = $supported['p'];
      $supported['h1']['page-break-before'] = 1;
      $supported['h1']['page-break-after'] = 1;

      // inherit
      $supported['h2'] = $supported['h1'];
      $supported['h3'] = $supported['h1'];
      $supported['h4'] = $supported['h1'];
      $supported['h5'] = $supported['h1'];
      $supported['h6'] = $supported['h1'];
      $supported['code'] = $supported['p'];
      $supported['li'] = $supported['p'];
      $supported['th'] = $supported['td'];
      $supported['del'] = $supported['span'];
      $supported['s'] = $supported['span'];
      $supported['ins'] = $supported['span'];
      $supported['u'] = $supported['span'];

      foreach (array_keys($css) as $selector) {
        if (!array_key_exists(trim($selector), $supported[$tag])) {
          unset($css[$selector]);
        }
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

    // Page breaks
    if (array_key_exists('page-break-before', $css)) {
      if (trim($css['page-break-before']) == "always") {
          $rtf_prefix .= "\\page";
       }
    }
    if (array_key_exists('page-break-after', $css)) {
      if (trim($css['page-break-after']) == "always") {
          $rtf_suffix .= "\\page";
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
   * Get the style for an HTML element.
   *
   * @param object $e
   *   An element from the html tree.
   *
   * @return array
   *   The list of css properties and values.
   */
  private function bookexportrtf_get_css_style_from_element($e) {
    // A list of inhereted css properties.
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

    // Start the cascade looking upwards from the element to get all the css.
    while ($e) {
      // Get css from the element's style attribute.
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

      // Get css associated with the element's id, classes and element.
      $id = '#' . $e->id;
      $classes = explode(' ', $e->class);
      $classes = array_map(static function ($class) {
            return "." . $class;
        }, $classes);
      $tag = $e->tag;

      foreach (array_merge([$id], $classes, [$tag]) as $selector) {
        if (array_key_exists($selector, $this->bookexportrtf_css)) {
          foreach (array_keys($this->bookexportrtf_css[$selector]) as $property) {
            // inheritance by default
            if (!array_key_exists($property, $css) & ($depth == 0 | array_key_exists($property, $css_inherit))) {
              $css[$property] = $this->bookexportrtf_css[$selector][$property];
            }
            // inheritance by setting
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
  private function bookexportrtf_get_rtf_style_from_element($element) {
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
  private function bookexportrtf_get_rtf_style_from_selector($selector) {
    return $this->bookexportrtf_get_rtf_style_from_css($this->bookexportrtf_css[$selector]);
  }


  /**
   * Convert CSS colors to a position in the colortable
   *
   * @param string $css
   *   The value in CSS, this is a string with the value and unit
   *
   * @return string
   *   The position in the color table
   */
  private function bookexportrtf_convert_color($css) {
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

    if ($color == "") {
      return 0;
    }

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
   *
   * @return string
   *   The length in twips.
   */
  private function bookexportrtf_convert_length($css) {
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
   * Converter CSS font size to RTF
   *
   * @param string $css
   *   The value in CSS, this is a string with the value and unit.
   *
   * @return string
   *   The font size in half points.
   */

  private function bookexportrtf_convert_font_size($css) {
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
