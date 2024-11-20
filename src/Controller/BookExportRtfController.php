<?php

/**
 * @file
 * Provides the controller for BookExportRtf.
 */

namespace Drupal\bookexportrtf\Controller;

use Drupal\book\BookExport;
use Drupal\bookexportrtf\BookConvertRtf;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;


/**
 * Defines BookExportRtfController class.
 */
class BookExportRtfController extends ControllerBase {

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

  /** The converter
   *
   * @var \Drupal\bookexportrtf\BookConvertRtf
   */
  protected $bookConvertRtf;

  /**
   * Constructs a BookExportRTfController object.
   *
   * @param \Drupal\book\BookExport $bookExport
   *   The book export service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\bookexportrtf\BookConvertRtf $bookConvertRtf
   *   The book converter.
   */
  public function __construct(BookExport $bookExport, RendererInterface $renderer, BookConvertRtf $bookConvertRtf) {
    $this->bookExport = $bookExport;
    $this->renderer = $renderer;
    $this->bookConvertRtf = $bookConvertRtf;
  }

   /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('book.export'),
      $container->get('renderer'),
      $container->get('bookexportrtf.convertrtf')
    );
  }

  /**
   * Converts a book (part) to RTF and offers a downloadable file.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to export.
   *
   * @return object
   *   Return a respose object of the RTF file.
   */
  public function content(NodeInterface $node) {
    if (!isset($node->book)) {
      return [
        '#type' => 'markup',
        '#markup' => $this->t("Not a book page, so nothing to export."),
      ];
    }

    // Grab the contents of the book in HTML form.
    $exported_book = $this->bookExport->bookExportHtml($node);
    $content = new Response($this->renderer->renderRoot($exported_book));

    // Set style sheet(s).
    $this->bookConvertRtf->bookexportrtf_load_css(\Drupal::service('extension.list.module')->getPath('bookexportrtf') . "/css/bookexportrtf.rtf.css");
    $theme = \Drupal::theme()->getActiveTheme();
    $this->bookConvertRtf->bookexportrtf_load_css($theme->getPath() . "/css/bookexportrtf.rtf.css");

    // Check whether the node is a book or a page.
    $is_book = ($node->id() == $node->book['bid']);

    // Convert the book to RTF.
    $rtf = $this->bookConvertRtf->bookexportrtf_convert($content, $is_book);
    return new Response($rtf, Response::HTTP_OK, ['content-type' => 'application/rtf', 'content-disposition' => 'inline; filename="book.rtf"']);
  }
}
