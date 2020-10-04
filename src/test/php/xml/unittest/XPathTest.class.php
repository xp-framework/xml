<?php namespace xml\unittest;

use lang\IllegalArgumentException;
use unittest\actions\RuntimeVersion;
use unittest\{Expect, Test};
use xml\{Node, Tree, XMLFormatException, XPath, XPathException};

/**
 * TestCase for XPath class
 *
 * @see  xp://xml.XPath
 */
class XPathTest extends \unittest\TestCase {

  /**
   * Returns an XML tree for use in further test cases
   *
   * @return  xml.Tree
   */
  protected function personTree() {
    $t= new Tree('person');
    $t->root()->setAttribute('id', '1549');
    $t->addChild(new Node('firstName', 'Timm'));
    $t->addChild(new Node('lastName', 'Friebe'));
    $t->addChild(new Node('location', 'Karlsruhe'));
    $t->addChild(new Node('location', 'Germany'));
    return $t;
  }

  #[Test]
  public function constructWithString() {
    new XPath('<document/>');
  }

  #[Test]
  public function constructWithDomDocument() {
    $d= new \DOMDocument();
    $d->appendChild($d->createElement('document'));
    new XPath($d);
  }

  #[Test]
  public function constructWithTree() {
    new XPath(new Tree('document'));
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function constructWithNull() {
    new XPath(null);
  }

  #[Test, Expect(XMLFormatException::class)]
  public function constructWithUnclosedTag() {
    new XPath('<unclosed-tag>');
  }

  #[Test, Expect(XMLFormatException::class)]
  public function constructWithSyntaxErrorInAttribute() {
    new XPath('<hello attribute="/>');
  }
  
  #[Test]
  public function queryReturnsNodeList() {
    $this->assertInstanceOf(
      'DOMNodeList',
      (new XPath('<document/>'))->query('/')
    );
  }

  #[Test]
  public function slashQueryReturnsDocument() {
    $this->assertInstanceOf(
      'DOMDocument',
      (new XPath('<document/>'))->query('/')->item(0)
    );
  }
  
  #[Test]
  public function attributeQuery() {
    $this->assertEquals('1549', (new XPath($this->personTree()))
      ->query('/person/@id')
      ->item(0)
      ->nodeValue
    );
  }

  #[Test]
  public function attributeName() {
    $this->assertEquals('id', (new XPath($this->personTree()))
      ->query('name(/person/@id)')
    );
  }

  #[Test]
  public function textQuery() {
    $this->assertEquals('Timm', (new XPath($this->personTree()))
      ->query('/person/firstName/text()')
      ->item(0)
      ->nodeValue
    );
  }

  #[Test]
  public function nameQuery() {
    $this->assertEquals('firstName', (new XPath($this->personTree()))
      ->query('name(/person/firstName)')
    );
  }

  #[Test]
  public function stringQuery() {
    $this->assertEquals('Timm', (new XPath($this->personTree()))
      ->query('string(/person/firstName)')
    );
  }

  #[Test]
  public function multipleQuery() {
    $locations= (new XPath($this->personTree()))->query('/person/location');
    
    $this->assertEquals('Karlsruhe', $locations->item(0)->nodeValue);
    $this->assertEquals('Germany', $locations->item(1)->nodeValue);
  }

  #[Test]
  public function offsetQuery() {
    $this->assertEquals('Karlsruhe', (new XPath($this->personTree()))
      ->query('string(/person/location[1])')
    );
  }

  #[Test, Expect(XPathException::class)]
  public function invalidQuery() {
    (new XPath('<document/>'))->query(',INVALID,');
  }
  
  #[Test]
  public function queryTree() {
    $xpath= new XPath(Tree::fromString('<document><node>value</node></document>'));
    $this->assertEquals('value', $xpath->query('string(/document/node)'));
  }
  
  #[Test]
  public function queryTreeWithEncoding() {
    $xpath= new XPath(Tree::fromString(sprintf(
      '<?xml version="1.0" encoding="iso-8859-1"?>'.
      '<document><node>%s</node></document>',
      utf8_decode('öäü')
    )));
    $this->assertEquals('öäü', $xpath->query('string(/document/node)'));
  }
  
  #[Test]
  public function queryTreeWithDefaultEncoding() {
    $xpath= new XPath('<document><node>öäü</node></document>');
    $this->assertEquals('öäü', $xpath->query('string(/document/node)'));
  }
}