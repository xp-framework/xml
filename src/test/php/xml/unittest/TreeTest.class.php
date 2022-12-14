<?php namespace xml\unittest;
 
use unittest\actions\RuntimeVersion;
use unittest\{Expect, Ignore, Test};
use xml\parser\XMLParser;
use xml\{Node, Tree, XMLFormatException};

/**
 * Test XML Tree class
 *
 * @see   xp://xml.unittest.NodeTest 
 */
class TreeTest extends \unittest\TestCase {
  
  /**
   * Helper method which returns the XML representation of a Tree object,
   * trimmed of trailing \ns.
   *
   * @param   xml.Tree tree
   * @return  string
   */
  protected function sourceOf($tree, $mode= INDENT_DEFAULT) {
    return rtrim($tree->getSource($mode), "\n");
  }

  #[Test]
  public function emptyTree() {
    $this->assertEquals(
      '<root/>', 
      $this->sourceOf(new Tree('root'))
    );
  }

  #[Test]
  public function rootMember() {
    with ($t= new Tree('formresult'), $r= $t->root()); {
      $this->assertInstanceOf(Node::class, $r);
      $this->assertFalse($r->hasChildren());
      $this->assertEquals([], $r->getAttributes());
      $this->assertEquals('formresult', $r->getName());
    }
  }

  #[Test]
  public function addChild() {
    $t= new Tree('tests');
    $child= new Node('test', 'success', ['name' => 'TreeTest']);
    $this->assertEquals($child, $t->addChild($child));
  }

  #[Test]
  public function fromString() {
    $t= Tree::fromString('
      <c:config xmlns:c="http://example.com/cfg/1.0">
        <attribute name="key">value</attribute>
      </c:config>
    ');
    
    with ($r= $t->root()); {
      $this->assertEquals('c:config', $r->getName());
      $this->assertTrue($r->hasAttribute('xmlns:c'));
      $this->assertEquals('http://example.com/cfg/1.0', $r->getAttribute('xmlns:c'));
      $this->assertEquals(1, sizeof($r->getChildren()));
    }      
    
    with ($c= $t->root()->nodeAt(0)); {
      $this->assertEquals('attribute', $c->getName());
      $this->assertTrue($c->hasAttribute('name'));
      $this->assertEquals('key', $c->getAttribute('name'));
      $this->assertEquals(0, sizeof($c->getChildren()));
      $this->assertEquals('value', $c->getContent());
    }
  }
  
  #[Test]
  public function fromStringEncodingIso88591() {
    $tree= Tree::fromString(iconv(\xp::ENCODING, 'iso-8859-1', '<?xml version="1.0" encoding="ISO-8859-1"?>
      <document><node>Some umlauts: öäü</node></document>
    '));
    
    $this->assertEquals('utf-8', $tree->getEncoding());
    $this->assertEquals(1, sizeof($tree->root()->getChildren()));
    $this->assertEquals('document', $tree->root()->getName());
    $this->assertEquals('Some umlauts: öäü', $tree->root()->nodeAt(0)->getContent());
  }

  #[Test]
  public function fromStringEncodingUTF8() {
    $tree= Tree::fromString('<?xml version="1.0" encoding="UTF-8"?>
      <document><node>Some umlauts: öäü</node></document>
    ');
    
    $this->assertEquals('utf-8', $tree->getEncoding());
    $this->assertEquals(1, sizeof($tree->root()->getChildren()));
    $this->assertEquals('document', $tree->root()->getName());
    $this->assertEquals('Some umlauts: öäü', $tree->root()->nodeAt(0)->getContent());
  }

  #[Test]
  public function singleElement() {
    $tree= Tree::fromString('<document empty="false">Content</document>');
    $this->assertEquals(0, sizeof($tree->root()->getChildren()));
    $this->assertEquals('Content', $tree->root()->getContent());
    $this->assertEquals('false', $tree->root()->getAttribute('empty'));
  }

  #[Test, Expect(XMLFormatException::class)]
  public function fromNonXmlString() {
    Tree::fromString('@@NO-XML-HERE@@');
  }

  #[Test]
  public function utf8Encoding() {
    $t= (new Tree('unicode'))->withEncoding('UTF-8');
    $t->root()->setContent('Hällo');

    $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>', $t->getDeclaration());
    $this->assertEquals('<unicode>Hällo</unicode>', $this->sourceOf($t));
  }

  #[Test]
  public function iso88591Encoding() {
    $t= (new Tree('unicode'))->withEncoding('iso-8859-1');
    $t->root()->setContent('Hällo');

    $this->assertEquals('<?xml version="1.0" encoding="ISO-8859-1"?>', $t->getDeclaration());
    $this->assertEquals(iconv(\xp::ENCODING, 'iso-8859-1', '<unicode>Hällo</unicode>'), $this->sourceOf($t));
  }

  #[Test, Ignore('Performance testing')]
  public function performance() {
    $s= microtime(true);
    $t= new Tree();
    for ($i= 0; $i < 100; $i++) {
      $c= $t->addChild(new Node('child', null, ['id' => $i]));
      for ($j= 0; $j < 100; $j++) {
        $c->addChild(new Node('elements', str_repeat('x', $j)));
      }
    }
    $l= strlen($t->getSource(INDENT_NONE));
    printf('%d bytes, %.3f seconds', $l, microtime(true) - $s);
  }

  #[Test]
  public function parseIntoUtf8() {
    $tree= new Tree();
    (new XMLParser('utf-8'))->withCallback($tree)->parse(trim('
      <?xml version="1.0" encoding="UTF-8"?>
      <document><node>Some umlauts: Ã¶Ã¤Ã¼</node></document>
    '));
    
    $this->assertEquals('utf-8', $tree->getEncoding());
    $this->assertEquals(1, sizeof($tree->root()->getChildren()));
    $this->assertEquals('document', $tree->root()->getName());
    $this->assertEquals('Some umlauts: Ã¶Ã¤Ã¼', $tree->root()->nodeAt(0)->getContent());
  }

  #[Test]
  public function parseIntoIso() {
    $tree= new Tree();
    (new XMLParser('iso-8859-1'))->withCallback($tree)->parse(trim('
      <?xml version="1.0" encoding="UTF-8"?>
      <document><node>Some umlauts: Ã¶Ã¤Ã¼</node></document>
    '));
    
    $this->assertEquals('iso-8859-1', $tree->getEncoding());
    $this->assertEquals(1, sizeof($tree->root()->getChildren()));
    $this->assertEquals('document', $tree->root()->getName());
    $this->assertEquals('Some umlauts: öäü', $tree->root()->nodeAt(0)->getContent());
  }

  #[Test]
  public function as_string() {
    $this->assertEquals(
      "xml.Tree(version=1.0 encoding=utf-8)@{\n".
      "  xml.Node(doc) { }\n".
      "}",
      (new Tree('doc'))->toString()
    );
  }
}