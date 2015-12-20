<?php namespace xml\unittest;

use lang\IllegalArgumentException;
use xml\XMLFormatException;
use xml\XPathException;
use xml\Tree;
use xml\XPath;
use xml\Node;
use unittest\actions\RuntimeVersion;

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

  #[@test]
  public function constructWithString() {
    new XPath('<document/>');
  }

  #[@test]
  public function constructWithDomDocument() {
    $d= new \DOMDocument();
    $d->appendChild($d->createElement('document'));
    new XPath($d);
  }

  #[@test]
  public function constructWithTree() {
    new XPath(new Tree('document'));
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function constructWithNull() {
    new XPath(null);
  }

  #[@test, @expect(XMLFormatException::class)]
  public function constructWithUnclosedTag() {
    new XPath('<unclosed-tag>');
  }

  #[@test, @expect(XMLFormatException::class)]
  public function constructWithSyntaxErrorInAttribute() {
    new XPath('<hello attribute="/>');
  }
  
  #[@test]
  public function queryReturnsNodeList() {
    $this->assertInstanceOf(
      'DOMNodeList',
      (new XPath('<document/>'))->query('/')
    );
  }

  #[@test]
  public function slashQueryReturnsDocument() {
    $this->assertInstanceOf(
      'DOMDocument',
      (new XPath('<document/>'))->query('/')->item(0)
    );
  }
  
  #[@test]
  public function attributeQuery() {
    $this->assertEquals('1549', (new XPath($this->personTree()))
      ->query('/person/@id')
      ->item(0)
      ->nodeValue
    );
  }

  #[@test]
  public function attributeName() {
    $this->assertEquals('id', (new XPath($this->personTree()))
      ->query('name(/person/@id)')
    );
  }

  #[@test]
  public function textQuery() {
    $this->assertEquals('Timm', (new XPath($this->personTree()))
      ->query('/person/firstName/text()')
      ->item(0)
      ->nodeValue
    );
  }

  #[@test]
  public function nameQuery() {
    $this->assertEquals('firstName', (new XPath($this->personTree()))
      ->query('name(/person/firstName)')
    );
  }

  #[@test]
  public function stringQuery() {
    $this->assertEquals('Timm', (new XPath($this->personTree()))
      ->query('string(/person/firstName)')
    );
  }

  #[@test]
  public function multipleQuery() {
    $locations= (new XPath($this->personTree()))->query('/person/location');
    
    $this->assertEquals('Karlsruhe', $locations->item(0)->nodeValue);
    $this->assertEquals('Germany', $locations->item(1)->nodeValue);
  }

  #[@test]
  public function offsetQuery() {
    $this->assertEquals('Karlsruhe', (new XPath($this->personTree()))
      ->query('string(/person/location[1])')
    );
  }

  #[@test, @expect(XPathException::class)]
  public function invalidQuery() {
    (new XPath('<document/>'))->query(',INVALID,');
  }
  
  #[@test]
  public function queryTree() {
    $xpath= new XPath(Tree::fromString('<document><node>value</node></document>'));
    $this->assertEquals('value', $xpath->query('string(/document/node)'));
  }
  
  #[@test, @action(new RuntimeVersion('<7.0.0-dev'))]
  public function queryTreeWithEncoding() {
    $value= new \lang\types\String('value öäü', 'iso-8859-1');
    $xpath= new XPath(Tree::fromString(sprintf(
      '<?xml version="1.0" encoding="iso-8859-1"?>'.
      '<document><node>%s</node></document>',
      $value->getBytes('iso-8859-1')
    )));

    $this->assertEquals($value, new \lang\types\String($xpath->query('string(/document/node)'), 'utf-8'));
  }
  
  #[@test, @action(new RuntimeVersion('<7.0.0-dev'))]
  public function queryTreeWithDefaultEncoding() {
    $value= new \lang\types\String('value Ã¶Ã¤Ã¼', 'utf-8');
    $xpath= new XPath($s= sprintf(
      '<document><node>%s</node></document>',
      $value->getBytes('utf-8')
    ));

    $this->assertEquals($value, new \lang\types\String($xpath->query('string(/document/node)'), 'utf-8'));
  }
}
