<?php namespace xml\unittest;

use test\Assert;
use test\verify\Runtime;
use test\{Expect, Ignore, Test};
use xml\parser\XMLParser;
use xml\{Node, Tree, XMLFormatException};

class TreeTest {
  
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
    Assert::equals(
      '<root/>', 
      $this->sourceOf(new Tree('root'))
    );
  }

  #[Test]
  public function rootMember() {
    with ($t= new Tree('formresult'), $r= $t->root()); {
      Assert::instance(Node::class, $r);
      Assert::false($r->hasChildren());
      Assert::equals([], $r->getAttributes());
      Assert::equals('formresult', $r->getName());
    }
  }

  #[Test]
  public function addChild() {
    $t= new Tree('tests');
    $child= new Node('test', 'success', ['name' => 'TreeTest']);
    Assert::equals($child, $t->addChild($child));
  }

  #[Test]
  public function fromString() {
    $t= Tree::fromString('
      <c:config xmlns:c="http://example.com/cfg/1.0">
        <attribute name="key">value</attribute>
      </c:config>
    ');
    
    with ($r= $t->root()); {
      Assert::equals('c:config', $r->getName());
      Assert::true($r->hasAttribute('xmlns:c'));
      Assert::equals('http://example.com/cfg/1.0', $r->getAttribute('xmlns:c'));
      Assert::equals(1, sizeof($r->getChildren()));
    }      
    
    with ($c= $t->root()->nodeAt(0)); {
      Assert::equals('attribute', $c->getName());
      Assert::true($c->hasAttribute('name'));
      Assert::equals('key', $c->getAttribute('name'));
      Assert::equals(0, sizeof($c->getChildren()));
      Assert::equals('value', $c->getContent());
    }
  }
  
  #[Test]
  public function fromStringEncodingIso88591() {
    $tree= Tree::fromString(iconv(\xp::ENCODING, 'iso-8859-1', '<?xml version="1.0" encoding="ISO-8859-1"?>
      <document><node>Some umlauts: öäü</node></document>
    '));
    
    Assert::equals('utf-8', $tree->getEncoding());
    Assert::equals(1, sizeof($tree->root()->getChildren()));
    Assert::equals('document', $tree->root()->getName());
    Assert::equals('Some umlauts: öäü', $tree->root()->nodeAt(0)->getContent());
  }

  #[Test]
  public function fromStringEncodingUTF8() {
    $tree= Tree::fromString('<?xml version="1.0" encoding="UTF-8"?>
      <document><node>Some umlauts: öäü</node></document>
    ');
    
    Assert::equals('utf-8', $tree->getEncoding());
    Assert::equals(1, sizeof($tree->root()->getChildren()));
    Assert::equals('document', $tree->root()->getName());
    Assert::equals('Some umlauts: öäü', $tree->root()->nodeAt(0)->getContent());
  }

  #[Test]
  public function singleElement() {
    $tree= Tree::fromString('<document empty="false">Content</document>');
    Assert::equals(0, sizeof($tree->root()->getChildren()));
    Assert::equals('Content', $tree->root()->getContent());
    Assert::equals('false', $tree->root()->getAttribute('empty'));
  }

  #[Test, Expect(XMLFormatException::class)]
  public function fromNonXmlString() {
    Tree::fromString('@@NO-XML-HERE@@');
  }

  #[Test]
  public function utf8Encoding() {
    $t= (new Tree('unicode'))->withEncoding('UTF-8');
    $t->root()->setContent('Hällo');

    Assert::equals('<?xml version="1.0" encoding="UTF-8"?>', $t->getDeclaration());
    Assert::equals('<unicode>Hällo</unicode>', $this->sourceOf($t));
  }

  #[Test]
  public function iso88591Encoding() {
    $t= (new Tree('unicode'))->withEncoding('iso-8859-1');
    $t->root()->setContent('Hällo');

    Assert::equals('<?xml version="1.0" encoding="ISO-8859-1"?>', $t->getDeclaration());
    Assert::equals(iconv(\xp::ENCODING, 'iso-8859-1', '<unicode>Hällo</unicode>'), $this->sourceOf($t));
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
    
    Assert::equals('utf-8', $tree->getEncoding());
    Assert::equals(1, sizeof($tree->root()->getChildren()));
    Assert::equals('document', $tree->root()->getName());
    Assert::equals('Some umlauts: Ã¶Ã¤Ã¼', $tree->root()->nodeAt(0)->getContent());
  }

  #[Test]
  public function parseIntoIso() {
    $tree= new Tree();
    (new XMLParser('iso-8859-1'))->withCallback($tree)->parse(trim('
      <?xml version="1.0" encoding="UTF-8"?>
      <document><node>Some umlauts: Ã¶Ã¤Ã¼</node></document>
    '));
    
    Assert::equals('iso-8859-1', $tree->getEncoding());
    Assert::equals(1, sizeof($tree->root()->getChildren()));
    Assert::equals('document', $tree->root()->getName());
    Assert::equals('Some umlauts: öäü', $tree->root()->nodeAt(0)->getContent());
  }

  #[Test]
  public function as_string() {
    Assert::equals(
      "xml.Tree(version=1.0 encoding=utf-8)@{\n".
      "  xml.Node(doc) { }\n".
      "}",
      (new Tree('doc'))->toString()
    );
  }
}