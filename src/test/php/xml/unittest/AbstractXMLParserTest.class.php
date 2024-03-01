<?php namespace xml\unittest;

use unittest\Assert;
use unittest\{Expect, Test, TestCase};
use xml\XMLFormatException;
use xml\parser\{InputSource, ParserCallback, XMLParser};

abstract class AbstractXMLParserTest {
  const NAME = 0;
  const ATTR = 1;
  const CHLD = 2;
  
  protected $parser= null;

  /**
   * Sets up test case
   */
  #[Before]
  public function setUp() {
    $this->parser= new XMLParser();
  }
  
  /**
   * Tears down test case
   */
  #[After]
  public function tearDown() {
    unset($this->parser);
  }
  
  /**
   * Returns an XML document by prepending the XML declaration to 
   * the given string and returning it.
   *
   * @param   string str
   * @param   bool decl default TRUE
   * @return  xml.parser.InputSource XML the source XML
   */
  protected abstract function source($str, $decl= true);
  
  /**
   * Creates a new callback
   *
   * @return  xml.parser.ParserCallback
   */
  protected function newCallback() {
    return newinstance(ParserCallback::class, [], '{
      protected
        $pointer  = array();
        
      public
        $tree     = NULL,
        $elements = array(),
        $encoding = NULL;
        
      public function onStartElement($parser, $name, $attrs) {
        $this->elements[]= $name;
        array_unshift($this->pointer, array($name, $attrs, array()));
      }

      public function onEndElement($parser, $name) {
        $e= array_shift($this->pointer);
        if (empty($this->pointer)) {
          $this->tree= $e;
        } else {
          $this->pointer[0][\xml\unittest\AbstractXMLParserTest::CHLD][]= $e;
        }
      }

      public function onCData($parser, $cdata) {
        $this->pointer[0][\xml\unittest\AbstractXMLParserTest::CHLD][]= trim($cdata);
      }

      public function onDefault($parser, $data) {
        $this->pointer[0][\xml\unittest\AbstractXMLParserTest::CHLD][]= trim($data);
      }

      public function onBegin($instance) {
        $this->encoding= $instance->getEncoding();
      }

      public function onError($instance, $exception) {
      }

      public function onFinish($instance) {
      }
    }');
  }

  #[Test]
  public function withoutDeclaration() {
    Assert::true($this->parser->parse($this->source('<root/>', true)));
  }

  #[Test, Expect(XMLFormatException::class)]
  public function emptyString() {
    $this->parser->parse($this->source('', false));
  }
  
  #[Test]
  public function withDeclaration() {
    Assert::true($this->parser->parse($this->source('<root/>')));
  }

  #[Test]
  public function tree() {
    $callback= $this->newCallback();
    $this->parser->setCallback($callback);
    $this->parser->parse($this->source('<book>
      <author><name>Timm</name></author>
      <chapter id="1">
        <title>Introduction</title>
        <paragraph>
          This is where it all started.
        </paragraph>
      </chapter>
    </book>'));
    Assert::equals('book', $callback->tree[self::NAME]);
    Assert::equals([], $callback->tree[self::ATTR]);
    
    with ($author= $callback->tree[self::CHLD][1]); {
      Assert::equals('author', $author[self::NAME]);
      Assert::equals([], $author[self::ATTR]);
    
      with ($name= $author[self::CHLD][0]); {
        Assert::equals('name', $name[self::NAME]);
        Assert::equals([], $name[self::ATTR]);
        Assert::equals(['Timm'], $name[self::CHLD]);
      }
    }

    with ($chapter= $callback->tree[self::CHLD][3]); {
      Assert::equals('chapter', $chapter[self::NAME]);
      Assert::equals(['id' => '1'], $chapter[self::ATTR]);

      with ($title= $chapter[self::CHLD][1]); {
        Assert::equals('title', $title[self::NAME]);
        Assert::equals([], $title[self::ATTR]);
        Assert::equals(['Introduction'], $title[self::CHLD]);
      }

      with ($paragraph= $chapter[self::CHLD][3]); {
        Assert::equals('paragraph', $paragraph[self::NAME]);
        Assert::equals([], $paragraph[self::ATTR]);
        Assert::equals(['This is where it all started.'], $paragraph[self::CHLD]);
      }
    }
  }

  #[Test]
  public function reusable() {
    for ($i= 0; $i < 4; $i++) {
      $callback= $this->newCallback();
      $this->parser->setCallback($callback);
      $this->parser->parse($this->source('<run id="'.$i.'"/>'));
      Assert::equals(
        ['run', ['id' => (string)$i], []], 
        $callback->tree, 
        'Run #'.$i
      );
    }
  }

  #[Test]
  public function errorOccursLate() {
    $callback= $this->newCallback();
    $this->parser->setCallback($callback);
    try {
      $this->parser->parse($this->source('<doc><h1>Title</h1><p>Text</p><img></doc>'));
      $this->fail('Parsed without problems', null, 'xml.XMLFormatException');
    } catch (\xml\XMLFormatException $expected) {
      Assert::equals(null, $callback->tree, 'Tree only set if entire doc parsed');

      Assert::equals(4, sizeof($callback->elements));
      Assert::equals('doc', $callback->elements[0]);
      Assert::equals('h1', $callback->elements[1]);
      Assert::equals('p', $callback->elements[2]);
      Assert::equals('img', $callback->elements[3]);
    }
  }

  #[Test, Expect(XMLFormatException::class)]
  public function withoutRoot() {
    $this->parser->parse($this->source(''));
  }

  #[Test, Expect(XMLFormatException::class)]
  public function unclosedTag() {
    $this->parser->parse($this->source('<a>'));
  }

  #[Test, Expect(XMLFormatException::class)]
  public function unclosedAttribute() {
    $this->parser->parse($this->source('<a href="http://>Click</a>'));
  }

  #[Test, Expect(XMLFormatException::class)]
  public function unclosedComment() {
    $this->parser->parse($this->source('<doc><!-- Comment</doc>'));
  }

  #[Test, Expect(XMLFormatException::class)]
  public function incorrectlyClosedComment() {
    $this->parser->parse($this->source('<doc><!-- Comment ></doc>'));
  }

  #[Test, Expect(XMLFormatException::class)]
  public function malformedComment() {
    $this->parser->parse($this->source('<doc><! Comment --></doc>'));
  }

  #[Test, Expect(XMLFormatException::class)]
  public function unclosedProcessingInstruction() {
    $this->parser->parse($this->source('<doc><?php echo "1"; </doc>'));
  }

  #[Test, Expect(XMLFormatException::class)]
  public function attributeRedefinition() {
    $this->parser->parse($this->source('<a id="1" id="2"/>'));
  }

  #[Test]
  public function quotesInsideAttributes() {
    $callback= $this->newCallback();
    $this->parser->setCallback($callback);
    $this->parser->parse($this->source('<n id="\'1\'" t=\'"_new"\' q="&apos;&quot;"/>'));
    Assert::equals(
      ['n', ['id' => "'1'", 't' => '"_new"', 'q' => '\'"'], []],
      $callback->tree
    );
  }

  #[Test]
  public function greaterSignInAttribute() {
    $callback= $this->newCallback();
    $this->parser->setCallback($callback);
    $this->parser->parse($this->source('<a id=">"/>'));
    Assert::equals(
      ['a', ['id' => '>'], []],
      $callback->tree
    );
  }

  #[Test, Expect(XMLFormatException::class)]
  public function smallerSignInAttribute() {
    $this->parser->parse($this->source('<a id="<"/>'));
  }

  #[Test]
  public function cdataSection() {
    $callback= $this->newCallback();
    $this->parser->setCallback($callback);
    $this->parser->parse($this->source('
      <doc>CDATA [<![CDATA[ <&> ]]>]</doc>
    '));
    Assert::equals([
      'doc', [], [
        'CDATA [', '<&>', ']'
      ]
    ], $callback->tree);
  }

  #[Test]
  public function processingInstruction() {
    $callback= $this->newCallback();
    $this->parser->setCallback($callback);
    $this->parser->parse($this->source('<doc><?php echo "1"; ?></doc>'));

    Assert::equals([
      'doc', [], [
        '<?php echo "1"; ?>'
      ]
    ], $callback->tree);
  }

  #[Test]
  public function comment() {
    $callback= $this->newCallback();
    $this->parser->setCallback($callback);
    $this->parser->parse($this->source('<doc><!-- Comment --></doc>'));

    Assert::equals([
      'doc', [], [
        '<!-- Comment -->'
      ]
    ], $callback->tree);
  }

  #[Test, Expect(XMLFormatException::class)]
  public function nestedCdata() {
    $callback= $this->newCallback();
    $this->parser->setCallback($callback);
    $this->parser->parse($this->source('
      <doc><![CDATA[ <![CDATA[ ]]> ]]></doc>
    '));
  }

  #[Test]
  public function predefinedEntities() {
    $callback= $this->newCallback();
    $this->parser->setCallback($callback);
    $this->parser->parse($this->source('
      <doc>&quot;3 &lt; 5 &apos;&amp;&apos; 5 &gt; 3&quot;</doc>
    '));

    Assert::equals([
      'doc', [], [
        '"', '3', '<', '5', "'", '&', "'", '5', '>', '3', '"'
      ]
    ], $callback->tree);
  }

  #[Test]
  public function hexEntity() {
    $callback= $this->newCallback();
    $this->parser->setCallback($callback);
    $this->parser->parse($this->source('
      <doc>&#169; 2001-2009 the XP team</doc>
    '));

    Assert::equals([
      'doc', [], [
        '©', '2001-2009 the XP team'
      ]
    ], $callback->tree);
  }

  #[Test]
  public function iso88591Conversion() {
    $this->parser->setEncoding('iso-8859-1');
    $callback= $this->newCallback();
    $this->parser->setCallback($callback);
    $this->parser->parse($this->source('
      <doc>The Ã¼bercoder returns</doc>
    '));

    Assert::equals('iso-8859-1', $callback->encoding);
    Assert::equals([
      'doc', [], [
        'The', 'übercoder returns'
      ]
    ], $callback->tree);
  }

  #[Test]
  public function utf8Conversion() {
    $this->parser->setEncoding('utf-8');
    $callback= $this->newCallback();
    $this->parser->setCallback($callback);
    $this->parser->parse($this->source('
      <doc>The Ã¼bercoder returns</doc>
    '));
    
    Assert::equals('utf-8', $callback->encoding);
    Assert::equals([
      'doc', [], [
        'The', 'Ã¼bercoder returns'
      ]
    ], $callback->tree);
  }

  #[Test, Expect(XMLFormatException::class)]
  public function undeclaredEntity() {
    $this->parser->parse($this->source('<doc>&nbsp;</doc>'));
  }

  #[Test, Expect(XMLFormatException::class)]
  public function undeclaredEntityInAttribute() {
    $this->parser->parse($this->source('<doc><a href="&nbsp;"/></doc>'));
  }

  #[Test, Expect(XMLFormatException::class)]
  public function doubleRoot() {
    $this->parser->parse($this->source('<doc/><doc/>'));
  }

  #[Test, Expect(XMLFormatException::class)]
  public function docTypeWithoutContent() {
    $this->parser->parse($this->source('<!DOCTYPE doc ]>'));
  }

  #[Test]
  public function characterEntity() {
    $callback= $this->newCallback();
    $this->parser->setCallback($callback);
    $this->parser->parse($this->source('
      <!DOCTYPE doc [ <!ENTITY copy "&#169;"> ]>
      <doc>Copyright: &copy;</doc>
    '));

    Assert::equals([
      'doc', [], [
        'Copyright:', '&copy;'
      ]
    ], $callback->tree);
  }

  #[Test]
  public function entity() {
    $callback= $this->newCallback();
    $this->parser->setCallback($callback);
    $this->parser->parse($this->source('
      <!DOCTYPE doc [ <!ENTITY copyright "2009 The XP team"> ]>
      <doc>Copyright: &copyright;</doc>
    '));

    Assert::equals([
      'doc', [], [
        'Copyright:', '&copyright;'
      ]
    ], $callback->tree);
  }

  #[Test]
  public function entityInAttribute() {
    $callback= $this->newCallback();
    $this->parser->setCallback($callback);
    $this->parser->parse($this->source('
      <!DOCTYPE doc [ <!ENTITY copyright "2009 The XP team"> ]>
      <doc><book copyright="Copyright &copyright;"/></doc>
    '));

    Assert::equals([
      'doc', [], [
        ['book', ['copyright' => 'Copyright 2009 The XP team'], []],
      ]
    ], $callback->tree);
  }

  #[Test]
  public function entityExpansion() {
    $callback= $this->newCallback();
    $this->parser->setCallback($callback);
    $this->parser->parse($this->source('
      <!DOCTYPE doc [ 
        <!ENTITY year "2009"> 
        <!ENTITY copyright "&year; The XP team"> 
      ]>
      <doc><book copyright="Copyright &copyright;"/></doc>
    '));

    Assert::equals([
      'doc', [], [
        ['book', ['copyright' => 'Copyright 2009 The XP team'], []],
      ]
    ], $callback->tree);
  }

  #[Test]
  public function externalEntity() {
    $callback= $this->newCallback();
    $this->parser->setCallback($callback);
    $this->parser->parse($this->source('
      <!DOCTYPE doc [ <!ENTITY copyright SYSTEM "http://xp-framework.net/copyright.txt" > ]>
      <doc>Copyright: &copyright;</doc>
    '));

    Assert::equals([
      'doc', [], ['Copyright:'],
    ], $callback->tree);
  }

  #[Test, Expect(XMLFormatException::class)]
  public function externalEntityInAttribute() {
    $callback= $this->newCallback();
    $this->parser->setCallback($callback);
    $this->parser->parse($this->source('
      <!DOCTYPE doc [ <!ENTITY copyright SYSTEM "http://xp-framework.net/copyright.txt" > ]>
      <doc><book copyright="Copyright &copyright;"/></doc>
    '));
  }
}