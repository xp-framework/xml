<?php namespace xml\unittest;

use unittest\{Test, TestCase};
use xml\dom\Document;

/**
 * TestCase for Document class
 *
 * @see      xp://xml.dom.Document
 */
class DocumentTest extends TestCase {

  #[Test]
  public function elementsByTagName() {
    $dom= Document::fromString('<list>
      <person id="1549">Timm</person>
      <person id="1552">Alex</person>
    </list>');
    
    $this->assertEquals(
      $dom->getDocumentElement()->getChildren(),
      $dom->getElementsByTagName('person')
    );
  }

  #[Test]
  public function elementById() {
    $dom= Document::fromString('<list>
      <person id="1549">Timm</person>
      <person id="1552">Alex</person>
    </list>');
    
    $this->assertEquals(
      $dom->getDocumentElement()->nodeAt(0),
      $dom->getElementById('1549')
    );
  }

  #[Test]
  public function elementsByName() {
    $dom= Document::fromString('<wizard>
      <step>
        <form name="package">
          <select>...</select>
        </form>
      </step>
    </wizard>');
    
    $this->assertEquals(
      [$dom->getDocumentElement()->nodeAt(0)->nodeAt(0)],
      $dom->getElementsByName('package')
    );
  }

  #[Test]
  public function nestedElementById() {
    $dom= Document::fromString('<html>
      <head>
        <title>Example page</title>
      </head>
      <body>
        <div id="header">
          <ul id="menu">
            <li id="home">Home</li>
          </ul>
        </div>
      </body>
    </html>');
    
    $this->assertEquals(
      $dom->getDocumentElement()->nodeAt(1)->nodeAt(0)->nodeAt(0)->nodeAt(0),
      $dom->getElementById('home')
    );
  }

  #[Test]
  public function noSuchElementById() {
    $this->assertEquals(
      null,
      Document::fromString('<list/>')->getElementById('1777')
    );
  }
}