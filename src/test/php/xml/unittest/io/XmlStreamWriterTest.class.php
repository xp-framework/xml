<?php namespace xml\unittest\io;

use io\streams\MemoryOutputStream;
use lang\IllegalStateException;
use unittest\{Expect, Test, TestCase};
use xml\io\XmlStreamWriter;

class XmlStreamWriterTest extends TestCase {
  protected $out= null;
  protected $writer= null;

  /** @return void */
  public function setUp() {
    $this->out= new MemoryOutputStream();
    $this->writer= new XmlStreamWriter($this->out);
  }
  
  #[Test]
  public function startIso88591Document() {
    $this->writer->startDocument('1.0', 'iso-8859-1');
    $this->assertEquals(
      '<?xml version="1.0" encoding="iso-8859-1"?>', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function startUtf8Document() {
    $this->writer->startDocument('1.0', 'utf-8');
    $this->assertEquals(
      '<?xml version="1.0" encoding="utf-8"?>', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function standaloneDocument() {
    $this->writer->startDocument('1.0', 'iso-8859-1', true);
    $this->assertEquals(
      '<?xml version="1.0" encoding="iso-8859-1" standalone="yes"?>', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function startElement() {
    $this->writer->startElement('book');
    $this->assertEquals(
      '<book>', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function startElementWithAttribute() {
    $this->writer->startElement('book', ['isbn' => '978-3-86680-192-9']);
    $this->assertEquals(
      '<book isbn="978-3-86680-192-9">', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function startElementWithAttributes() {
    $this->writer->startElement('book', ['isbn' => '978-3-86680-192-9', 'authors' => 'Timm & Alex']);
    $this->assertEquals(
      '<book isbn="978-3-86680-192-9" authors="Timm &amp; Alex">', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function closeElement() {
    $this->writer->startElement('book');
    $this->writer->closeElement();
    $this->assertEquals(
      '<book></book>', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function closeElements() {
    $this->writer->startElement('book');
    $this->writer->startElement('author');
    $this->writer->closeElement();
    $this->writer->closeElement();
    $this->assertEquals(
      '<book><author></author></book>', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function startComment() {
    $this->writer->startComment();
    $this->assertEquals(
      '<!--', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function closeComment() {
    $this->writer->startComment();
    $this->writer->closeComment();
    $this->assertEquals(
      '<!---->', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function startCData() {
    $this->writer->startCData();
    $this->assertEquals(
      '<![CDATA[', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function closeCData() {
    $this->writer->startCData();
    $this->writer->closeCData();
    $this->assertEquals(
      '<![CDATA[]]>', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function startPI() {
    $this->writer->startPI('php');
    $this->assertEquals(
      '<?php ', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function closePI() {
    $this->writer->startPI('php');
    $this->writer->closePI();
    $this->assertEquals(
      '<?php ?>', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function writeText() {
    $this->writer->startElement('book');
    $this->writer->writeText('Hello & World');
    $this->writer->closeElement();
    $this->assertEquals(
      '<book>Hello &amp; World</book>', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function writeCData() {
    $this->writer->startElement('book');
    $this->writer->writeCData('Hello & World');
    $this->writer->closeElement();
    $this->assertEquals(
      '<book><![CDATA[Hello & World]]></book>', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function writeComment() {
    $this->writer->startElement('book');
    $this->writer->writeComment('Hello & World');
    $this->writer->closeElement();
    $this->assertEquals(
      '<book><!--Hello & World--></book>', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function writeCommentedNode() {
    $this->writer->startElement('book');
    $this->writer->startComment();
    $this->writer->writeElement('author', 'Timm');
    $this->writer->closeComment();
    $this->writer->closeElement();
    $this->assertEquals(
      '<book><!--<author>Timm</author>--></book>', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function writeMarkup() {
    $this->writer->startElement('markup');
    $this->writer->writeText('This is ');
    $this->writer->writeElement('b', 'really');
    $this->writer->writeText(' important!');
    $this->writer->closeElement();
    $this->assertEquals(
      '<markup>This is <b>really</b> important!</markup>', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function writePI() {
    $this->writer->startElement('code');
    $this->writer->writePI('php', 'echo "Hello World";');
    $this->writer->closeElement();
    $this->assertEquals(
      '<code><?php echo "Hello World";?></code>', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function writePIWithAttributes() {
    $this->writer->writePI('xml-stylesheet', ['href' => 'template.xsl']);
    $this->assertEquals(
      '<?xml-stylesheet href="template.xsl"?>', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function writeCDataAndText() {
    $this->writer->startElement('book');
    $this->writer->writeText('Hello');
    $this->writer->writeCData(' & ');
    $this->writer->writeText('World');
    $this->writer->closeElement();
    $this->assertEquals(
      '<book>Hello<![CDATA[ & ]]>World</book>', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function writElement() {
    $this->writer->writeElement('book', 'Hello & World', ['isbn' => '978-3-86680-192-9']);
    $this->assertEquals(
      '<book isbn="978-3-86680-192-9">Hello &amp; World</book>', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function writElementEmptyContent() {
    $this->writer->writeElement('book');
    $this->assertEquals(
      '<book/>', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function endDocumentClosesAllElements() {
    $this->writer->startElement('books');
    $this->writer->startElement('book');
    $this->writer->startElement('author');

    $this->writer->closeDocument();
    $this->assertEquals(
      '<books><book><author></author></book></books>', 
      $this->out->bytes()
    );
  }

  #[Test]
  public function endDocumentClosesComments() {
    $this->writer->startElement('books');
    $this->writer->startComment();
    $this->writer->writeText('Nothing here yet');

    $this->writer->closeDocument();
    $this->assertEquals(
      '<books><!--Nothing here yet--></books>', 
      $this->out->bytes()
    );
  }

  #[Test, Expect(['class' => IllegalStateException::class, 'withMessage' => '/Incorrect nesting/'])]
  public function incorrectNesting() {
    $this->writer->startElement('books');
    $this->writer->startComment();
    $this->writer->writeText('Nothing here yet');
    $this->writer->closeElement();
  }
}