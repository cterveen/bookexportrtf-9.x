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
   * Test getting the style from an css array
   */
  public function test_get_rtf_from_css() {
    $this->assertEquals($this->get_function("bookexportrtf_get_rtf_style_from_css", $this->testfile), $this->get_function("bookexportrtf_get_rtf_style_from_css", $this->codefile), "Failure cloning bookexportrtf_convert_length()");

    /**
     * Test all supported properties
     *
     * Although width is supported for table cells it is not handled by
     * bookexportrtf_get_rtf_style_from_css()
     */
    $result = $this->bookexportrtf_get_rtf_style_from_css(['color' => 'blue'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for font color of p");
    $this->assertEquals("\\cf4 ", $result[1], "Failure setting style infix for font color of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for font color of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['font-family' => 'Calibri'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for default font of p");
    $this->assertEquals("", $result[1], "Failure setting style infix for default font of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for default font of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['font-family' => 'Arial'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for new font of p");
    $this->assertEquals("\\f1 ", $result[1], "Failure setting style infix for new font of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for new font of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['font-family' => 'Calibri, Arial'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for two fonts of p");
    $this->assertEquals("", $result[1], "Failure setting style infix for two font of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for two font of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['font-size' => '12pt'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for font size of p");
    $this->assertEquals("\\fs24 ", $result[1], "Failure setting style infix for font size of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for font size of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['font-weight' => 'bold'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for bold font weight of p");
    $this->assertEquals("\\b ", $result[1], "Failure setting style infix for bold font weight of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for bold font of weight p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['font-weight' => 'normal'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for normal font weight of p");
    $this->assertEquals("", $result[1], "Failure setting style infix for normal font weight of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for normal font weight of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'left'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for text-align: left of p");
    $this->assertEquals("\\ql ", $result[1], "Failure setting style infix for text-align: left of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for text-align: left of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'right'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for text-align: right of p");
    $this->assertEquals("\\qr ", $result[1], "Failure setting style infix for text-align: right of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for text-align: right of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'center'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for text-align: center of p");
    $this->assertEquals("\\qc ", $result[1], "Failure setting style infix for text-align: center of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for text-align: center of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'justify'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for text-align: justify of p");
    $this->assertEquals("\\qj ", $result[1], "Failure setting style infix for text-align: justify of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for text-align: justify of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for underline of p");
    $this->assertEquals("\\ul ", $result[1], "Failure setting style infix for underline of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for underline of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'line-through'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for line-through of p");
    $this->assertEquals("\\strike ", $result[1], "Failure setting style infix for line-through of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for line-through of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'none'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for no text decoration of p");
    $this->assertEquals("", $result[1], "Failure setting style infix for no text decoration of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for no text decoration of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration-color' => 'red'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for underline color of p");
    $this->assertEquals("\\ulc2 ", $result[1], "Failure setting style infix for underline color of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for underline color of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for underline of p");
    $this->assertEquals("\\ul ", $result[1], "Failure setting style infix for underline of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for underline of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'solid'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for underline style solid of p");
    $this->assertEquals("\\ul ", $result[1], "Failure setting style infix for underline style solid of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for underline style solid of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration-style' => 'wavy'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for underline style without underline of p");
    $this->assertEquals("", $result[1], "Failure setting style infix for underline style without underline of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for underline style without underline of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'double'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for underline style double of p");
    $this->assertEquals("\\uldb ", $result[1], "Failure setting style infix for underline style double of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for underline style double of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'dashed'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for underline style dashed of p");
    $this->assertEquals("\\uldash ", $result[1], "Failure setting style infix for underline style dashed  of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for underline style dashed  of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'dotted'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for underline style dotted of p");
    $this->assertEquals("\\uld ", $result[1], "Failure setting style infix for underline style dotted of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for underline style dotted of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'wavy'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for underline style wavy of p");
    $this->assertEquals("\\ulwave ", $result[1], "Failure setting style infix for underline style wavy  of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for underline style wavy of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-top' => '10px'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for margin-top of p");
    $this->assertEquals("\\sb150 ", $result[1], "Failure setting style infix for margin-top of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for margin-top of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-right' => '10px'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for margin-right of p");
    $this->assertEquals("\\ri150 ", $result[1], "Failure setting style infix for margin-right of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for margin-right of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-bottom' => '10px'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for margin-bottom of p");
    $this->assertEquals("\\sa150 ", $result[1], "Failure setting style infix for margin-bottom of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for margin-bottom of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-left' => '10px'], "p");
    $this->assertEquals("", $result[0], "Failure setting style prefix for margin-left of p");
    $this->assertEquals("\\li150 ", $result[1], "Failure setting style infix for margin-left of p");
    $this->assertEquals("", $result[2], "Failure setting style suffix for margin-left of p");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-before' => 'always'], "h1");
    $this->assertEquals("\\page\r\n", $result[0], "Failure setting style prefix for page-break-before: always of h1");
    $this->assertEquals("", $result[1], "Failure setting style infix for age-break-before: always of h1");
    $this->assertEquals("", $result[2], "Failure setting style suffix for age-break-before: always of h1");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-before' => 'avoid'], "h1");
    $this->assertEquals("", $result[0], "Failure setting style prefix for page-break-before: avoid of h1");
    $this->assertEquals("", $result[1], "Failure setting style infix for age-break-before: avoid of h1");
    $this->assertEquals("", $result[2], "Failure setting style suffix for age-break-before: avoid of h1");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-after' => 'always'], "h1");
    $this->assertEquals("", $result[0], "Failure setting style prefix for page-break-after: always of h1");
    $this->assertEquals("", $result[1], "Failure setting style infix for age-break-after: always of h1");
    $this->assertEquals("\\page\r\n", $result[2], "Failure setting style suffix for age-break-after: always of h1");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-after' => 'avoid'], "h1");
    $this->assertEquals("", $result[0], "Failure setting style prefix for page-break-after: avoid of h1");
    $this->assertEquals("", $result[1], "Failure setting style infix for age-break-after: avoid of h1");
    $this->assertEquals("", $result[2], "Failure setting style suffix for age-break-after: avoid of h1");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px'], "td");
    $this->assertEquals("\\clbrdrb\\brdrw15\\brdrs \r\n", $result[0], "Failure setting style prefix for border-bottom-width of td");
    $this->assertEquals("", $result[1], "Failure setting style infix for border-bottom-width of td");
    $this->assertEquals("", $result[2], "Failure setting style suffix for border-bottom-width of td");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'solid'], "td");
    $this->assertEquals("\\clbrdrb\\brdrw15\\brdrs \r\n", $result[0], "Failure setting style prefix for border-bottom-style: solid of td");
    $this->assertEquals("", $result[1], "Failure setting style infix for border-bottom-style: solid of td");
    $this->assertEquals("", $result[2], "Failure setting style suffix for border-bottom-style: solid of td");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'dotted'], "td");
    $this->assertEquals("\\clbrdrb\\brdrw15\\brdrdot \r\n", $result[0], "Failure setting style prefix for border-bottom-style: dotted of td");
    $this->assertEquals("", $result[1], "Failure setting style infix for border-bottom-style: dotted of td");
    $this->assertEquals("", $result[2], "Failure setting style suffix for border-bottom-style: dotted of td");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'dashed'], "td");
    $this->assertEquals("\\clbrdrb\\brdrw15\\brdrdash \r\n", $result[0], "Failure setting style prefix for border-bottom-style: dashed of td");
    $this->assertEquals("", $result[1], "Failure setting style infix for border-bottom-style: dashed of td");
    $this->assertEquals("", $result[2], "Failure setting style suffix for border-bottom-style: dashed of td");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'double'], "td");
    $this->assertEquals("\\clbrdrb\\brdrw15\\brdrdb \r\n", $result[0], "Failure setting style prefix for border-bottom-style: double of td");
    $this->assertEquals("", $result[1], "Failure setting style infix for border-bottom-style: double of td");
    $this->assertEquals("", $result[2], "Failure setting style suffix for border-bottom-style: double of td");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'none'], "td");
    $this->assertEquals("\\clbrdrb\\brdrw15\\brdrnone \r\n", $result[0], "Failure setting style prefix for border-bottom-style: none of td");
    $this->assertEquals("", $result[1], "Failure setting style infix for border-bottom-style: none of td");
    $this->assertEquals("", $result[2], "Failure setting style suffix for border-bottom-style: none of td");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'hidden'], "td");
    $this->assertEquals("\\clbrdrb\\brdrw15\\brdrnone \r\n", $result[0], "Failure setting style prefix for border-bottom-style: hidden of td");
    $this->assertEquals("", $result[1], "Failure setting style infix for border-bottom-style: hidden of td");
    $this->assertEquals("", $result[2], "Failure setting style suffix for border-bottom-style: hidden of td");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['vertical-align' => 'top'], "td");
    $this->assertEquals("\\clvertalt\r\n", $result[0], "Failure setting style prefix for vertical-align: top of td");
    $this->assertEquals("", $result[1], "Failure setting style infix for vertical-align: top of td");
    $this->assertEquals("", $result[2], "Failure setting style suffix for vertical-align: top of td");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['vertical-align' => 'middle'], "td");
    $this->assertEquals("\\clvertalc\r\n", $result[0], "Failure setting style prefix for vertical-align: middle of td");
    $this->assertEquals("", $result[1], "Failure setting style infix for vertical-align: middle of td");
    $this->assertEquals("", $result[2], "Failure setting style suffix for vertical-align: middle of td");

    $result = $this->bookexportrtf_get_rtf_style_from_css(['vertical-align' => 'bottom'], "td");
    $this->assertEquals("\\clvertalb\r\n", $result[0], "Failure setting style prefix for vertical-align: bottom of td");
    $this->assertEquals("", $result[1], "Failure setting style infix for vertical-align: bottom of td");
    $this->assertEquals("", $result[2], "Failure setting style suffix for vertical-align: bottom of td");

    // Now prefix, infix and suffix are correct let's see if everything is assigned correctly
    foreach (['div'] as $tag) {
      $result = $this->bookexportrtf_get_rtf_style_from_css(['color' => 'blue'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for new font of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-family' => 'Calibri'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for default font of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-family' => 'Arial'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for new font of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-family' => 'Calibri, Arial'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for two font of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-size' => '12pt'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for font size of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-weight' => 'bold'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for bold font weight of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-weight' => 'normal'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for normal font weight of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'left'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for text-align: left of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'right'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for text-align: right of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'center'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for text-align: center of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'justify'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for text-align: justify of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for underline of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'line-through'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for line-through of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'none'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for no text decoration of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration-color' => 'red'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for underline color of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for underline of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'solid'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for underline style solid of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration-style' => 'wavy'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for underline style without underline of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'double'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for underline style double of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'dashed'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for underline style dashed  of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'dotted'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for underline style dotted of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'wavy'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for underline style wavy  of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-top' => '10px'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for margin-top of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-right' => '10px'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for margin-right of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-bottom' => '10px'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for margin-bottom of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-left' => '10px'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for margin-left of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-before' => 'always'], $tag);
      $this->assertEquals("\\page\r\n", $result[0], "Failure setting style prefix for page-break-before: always of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-before' => 'avoid'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for page-break-before: avoid of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-after' => 'always'], $tag);
      $this->assertEquals("\\page\r\n", $result[2], "Failure setting style suffix for age-break-after: always of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-after' => 'avoid'], $tag);
      $this->assertEquals("", $result[2], "Failure setting style suffix for age-break-after: avoid of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-width of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'solid'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: solid of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'dotted'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: dotted of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'dashed'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: dashed of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'double'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: double of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'none'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: none of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'hidden'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: hidden of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['vertical-align' => 'top'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for vertical-align: top of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['vertical-align' => 'middle'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for vertical-align: middle of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['vertical-align' => 'bottom'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for vertical-align: bottom of " . $tag);
    }

    foreach (['code', 'li', 'p'] as $tag) {
      $result = $this->bookexportrtf_get_rtf_style_from_css(['color' => 'blue'], $tag);
      $this->assertEquals("\\cf4 ", $result[1], "Failure setting style infix for new font of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-family' => 'Calibri'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for default font of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-family' => 'Arial'], $tag);
      $this->assertEquals("\\f1 ", $result[1], "Failure setting style infix for new font of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-family' => 'Calibri, Arial'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for two font of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-size' => '12pt'], $tag);
      $this->assertEquals("\\fs24 ", $result[1], "Failure setting style infix for font size of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-weight' => 'bold'], $tag);
      $this->assertEquals("\\b ", $result[1], "Failure setting style infix for bold font weight of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-weight' => 'normal'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for normal font weight of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'left'], $tag);
      $this->assertEquals("\\ql ", $result[1], "Failure setting style infix for text-align: left of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'right'], $tag);
      $this->assertEquals("\\qr ", $result[1], "Failure setting style infix for text-align: right of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'center'], $tag);
      $this->assertEquals("\\qc ", $result[1], "Failure setting style infix for text-align: center of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'justify'], $tag);
      $this->assertEquals("\\qj ", $result[1], "Failure setting style infix for text-align: justify of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline'], $tag);
      $this->assertEquals("\\ul ", $result[1], "Failure setting style infix for underline of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'line-through'], $tag);
      $this->assertEquals("\\strike ", $result[1], "Failure setting style infix for line-through of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'none'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for no text decoration of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration-color' => 'red'], $tag);
      $this->assertEquals("\\ulc2 ", $result[1], "Failure setting style infix for underline color of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline'], $tag);
      $this->assertEquals("\\ul ", $result[1], "Failure setting style infix for underline of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'solid'], $tag);
      $this->assertEquals("\\ul ", $result[1], "Failure setting style infix for underline style solid of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration-style' => 'wavy'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for underline style without underline of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'double'], $tag);
      $this->assertEquals("\\uldb ", $result[1], "Failure setting style infix for underline style double of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'dashed'], $tag);
      $this->assertEquals("\\uldash ", $result[1], "Failure setting style infix for underline style dashed  of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'dotted'], $tag);
      $this->assertEquals("\\uld ", $result[1], "Failure setting style infix for underline style dotted of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'wavy'], $tag);
      $this->assertEquals("\\ulwave ", $result[1], "Failure setting style infix for underline style wavy  of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-top' => '10px'], $tag);
      $this->assertEquals("\\sb150 ", $result[1], "Failure setting style infix for margin-top of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-right' => '10px'], $tag);
      $this->assertEquals("\\ri150 ", $result[1], "Failure setting style infix for margin-right of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-bottom' => '10px'], $tag);
      $this->assertEquals("\\sa150 ", $result[1], "Failure setting style infix for margin-bottom of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-left' => '10px'], $tag);
      $this->assertEquals("\\li150 ", $result[1], "Failure setting style infix for margin-left of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-before' => 'always'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for page-break-before: always of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-before' => 'avoid'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for page-break-before: avoid of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-after' => 'always'], $tag);
      $this->assertEquals("", $result[2], "Failure setting style suffix for age-break-after: always of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-after' => 'avoid'], $tag);
      $this->assertEquals("", $result[2], "Failure setting style suffix for age-break-after: avoid of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-width of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'solid'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: solid of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'dotted'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: dotted of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'dashed'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: dashed of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'double'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: double of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'none'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: none of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'hidden'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: hidden of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['vertical-align' => 'top'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for vertical-align: top of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['vertical-align' => 'middle'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for vertical-align: middle of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['vertical-align' => 'bottom'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for vertical-align: bottom of " . $tag);
    }

    foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
      $result = $this->bookexportrtf_get_rtf_style_from_css(['color' => 'blue'], $tag);
      $this->assertEquals("\\cf4 ", $result[1], "Failure setting style infix for new font of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-family' => 'Calibri'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for default font of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-family' => 'Arial'], $tag);
      $this->assertEquals("\\f1 ", $result[1], "Failure setting style infix for new font of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-family' => 'Calibri, Arial'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for two font of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-size' => '12pt'], $tag);
      $this->assertEquals("\\fs24 ", $result[1], "Failure setting style infix for font size of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-weight' => 'bold'], $tag);
      $this->assertEquals("\\b ", $result[1], "Failure setting style infix for bold font weight of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-weight' => 'normal'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for normal font weight of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'left'], $tag);
      $this->assertEquals("\\ql ", $result[1], "Failure setting style infix for text-align: left of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'right'], $tag);
      $this->assertEquals("\\qr ", $result[1], "Failure setting style infix for text-align: right of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'center'], $tag);
      $this->assertEquals("\\qc ", $result[1], "Failure setting style infix for text-align: center of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'justify'], $tag);
      $this->assertEquals("\\qj ", $result[1], "Failure setting style infix for text-align: justify of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline'], $tag);
      $this->assertEquals("\\ul ", $result[1], "Failure setting style infix for underline of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'line-through'], $tag);
      $this->assertEquals("\\strike ", $result[1], "Failure setting style infix for line-through of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'none'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for no text decoration of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration-color' => 'red'], $tag);
      $this->assertEquals("\\ulc2 ", $result[1], "Failure setting style infix for underline color of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline'], $tag);
      $this->assertEquals("\\ul ", $result[1], "Failure setting style infix for underline of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'solid'], $tag);
      $this->assertEquals("\\ul ", $result[1], "Failure setting style infix for underline style solid of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration-style' => 'wavy'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for underline style without underline of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'double'], $tag);
      $this->assertEquals("\\uldb ", $result[1], "Failure setting style infix for underline style double of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'dashed'], $tag);
      $this->assertEquals("\\uldash ", $result[1], "Failure setting style infix for underline style dashed  of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'dotted'], $tag);
      $this->assertEquals("\\uld ", $result[1], "Failure setting style infix for underline style dotted of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'wavy'], $tag);
      $this->assertEquals("\\ulwave ", $result[1], "Failure setting style infix for underline style wavy  of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-top' => '10px'], $tag);
      $this->assertEquals("\\sb150 ", $result[1], "Failure setting style infix for margin-top of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-right' => '10px'], $tag);
      $this->assertEquals("\\ri150 ", $result[1], "Failure setting style infix for margin-right of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-bottom' => '10px'], $tag);
      $this->assertEquals("\\sa150 ", $result[1], "Failure setting style infix for margin-bottom of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-left' => '10px'], $tag);
      $this->assertEquals("\\li150 ", $result[1], "Failure setting style infix for margin-left of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-before' => 'always'], $tag);
      $this->assertEquals("\\page\r\n", $result[0], "Failure setting style prefix for page-break-before: always of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-before' => 'avoid'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for page-break-before: avoid of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-after' => 'always'], $tag);
      $this->assertEquals("\\page\r\n", $result[2], "Failure setting style suffix for age-break-after: always of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-after' => 'avoid'], $tag);
      $this->assertEquals("", $result[2], "Failure setting style suffix for age-break-after: avoid of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-width of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'solid'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: solid of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'dotted'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: dotted of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'dashed'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: dashed of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'double'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: double of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'none'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: none of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'hidden'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: hidden of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['vertical-align' => 'top'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for vertical-align: top of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['vertical-align' => 'middle'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for vertical-align: middle of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['vertical-align' => 'bottom'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for vertical-align: bottom of " . $tag);
    }

    foreach (['del', 'ins', 's', 'span', 'u'] as $tag) {
      $result = $this->bookexportrtf_get_rtf_style_from_css(['color' => 'blue'], $tag);
      $this->assertEquals("\\cf4 ", $result[1], "Failure setting style infix for new font of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-family' => 'Calibri'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for default font of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-family' => 'Arial'], $tag);
      $this->assertEquals("\\f1 ", $result[1], "Failure setting style infix for new font of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-family' => 'Calibri, Arial'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for two font of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-size' => '12pt'], $tag);
      $this->assertEquals("\\fs24 ", $result[1], "Failure setting style infix for font size of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-weight' => 'bold'], $tag);
      $this->assertEquals("\\b ", $result[1], "Failure setting style infix for bold font weight of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-weight' => 'normal'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for normal font weight of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'left'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for text-align: left of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'right'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for text-align: right of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'center'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for text-align: center of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'justify'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for text-align: justify of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline'], $tag);
      $this->assertEquals("\\ul ", $result[1], "Failure setting style infix for underline of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'line-through'], $tag);
      $this->assertEquals("\\strike ", $result[1], "Failure setting style infix for line-through of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'none'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for no text decoration of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration-color' => 'red'], $tag);
      $this->assertEquals("\\ulc2 ", $result[1], "Failure setting style infix for underline color of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline'], $tag);
      $this->assertEquals("\\ul ", $result[1], "Failure setting style infix for underline of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'solid'], $tag);
      $this->assertEquals("\\ul ", $result[1], "Failure setting style infix for underline style solid of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration-style' => 'wavy'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for underline style without underline of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'double'], $tag);
      $this->assertEquals("\\uldb ", $result[1], "Failure setting style infix for underline style double of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'dashed'], $tag);
      $this->assertEquals("\\uldash ", $result[1], "Failure setting style infix for underline style dashed  of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'dotted'], $tag);
      $this->assertEquals("\\uld ", $result[1], "Failure setting style infix for underline style dotted of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'wavy'], $tag);
      $this->assertEquals("\\ulwave ", $result[1], "Failure setting style infix for underline style wavy  of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-top' => '10px'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for margin-top of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-right' => '10px'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for margin-right of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-bottom' => '10px'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for margin-bottom of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-left' => '10px'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for margin-left of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-before' => 'always'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for page-break-before: always of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-before' => 'avoid'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for page-break-before: avoid of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-after' => 'always'], $tag);
      $this->assertEquals("", $result[2], "Failure setting style suffix for age-break-after: always of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-after' => 'avoid'], $tag);
      $this->assertEquals("", $result[2], "Failure setting style suffix for age-break-after: avoid of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-width of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'solid'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: solid of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'dotted'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: dotted of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'dashed'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: dashed of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'double'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: double of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'none'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: none of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'hidden'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for border-bottom-style: hidden of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['vertical-align' => 'top'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for vertical-align: top of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['vertical-align' => 'middle'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for vertical-align: middle of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['vertical-align' => 'bottom'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for vertical-align: bottom of " . $tag);
    }

    foreach (['td', 'th'] as $tag) {
      $result = $this->bookexportrtf_get_rtf_style_from_css(['color' => 'blue'], $tag);
      $this->assertEquals("\\cf4 ", $result[1], "Failure setting style infix for new font of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-family' => 'Calibri'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for default font of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-family' => 'Arial'], $tag);
      $this->assertEquals("\\f1 ", $result[1], "Failure setting style infix for new font of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-family' => 'Calibri, Arial'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for two font of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-size' => '12pt'], $tag);
      $this->assertEquals("\\fs24 ", $result[1], "Failure setting style infix for font size of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-weight' => 'bold'], $tag);
      $this->assertEquals("\\b ", $result[1], "Failure setting style infix for bold font weight of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['font-weight' => 'normal'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for normal font weight of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'left'], $tag);
      $this->assertEquals("\\ql ", $result[1], "Failure setting style infix for text-align: left of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'right'], $tag);
      $this->assertEquals("\\qr ", $result[1], "Failure setting style infix for text-align: right of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'center'], $tag);
      $this->assertEquals("\\qc ", $result[1], "Failure setting style infix for text-align: center of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-align' => 'justify'], $tag);
      $this->assertEquals("\\qj ", $result[1], "Failure setting style infix for text-align: justify of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline'], $tag);
      $this->assertEquals("\\ul ", $result[1], "Failure setting style infix for underline of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'line-through'], $tag);
      $this->assertEquals("\\strike ", $result[1], "Failure setting style infix for line-through of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'none'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for no text decoration of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration-color' => 'red'], $tag);
      $this->assertEquals("\\ulc2 ", $result[1], "Failure setting style infix for underline color of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline'], $tag);
      $this->assertEquals("\\ul ", $result[1], "Failure setting style infix for underline of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'solid'], $tag);
      $this->assertEquals("\\ul ", $result[1], "Failure setting style infix for underline style solid of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration-style' => 'wavy'], $tag);
      $this->assertEquals("", $result[1], "Failure setting style infix for underline style without underline of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'double'], $tag);
      $this->assertEquals("\\uldb ", $result[1], "Failure setting style infix for underline style double of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'dashed'], $tag);
      $this->assertEquals("\\uldash ", $result[1], "Failure setting style infix for underline style dashed  of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'dotted'], $tag);
      $this->assertEquals("\\uld ", $result[1], "Failure setting style infix for underline style dotted of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['text-decoration' => 'underline', 'text-decoration-style' => 'wavy'], $tag);
      $this->assertEquals("\\ulwave ", $result[1], "Failure setting style infix for underline style wavy  of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-top' => '10px'], $tag);
      $this->assertEquals("\\sb150 ", $result[1], "Failure setting style infix for margin-top of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-right' => '10px'], $tag);
      $this->assertEquals("\\ri150 ", $result[1], "Failure setting style infix for margin-right of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-bottom' => '10px'], $tag);
      $this->assertEquals("\\sa150 ", $result[1], "Failure setting style infix for margin-bottom of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['margin-left' => '10px'], $tag);
      $this->assertEquals("\\li150 ", $result[1], "Failure setting style infix for margin-left of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-before' => 'always'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for page-break-before: always of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-before' => 'avoid'], $tag);
      $this->assertEquals("", $result[0], "Failure setting style prefix for page-break-before: avoid of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-after' => 'always'], $tag);
      $this->assertEquals("", $result[2], "Failure setting style suffix for age-break-after: always of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['page-break-after' => 'avoid'], $tag);
      $this->assertEquals("", $result[2], "Failure setting style suffix for age-break-after: avoid of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px'], $tag);
      $this->assertEquals("\\clbrdrb\\brdrw15\\brdrs \r\n", $result[0], "Failure setting style prefix for border-bottom-width of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'solid'], $tag);
      $this->assertEquals("\\clbrdrb\\brdrw15\\brdrs \r\n", $result[0], "Failure setting style prefix for border-bottom-style: solid of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'dotted'], $tag);
      $this->assertEquals("\\clbrdrb\\brdrw15\\brdrdot \r\n", $result[0], "Failure setting style prefix for border-bottom-style: dotted of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'dashed'], $tag);
      $this->assertEquals("\\clbrdrb\\brdrw15\\brdrdash \r\n", $result[0], "Failure setting style prefix for border-bottom-style: dashed of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'double'], $tag);
      $this->assertEquals("\\clbrdrb\\brdrw15\\brdrdb \r\n", $result[0], "Failure setting style prefix for border-bottom-style: double of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'none'], $tag);
      $this->assertEquals("\\clbrdrb\\brdrw15\\brdrnone \r\n", $result[0], "Failure setting style prefix for border-bottom-style: none of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['border-bottom-width' => '1px', 'border-bottom-style' => 'hidden'], $tag);
      $this->assertEquals("\\clbrdrb\\brdrw15\\brdrnone \r\n", $result[0], "Failure setting style prefix for border-bottom-style: hidden of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['vertical-align' => 'top'], $tag);
      $this->assertEquals("\\clvertalt\r\n", $result[0], "Failure setting style prefix for vertical-align: top of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['vertical-align' => 'middle'], $tag);
      $this->assertEquals("\\clvertalc\r\n", $result[0], "Failure setting style prefix for vertical-align: middle of " . $tag);

      $result = $this->bookexportrtf_get_rtf_style_from_css(['vertical-align' => 'bottom'], $tag);
      $this->assertEquals("\\clvertalb\r\n", $result[0], "Failure setting style prefix for vertical-align: bottom of " . $tag);
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
