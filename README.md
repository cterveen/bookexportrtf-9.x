## Project title
bookexport-rtf-9.x

## Description

bookexport-rtf-9.x is a Drupal 9 module that exports a Drupal book or book page to RTF format. The module was build first for Drupal 7 to export books to something printable and readable as the printer friendly version isn't really reader friendly. bookexport-rtf-9.x creates an A4 document of the book or book page that contains elements suchs as a front page, table of contents, page numbers, chapter references etc. The module integrates with bookindex-9.x to add a page referenced index. The lay-out is customizable through CSS.

The project can be considered beta. The module is not in used but worked in my Drupal 9.x test environment and is currently being tested in Drupal 11.x with Book 1.x. The coding has been updated to Drupal 9.x coding standards and includes testing. The RTF document was tested in Microsoft Word and Libreoffice Writer. A help page and options are available. Internationalisation and localisation is not available. The module is not in the Drupal module repository.

Further development is intended.

## Installation
Depends on the Drupal [Book](https://www.drupal.org/project/book) module which was part of Drupal Core until Drupal 10.x and is a separate module in Drupal 11.

Download [Simple HTML DOM](https://simplehtmldom.sourceforge.io/) and copy parser.php into /libraries/simple_html_dom/

Download [Schepp's CSS Parser](https://github.com/Schepp/CSS-Parser) and copy simple_html_dom.php into /libraries/schepp-css-parser/

Copy all the files into /modules/bookexportrtf/

Enable the module

## Use

The RTF document can be accessed through the url (/book/exportrtf/\[node-id\]. A link to download the book or book page as RTF is available on every book page next to the printer friendly link (this doesn't seem to work in Drupal 11).

Settings and help can be found in the admin area (/admin/help/bookexportrtf).

## Credits

Written by Christiaan ter Veen <https://www.rork.nl/>

Depends on:

- Drupal <https://www.drupal.org/>
- Drupal book <https://www.drupal.org/project/book>
- Simple HTML DOM <https://simplehtmldom.sourceforge.io/>
- Schepp's CSS Parser <https://github.com/Schepp/CSS-Parser>

Technical details from:

- Microsoft RTF Specification
- RTF Pocket Guide by Sean M. Burke <https://www.oreilly.com/library/view/rtf-pocket-guide/9781449302047/>
- W3Schools CSS Reference <https://www.w3schools.com/cssref/index.php>

## License
To be decided, but consider it free to use, modify and distribute.
