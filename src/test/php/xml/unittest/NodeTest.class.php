<?php namespace xml\unittest;

use lang\{Error, IllegalArgumentException};
use unittest\Assert;
use unittest\{Expect, Test};
use xml\{Node, XMLFormatException};

class NodeTest {
  
  /**
   * Helper method which returns the XML representation of a Node object,
   * trimmed of trailing \ns.
   *
   * @param   xml.Node node
   * @return  string
   */
  protected function sourceOf($node, $mode= INDENT_DEFAULT) {
    return rtrim($node->getSource($mode), "\n");
  }
  
  #[Test]
  public function attributeAccessors() {
    $n= new Node('node');
    $n->setAttribute('id', 1);
    Assert::true($n->hasAttribute('id'));
    Assert::false($n->hasAttribute('href'));
    Assert::equals(1, $n->getAttribute('id'));
  }

  #[Test]
  public function contentAccessors() {
    $content= '"This is interesting", Tom\'s friend said. "It\'s > 4 but < 2!"';
    $n= new Node('node');
    $n->setContent($content);
    Assert::equals($content, $n->getContent());
  }
  
  #[Test]
  public function nameAccessors() {
    $n= new Node('node');
    $n->setName('name');
    Assert::equals('name', $n->getName());
  }
  
  #[Test, Expect(XMLFormatException::class)]
  public function illegalContent() {
    $n= new Node('node');
    $n->setContent("\0");
  }
  
  #[Test, Expect(Error::class)]
  public function addingNullChild() {
    (new Node('node'))->addChild(null);
  }

  #[Test]
  public function addingReturnsChild() {
    $n= new Node('node');
    $child= new Node('node');
    Assert::equals($child, $n->addChild($child));
  }

  #[Test]
  public function withChildReturnsNode() {
    $n= new Node('node');
    $child= new Node('node');
    Assert::equals($n, $n->withChild($child));
  }
  
  #[Test]
  public function fromEmptyArray() {
    Assert::equals(
      '<node/>', 
      $this->sourceOf(Node::fromArray([], 'node'))
    );
  }

  #[Test]
  public function fromNumberArray() {
    Assert::equals(
      '<items><item>1</item><item>2</item></items>', 
      $this->sourceOf(Node::fromArray([1, 2], 'items'), INDENT_NONE)
    );
  }

  #[Test]
  public function fromCharacterArray() {
    Assert::equals(
      '<characters><character>1</character><character>&amp;</character><character>1</character></characters>', 
      $this->sourceOf(Node::fromArray(['1', '&', '1'], 'characters'), INDENT_NONE)
    );
  }
  
  #[Test]
  public function sourceOfEmptyNode() {
    Assert::equals(
      '<node/>', 
      $this->sourceOf(new Node('node'))
    );
  }

  #[Test]
  public function sourceOfNodeWithOneAttribute() {
    Assert::equals(
      '<node id="1"/>', 
      $this->sourceOf(new Node('node', null, ['id' => 1]))
    );
  }

  #[Test]
  public function sourceOfNodeWithTwoAttributes() {
    Assert::equals(
      '<node id="2" name="&amp;XML"/>', 
      $this->sourceOf(new Node('node', null, ['id' => 2, 'name' => '&XML']))
    );
  }

  #[Test]
  public function sourceOfNodeWithContent() {
    Assert::equals(
      '<expr>eval(\'1 &lt;&gt; 2 &amp;&amp; \') == &quot;Parse Error&quot;</expr>', 
      $this->sourceOf(new Node('expr', 'eval(\'1 <> 2 && \') == "Parse Error"'))
    );
  }

  #[Test]
  public function sourceOfNodeWithCData() {
    Assert::equals(
      '<text><![CDATA[Special characters: <>"\'&]]></text>', 
      $this->sourceOf(new Node('text', new \xml\CData('Special characters: <>"\'&')))
    );
  }

  #[Test]
  public function sourceOfNodeWithPCData() {
    Assert::equals(
      '<text>A <a href="http://xp-framework.net/">link</a> to click on</text>', 
      $this->sourceOf(new Node('text', new \xml\PCData('A <a href="http://xp-framework.net/">link</a> to click on')))
    );
  }
  
  #[Test]
  public function getSourceWithDefaultEncoding() {
    Assert::equals(
      "<n>\xc3\x9cbercoder</n>",
      (new Node('n', "Übercoder"))->getSource(INDENT_NONE)
    );
  }

  #[Test]
  public function getSourceWithIsoEncoding() {
    Assert::equals(
      "<n>\xdcbercoder</n>",
      (new Node('n', "Übercoder"))->getSource(INDENT_NONE, 'iso-8859-1')
    );
  }

  #[Test]
  public function getSourceWithUtf8Encoding() {
    Assert::equals(
      "<n>\xc3\x9cbercoder</n>",
      (new Node('n', "Übercoder"))->getSource(INDENT_NONE, 'utf-8')
    );
  }

  #[Test]
  public function fromObject() { 
    Assert::equals(
      "<node>\n".
      "  <id>1549</id>\n".
      "  <color>green</color>\n".
      "  <name>Name goes here</name>\n".
      "</node>",
      $this->sourceOf(Node::fromObject(new class() {
        public $id= 1549;
        public $color= 'green';
        public $name= null;

        public function __construct() {
          $this->name= 'Name goes here';
        }
      }, 'node'))
    );
  }

  #[Test]
  public function fromObject_uses_short_name_if_omitted() {
    Assert::equals(
      '<Some/>',
      $this->sourceOf(Node::fromObject(new Some(), null))
    );
  }

  #[Test]
  public function fromObject_invokes_serialize() {
    Assert::equals(
      "<node>\n".
      "  <value>test</value>\n".
      "</node>",
      $this->sourceOf(Node::fromObject(new class() {
        public function __serialize() {
          return ['value' => 'test'];
        }
      }, 'node'))
    );
  }

  #[Test]
  public function as_string() {
    Assert::equals(
      'xml.Node(doc) { }',
      (new Node('doc'))->toString()
    );
  }

  #[Test]
  public function as_string_with_content() {
    Assert::equals(
      'xml.Node(test) { "Succeeded" }',
      (new Node('test', 'Succeeded'))->toString()
    );
  }

  #[Test]
  public function as_string_with_attributes() {
    Assert::equals(
      'xml.Node(a @href= "http://example.com") { }',
      (new Node('a', null, ['href' => 'http://example.com']))->toString()
    );
  }

  #[Test]
  public function as_string_with_children() {
    Assert::equals(
      "xml.Node(div) {\n".
      "  xml.Node(p) { \"Test\" }\n".
      "}",
      (new Node('div'))->withChild(new Node('p', 'Test'))->toString()
    );
  }
}