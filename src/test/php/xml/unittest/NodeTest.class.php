<?php namespace xml\unittest;
 
use xml\XMLFormatException;
use lang\IllegalArgumentException;
use lang\Error;
use lang\Object;
use xml\Node;
use unittest\actions\RuntimeVersion;

/**
 * Test XML Node class
 *
 * @see   xp://net.xp_framework.unittest.xml.TreeTest 
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
  
  #[@test]
  public function attributeAccessors() {
    $n= new Node('node');
    $n->setAttribute('id', 1);
    $this->assertTrue($n->hasAttribute('id'));
    $this->assertFalse($n->hasAttribute('href'));
    $this->assertEquals(1, $n->getAttribute('id'));
  }

  #[@test]
  public function contentAccessors() {
    $content= '"This is interesting", Tom\'s friend said. "It\'s > 4 but < 2!"';
    $n= new Node('node');
    $n->setContent($content);
    $this->assertEquals($content, $n->getContent());
  }
  
  #[@test]
  public function nameAccessors() {
    $n= new Node('node');
    $n->setName('name');
    $this->assertEquals('name', $n->getName());
  }
  
  #[@test, @expect(XMLFormatException::class)]
  public function illegalContent() {
    $n= new Node('node');
    $n->setContent("\0");
  }
  
  #[@test, @expect(IllegalArgumentException::class), @action(new RuntimeVersion('<7.0.0-dev'))]
  public function addingNullChild() {
    (new Node('node'))->addChild(null);
  }

  #[@test, @expect(Error::class), @action(new RuntimeVersion('>=7.0.0-dev'))]
  public function addingNullChild7() {
    (new Node('node'))->addChild(null);
  }

  #[@test]
  public function addingReturnsChild() {
    $n= new Node('node');
    $child= new Node('node');
    $this->assertEquals($child, $n->addChild($child));
  }

  #[@test]
  public function withChildReturnsNode() {
    $n= new Node('node');
    $child= new Node('node');
    $this->assertEquals($n, $n->withChild($child));
  }
  
  #[@test]
  public function fromEmptyArray() {
    $this->assertEquals(
      '<node/>', 
      $this->sourceOf(Node::fromArray([], 'node'))
    );
  }

  #[@test]
  public function fromNumberArray() {
    $this->assertEquals(
      '<items><item>1</item><item>2</item></items>', 
      $this->sourceOf(Node::fromArray([1, 2], 'items'), INDENT_NONE)
    );
  }

  #[@test]
  public function fromCharacterArray() {
    $this->assertEquals(
      '<characters><character>1</character><character>&amp;</character><character>1</character></characters>', 
      $this->sourceOf(Node::fromArray(['1', '&', '1'], 'characters'), INDENT_NONE)
    );
  }
  
  #[@test]
  public function sourceOfEmptyNode() {
    $this->assertEquals(
      '<node/>', 
      $this->sourceOf(new Node('node'))
    );
  }

  #[@test]
  public function sourceOfNodeWithOneAttribute() {
    $this->assertEquals(
      '<node id="1"/>', 
      $this->sourceOf(new Node('node', null, ['id' => 1]))
    );
  }

  #[@test]
  public function sourceOfNodeWithTwoAttributes() {
    $this->assertEquals(
      '<node id="2" name="&amp;XML"/>', 
      $this->sourceOf(new Node('node', null, ['id' => 2, 'name' => '&XML']))
    );
  }

  #[@test]
  public function sourceOfNodeWithContent() {
    $this->assertEquals(
      '<expr>eval(\'1 &lt;&gt; 2 &amp;&amp; \') == &quot;Parse Error&quot;</expr>', 
      $this->sourceOf(new Node('expr', 'eval(\'1 <> 2 && \') == "Parse Error"'))
    );
  }

  #[@test]
  public function sourceOfNodeWithCData() {
    $this->assertEquals(
      '<text><![CDATA[Special characters: <>"\'&]]></text>', 
      $this->sourceOf(new Node('text', new \xml\CData('Special characters: <>"\'&')))
    );
  }

  #[@test]
  public function sourceOfNodeWithPCData() {
    $this->assertEquals(
      '<text>A <a href="http://xp-framework.net/">link</a> to click on</text>', 
      $this->sourceOf(new Node('text', new \xml\PCData('A <a href="http://xp-framework.net/">link</a> to click on')))
    );
  }
  
  #[@test]
  public function getSourceWithDefaultEncoding() {
    $this->assertEquals(
      "<n>\xc3\x9cbercoder</n>",
      (new Node('n', "Übercoder"))->getSource(INDENT_NONE)
    );
  }

  #[@test]
  public function getSourceWithIsoEncoding() {
    $this->assertEquals(
      "<n>\xdcbercoder</n>",
      (new Node('n', "Übercoder"))->getSource(INDENT_NONE, 'iso-8859-1')
    );
  }

  #[@test]
  public function getSourceWithUtf8Encoding() {
    $this->assertEquals(
      "<n>\xc3\x9cbercoder</n>",
      (new Node('n', "Übercoder"))->getSource(INDENT_NONE, 'utf-8')
    );
  }

  #[@test]
  public function fromObject() { 
    $this->assertEquals(
      "<node>\n".
      "  <id>1549</id>\n".
      "  <color>green</color>\n".
      "  <name>Name goes here</name>\n".
      "  <__id/>\n".
      "</node>",
      $this->sourceOf(Node::fromObject(newinstance('lang\Object', [], [
        'id'          => 1549,
        'color'       => 'green',
        'name'        => null,
        '__construct' => function() {
          $this->name= 'Name goes here';
        }
      ]), 'node'))
    );
  }

  #[@test]
  public function fromObjectShortName() {
    $this->assertEquals(
      "<Object·6>\n".
      "  <id>1549</id>\n".
      "  <color>green</color>\n".
      "  <name>Name goes here</name>\n".
      "  <__id/>\n".
      "</Object·6>",
      $this->sourceOf(Node::fromObject(newinstance('lang\Object', [], [
        'id'          => 1549,
        'color'       => 'green',
        'name'        => null,
        '__construct' => function() {
          $this->name= 'Name goes here';
        }
      ]), null))
    );
  }
}
