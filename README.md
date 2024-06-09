## Project title
bookexport-rtf-9.x

## Description

bookexport-rtf-9.x is a Drupal 9 module that exports a Drupal book or book page to RTF format. The module was build first for Drupal 7 to export books to something printable and readable as the printer friendly version isn't really reader friendly. bookexport-rtf-9.x creates an A4 document of the book or book page that contains elements suchs as a front page, table of contents, page numbers, chapter references etc. The lay-out is customizable through CSS and there is integration with bookindex-9.x to make a page referenced index. A help page about the supported features and options are available. Internationalisation and localisation is not available.

The coding has been updated to Drupal 9.x coding standards and includes testing. I'm not particularly interested in to uploading it into the Drupal modules database as I'm not that invested into Drupal development.

The project is currently not in use but worked in my test environment. I still intend to continue development and use it on a live website.

## Installation
Depends on the Drupal [Book](https://www.drupal.org/project/book) module which is part of Drupal Core

Download [Simple HTML DOM](https://simplehtmldom.sourceforge.io/) and copy it into /libraries/simple_html_dom/

Download [Schepp's CSS Parser](https://github.com/Schepp/CSS-Parser) and copy it into /libraries/schepp-css-parser/ 

Copy all the files into /modules/bookexport-rtf/

Enable the module

## Use

A link to download the book or book page as RTF is available on every book page next to the printer friendly link.

Settings and help can be found in the admin area.

## Credits

Depends on:

- Drupal book <https://www.drupal.org/project/book>
- Simple HTML DOM <https://simplehtmldom.sourceforge.io/>
- Schepp's CSS Parser <https://github.com/Schepp/CSS-Parser>

Sources used for RTF:

- Microsoft RTF Specification
- RTF Pocket Guide by Sean M. Burke <https://www.oreilly.com/library/view/rtf-pocket-guide/9781449302047/>

## License
To be decided
