<?php namespace xml\unittest;
 
use io\streams\MemoryInputStream;
use lang\IllegalArgumentException;
use lang\reflect\TargetInvocationException;
use unittest\{Expect, Test, TestCase};
use xml\XMLFormatException;
use xml\meta\Unmarshaller;
use xml\parser\StreamInputSource;
use xml\unittest\{ApplicationType, ButtonType, DialogType, TextInputType};

/**
 * Test Unmarshaller API
 *
 * @see    xp://xml.meta.Unmarshaller
 */
class UnmarshallerTest extends TestCase {
  protected $fixture= null;

  /**
   * Creates fixture
   */
  public function setUp() {
    $this->fixture= new Unmarshaller();
  }

  #[Test]
  public function idAttribute() {
    $dialog= $this->fixture->unmarshalFrom(new StreamInputSource(new MemoryInputStream('
      <dialogtype id="file.open">
        <caption/>
      </dialogtype>')),
      'xml.unittest.DialogType'
    );
    $this->assertInstanceOf(DialogType::class, $dialog);
    $this->assertEquals('file.open', $dialog->getId());
  }

  #[Test]
  public function captionNode() {
    $dialog= $this->fixture->unmarshalFrom(new StreamInputSource(new MemoryInputStream('
      <dialogtype id="">
        <caption>Open a file &gt; Choose</caption>
      </dialogtype>')),
      'xml.unittest.DialogType'
    );
    $this->assertInstanceOf(DialogType::class, $dialog);
    $this->assertEquals('Open a file > Choose', $dialog->getCaption());
  }
  
  #[Test]
  public function buttonsNodeSet() {
    $dialog= $this->fixture->unmarshalFrom(new StreamInputSource(new MemoryInputStream('
      <dialogtype id="">
        <caption>Really delete the file &quot;Ü&quot;?</caption>
        <button id="ok">Yes, go ahead</button>
        <button id="cancel">No, please don\'t!</button>
      </dialogtype>')), 
      'xml.unittest.DialogType'
    );
    $this->assertInstanceOf(DialogType::class, $dialog);
    $this->assertTrue($dialog->hasButtons());
    $this->assertEquals(2, $dialog->numButtons());

    with ($ok= $dialog->buttonAt(0), $cancel= $dialog->buttonAt(1)); {
      $this->assertInstanceOf(ButtonType::class, $ok);
      $this->assertInstanceOf(ButtonType::class, $cancel);
      $this->assertEquals('ok', $ok->getId());
      $this->assertEquals('cancel', $cancel->getId());
      $this->assertEquals('Yes, go ahead', $ok->getCaption());
      $this->assertEquals('No, please don\'t!', $cancel->getCaption());
    }
  }
  
  #[Test]
  public function usingPassWithScalars() {
    $dialog= $this->fixture->unmarshalFrom(new StreamInputSource(new MemoryInputStream('
      <dialogtype id="">
        <flags>ON_TOP|MODAL</flags>
      </dialogtype>')), 
      'xml.unittest.DialogType'
    );
    $this->assertInstanceOf(DialogType::class, $dialog);
    $this->assertEquals(['ON_TOP', 'MODAL'], $dialog->getFlags());
  }
  
  #[Test]
  public function usingPassWithNodes() {
    $dialog= $this->fixture->unmarshalFrom(new StreamInputSource(new MemoryInputStream('
      <dialogtype id="">
        <options>
          <option name="width" value="100"/>
          <option name="height" value="100"/>
        </options>
      </dialogtype>')), 
      'xml.unittest.DialogType'
    );
    $this->assertInstanceOf(DialogType::class, $dialog);
    $this->assertEquals([
      'width' => '100',
      'height' => '100'
    ], $dialog->getOptions());
  }

  #[Test]
  public function unmarshallingAnInputStream() {
    $dialog= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<dialogtype id="stream.select"/>'), 'memory'),
      'xml.unittest.DialogType'
    );
    $this->assertInstanceOf(DialogType::class, $dialog);
    $this->assertEquals('stream.select', $dialog->getId());
  }

  #[Test, Expect(XMLFormatException::class)]
  public function malformedStream() {
    $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<not-valid-xml'), 'memory'), 
      'xml.unittest.DialogType'
    );
  }

  #[Test, Expect(XMLFormatException::class)]
  public function emptyStream() {
    $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream(''), 'memory'), 
      'xml.unittest.DialogType'
    );
  }

  #[Test]
  public function deprecatedUsage() {
    $xml= '<dialogtype id="file.open"/>';
    $type= 'xml.unittest.DialogType';
    $this->assertEquals(
      Unmarshaller::unmarshal($xml, $type),
      $this->fixture->unmarshalFrom(new StreamInputSource(new MemoryInputStream($xml)), $type)
    );
  }

  #[Test, Expect(XMLFormatException::class)]
  public function malformedString() {
    Unmarshaller::unmarshal(
      '<not-valid-xml', 
      'xml.unittest.DialogType'
    );
  }

  #[Test, Expect(XMLFormatException::class)]
  public function emptyString() {
    Unmarshaller::unmarshal(
      '', 
      'xml.unittest.DialogType'
    );
  }

  #[Test]
  public function nameBasedFactoryToDialog() {
    $object= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<dialog/>')),
      'xml.unittest.NameBasedTypeFactory'
    );
    $this->assertInstanceOf(DialogType::class, $object);
  }

  #[Test]
  public function nameBasedFactoryToButton() {
    $object= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<button/>')),
      'xml.unittest.NameBasedTypeFactory'
    );
    $this->assertInstanceOf(ButtonType::class, $object);
  }

  #[Test, Expect(TargetInvocationException::class)]
  public function nameBasedFactoryToUnknown() {
    $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<unknown/>')),
      'xml.unittest.NameBasedTypeFactory'
    );
  }

  #[Test]
  public function idBasedFactoryToDialog() {
    $object= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<object id="dialog"/>')),
      'xml.unittest.IdBasedTypeFactory'
    );
    $this->assertInstanceOf(DialogType::class, $object);
  }

  #[Test]
  public function idBasedFactoryToButton() {
    $object= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<object id="button"/>')),
      'xml.unittest.IdBasedTypeFactory'
    );
    $this->assertInstanceOf(ButtonType::class, $object);
  }

  #[Test, Expect(TargetInvocationException::class)]
  public function idBasedFactoryToUnknown() {
    $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<object id="unknown"/>')),
      'xml.unittest.IdBasedTypeFactory'
    );
  }

  #[Test]
  public function inject() {
    $window= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<window owner-window="main"/>')),
      'xml.unittest.WindowType',
      ['windows' => [
        'main'     => 1,
        'desktop'  => 0
      ]]
    );
    $this->assertEquals(1, $window->getOwnerWindow());
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function injectionFails() {
    $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<window owner-window="main"/>')),
      'xml.unittest.WindowType'
    );
  }

  #[Test]
  public function namespaces() {
    $app= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<app:application xmlns:app="http://projects.xp-framework.net/xmlns/app"/>')),
      'xml.unittest.ApplicationType'
    );
    $this->assertInstanceOf(ApplicationType::class, $app);
  }

  #[Test]
  public function casting() {
    $t= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<input id="name" disabled="true"/>')),
      'xml.unittest.TextInputType'
    );
    $this->assertInstanceOf(TextInputType::class, $t);
    $this->assertTrue($t->getDisabled());
  }
}