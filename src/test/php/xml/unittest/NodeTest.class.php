<?php namespace xml\unittest;
 
use lang\{Error, IllegalArgumentException};
use unittest\{Expect, Test};
use xml\{Node, XMLFormatException};

/**
 * Test XML Node class
 *
 * @see   xp://xml.unittest.TreeTest 
 */
class NodeTest extends \unittest\TestCase {
  
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
    $this->assertTrue($n->hasAttribute('id'));
    $this->assertFalse($n->hasAttribute('href'));
    $this->assertEquals(1, $n->getAttribute('id'));
  }

  #[Test]
  public function contentAccessors() {
    $content= '"This is interesting", Tom\'s friend said. "It\'s > 4 but < 2!"';
    $n= new Node('node');
    $n->setContent($content);
    $this->assertEquals($content, $n->getContent());
  }
  
  #[Test]
  public function nameAccessors() {
    $n= new Node('node');
    $n->setName('name');
    $this->assertEquals('name', $n->getName());
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
    $this->assertEquals($child, $n->addChild($child));
  }

  #[Test]
  public function withChildReturnsNode() {
    $n= new Node('node');
    $child= new Node('node');
    $this->assertEquals($n, $n->withChild($child));
  }
  
  #[Test]
  public function fromEmptyArray() {
    $this->assertEquals(
      '<node/>', 
      $this->sourceOf(Node::fromArray([], 'node'))
    );
  }

  #[Test]
  public function fromNumberArray() {
    $this->assertEquals(
      '<items><item>1</item><item>2</item></items>', 
      $this->sourceOf(Node::fromArray([1, 2], 'items'), INDENT_NONE)
    );
  }

  #[Test]
  public function fromCharacterArray() {
    $this->assertEquals(
      '<characters><character>1</character><character>&amp;</character><character>1</character></characters>', 
      $this->sourceOf(Node::fromArray(['1', '&', '1'], 'characters'), INDENT_NONE)
    );
  }
  
  #[Test]
  public function sourceOfEmptyNode() {
    $this->assertEquals(
      '<node/>', 
      $this->sourceOf(new Node('node'))
    );
  }

  #[Test]
  public function sourceOfNodeWithOneAttribute() {
    $this->assertEquals(
      '<node id="1"/>', 
      $this->sourceOf(new Node('node', null, ['id' => 1]))
    );
  }

  #[Test]
  public function sourceOfNodeWithTwoAttributes() {
    $this->assertEquals(
      '<node id="2" name="&amp;XML"/>', 
      $this->sourceOf(new Node('node', null, ['id' => 2, 'name' => '&XML']))
    );
  }

  #[Test]
  public function sourceOfNodeWithContent() {
    $this->assertEquals(
      '<expr>eval(\'1 &lt;&gt; 2 &amp;&amp; \') == &quot;Parse Error&quot;</expr>', 
      $this->sourceOf(new Node('expr', 'eval(\'1 <> 2 && \') == "Parse Error"'))
    );
  }

  #[Test]
  public function sourceOfNodeWithCData() {
    $this->assertEquals(
      '<text><![CDATA[Special characters: <>"\'&]]></text>', 
      $this->sourceOf(new Node('text', new \xml\CData('Special characters: <>"\'&')))
    );
  }

  #[Test]
  public function sourceOfNodeWithPCData() {
    $this->assertEquals(
      '<text>A <a href="http://xp-framework.net/">link</a> to click on</text>', 
      $this->sourceOf(new Node('text', new \xml\PCData('A <a href="http://xp-framework.net/">link</a> to click on')))
    );
  }
  
  #[Test]
  public function getSourceWithDefaultEncoding() {
    $this->assertEquals(
      "<n>\xc3\x9cbercoder</n>",
      (new Node('n', "Übercoder"))->getSource(INDENT_NONE)
    );
  }

  #[Test]
  public function getSourceWithIsoEncoding() {
    $this->assertEquals(
      "<n>\xdcbercoder</n>",
      (new Node('n', "Übercoder"))->getSource(INDENT_NONE, 'iso-8859-1')
    );
  }

  #[Test]
  public function getSourceWithUtf8Encoding() {
    $this->assertEquals(
      "<n>\xc3\x9cbercoder</n>",
      (new Node('n', "Übercoder"))->getSource(INDENT_NONE, 'utf-8')
    );
  }

  #[Test]
  public function fromObject() { 
    $this->assertEquals(
      "<node>\n".
      "  <id>1549</id>\n".
      "  <color>green</color>\n".
      "  <name>Name goes here</name>\n".
      "</node>",
      $this->sourceOf(Node::fromObject(new class() extends Some {
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
  public function fromObjectShortName() {
    $this->assertEquals(
      '<Some/>',
      $this->sourceOf(Node::fromObject(new Some(), null))
    );
  }

  #[Test]
  public function as_string() {
    $this->assertEquals(
      'xml.Node(doc) { }',
      (new Node('doc'))->toString()
    );
  }

  #[Test]
  public function as_string_with_content() {
    $this->assertEquals(
      'xml.Node(test) { "Succeeded" }',
      (new Node('test', 'Succeeded'))->toString()
    );
  }

  #[Test]
  public function as_string_with_attributes() {
    $this->assertEquals(
      'xml.Node(a @href= "http://example.com") { }',
      (new Node('a', null, ['href' => 'http://example.com']))->toString()
    );
  }

  #[Test]
  public function as_string_with_children() {
    $this->assertEquals(
      "xml.Node(div) {\n".
      "  xml.Node(p) { \"Test\" }\n".
      "}",
      (new Node('div'))->withChild(new Node('p', 'Test'))->toString()
    );
  }
}