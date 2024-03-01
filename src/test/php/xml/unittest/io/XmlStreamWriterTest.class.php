<?php namespace xml\unittest\io;

use io\streams\MemoryOutputStream;
use lang\IllegalStateException;
use test\{Assert, Before, Expect, Test, TestCase};
use xml\io\XmlStreamWriter;

class XmlStreamWriterTest {

  /** @return xml.io.XmlStreamWriter */
  private function newFixture() {
    return new XmlStreamWriter(new MemoryOutputStream());
  }
  
  #[Test]
  public function startIso88591Document() {
    $writer= $this->newFixture();
    $writer->startDocument('1.0', 'iso-8859-1');
    Assert::equals(
      '<?xml version="1.0" encoding="iso-8859-1"?>', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function startUtf8Document() {
    $writer= $this->newFixture();
    $writer->startDocument('1.0', 'utf-8');
    Assert::equals(
      '<?xml version="1.0" encoding="utf-8"?>', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function standaloneDocument() {
    $writer= $this->newFixture();
    $writer->startDocument('1.0', 'iso-8859-1', true);
    Assert::equals(
      '<?xml version="1.0" encoding="iso-8859-1" standalone="yes"?>', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function startElement() {
    $writer= $this->newFixture();
    $writer->startElement('book');
    Assert::equals(
      '<book>', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function startElementWithAttribute() {
    $writer= $this->newFixture();
    $writer->startElement('book', ['isbn' => '978-3-86680-192-9']);
    Assert::equals(
      '<book isbn="978-3-86680-192-9">', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function startElementWithAttributes() {
    $writer= $this->newFixture();
    $writer->startElement('book', ['isbn' => '978-3-86680-192-9', 'authors' => 'Timm & Alex']);
    Assert::equals(
      '<book isbn="978-3-86680-192-9" authors="Timm &amp; Alex">', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function closeElement() {
    $writer= $this->newFixture();
    $writer->startElement('book');
    $writer->closeElement();
    Assert::equals(
      '<book></book>', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function closeElements() {
    $writer= $this->newFixture();
    $writer->startElement('book');
    $writer->startElement('author');
    $writer->closeElement();
    $writer->closeElement();
    Assert::equals(
      '<book><author></author></book>', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function startComment() {
    $writer= $this->newFixture();
    $writer->startComment();
    Assert::equals(
      '<!--', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function closeComment() {
    $writer= $this->newFixture();
    $writer->startComment();
    $writer->closeComment();
    Assert::equals(
      '<!---->', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function startCData() {
    $writer= $this->newFixture();
    $writer->startCData();
    Assert::equals(
      '<![CDATA[', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function closeCData() {
    $writer= $this->newFixture();
    $writer->startCData();
    $writer->closeCData();
    Assert::equals(
      '<![CDATA[]]>', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function startPI() {
    $writer= $this->newFixture();
    $writer->startPI('php');
    Assert::equals(
      '<?php ', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function closePI() {
    $writer= $this->newFixture();
    $writer->startPI('php');
    $writer->closePI();
    Assert::equals(
      '<?php ?>', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function writeText() {
    $writer= $this->newFixture();
    $writer->startElement('book');
    $writer->writeText('Hello & World');
    $writer->closeElement();
    Assert::equals(
      '<book>Hello &amp; World</book>', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function writeCData() {
    $writer= $this->newFixture();
    $writer->startElement('book');
    $writer->writeCData('Hello & World');
    $writer->closeElement();
    Assert::equals(
      '<book><![CDATA[Hello & World]]></book>', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function writeComment() {
    $writer= $this->newFixture();
    $writer->startElement('book');
    $writer->writeComment('Hello & World');
    $writer->closeElement();
    Assert::equals(
      '<book><!--Hello & World--></book>', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function writeCommentedNode() {
    $writer= $this->newFixture();
    $writer->startElement('book');
    $writer->startComment();
    $writer->writeElement('author', 'Timm');
    $writer->closeComment();
    $writer->closeElement();
    Assert::equals(
      '<book><!--<author>Timm</author>--></book>', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function writeMarkup() {
    $writer= $this->newFixture();
    $writer->startElement('markup');
    $writer->writeText('This is ');
    $writer->writeElement('b', 'really');
    $writer->writeText(' important!');
    $writer->closeElement();
    Assert::equals(
      '<markup>This is <b>really</b> important!</markup>', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function writePI() {
    $writer= $this->newFixture();
    $writer->startElement('code');
    $writer->writePI('php', 'echo "Hello World";');
    $writer->closeElement();
    Assert::equals(
      '<code><?php echo "Hello World";?></code>', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function writePIWithAttributes() {
    $writer= $this->newFixture();
    $writer->writePI('xml-stylesheet', ['href' => 'template.xsl']);
    Assert::equals(
      '<?xml-stylesheet href="template.xsl"?>', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function writeCDataAndText() {
    $writer= $this->newFixture();
    $writer->startElement('book');
    $writer->writeText('Hello');
    $writer->writeCData(' & ');
    $writer->writeText('World');
    $writer->closeElement();
    Assert::equals(
      '<book>Hello<![CDATA[ & ]]>World</book>', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function writElement() {
    $writer= $this->newFixture();
    $writer->writeElement('book', 'Hello & World', ['isbn' => '978-3-86680-192-9']);
    Assert::equals(
      '<book isbn="978-3-86680-192-9">Hello &amp; World</book>', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function writElementEmptyContent() {
    $writer= $this->newFixture();
    $writer->writeElement('book');
    Assert::equals(
      '<book/>', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function endDocumentClosesAllElements() {
    $writer= $this->newFixture();
    $writer->startElement('books');
    $writer->startElement('book');
    $writer->startElement('author');

    $writer->closeDocument();
    Assert::equals(
      '<books><book><author></author></book></books>', 
      $writer->out()->bytes()
    );
  }

  #[Test]
  public function endDocumentClosesComments() {
    $writer= $this->newFixture();
    $writer->startElement('books');
    $writer->startComment();
    $writer->writeText('Nothing here yet');

    $writer->closeDocument();
    Assert::equals(
      '<books><!--Nothing here yet--></books>', 
      $writer->out()->bytes()
    );
  }

  #[Test, Expect(class: IllegalStateException::class, message: '/Incorrect nesting/')]
  public function incorrectNesting() {
    $writer= $this->newFixture();
    $writer->startElement('books');
    $writer->startComment();
    $writer->writeText('Nothing here yet');
    $writer->closeElement();
  }
}