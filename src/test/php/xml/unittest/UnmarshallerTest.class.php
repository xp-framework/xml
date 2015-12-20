<?php namespace xml\unittest;
 
use xml\unittest\DialogType;
use xml\unittest\ButtonType;
use xml\XMLFormatException;
use lang\reflect\TargetInvocationException;
use lang\IllegalArgumentException;
use xml\unittest\ApplicationType;
use xml\unittest\TextInputType;
use unittest\TestCase;
use xml\meta\Unmarshaller;
use io\streams\MemoryInputStream;
use xml\parser\StreamInputSource;

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

  #[@test]
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

  #[@test]
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
  
  #[@test]
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
  
  #[@test]
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
  
  #[@test]
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

  #[@test]
  public function unmarshallingAnInputStream() {
    $dialog= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<dialogtype id="stream.select"/>'), 'memory'),
      'xml.unittest.DialogType'
    );
    $this->assertInstanceOf(DialogType::class, $dialog);
    $this->assertEquals('stream.select', $dialog->getId());
  }

  #[@test, @expect(XMLFormatException::class)]
  public function malformedStream() {
    $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<not-valid-xml'), 'memory'), 
      'xml.unittest.DialogType'
    );
  }

  #[@test, @expect(XMLFormatException::class)]
  public function emptyStream() {
    $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream(''), 'memory'), 
      'xml.unittest.DialogType'
    );
  }

  #[@test]
  public function deprecatedUsage() {
    $xml= '<dialogtype id="file.open"/>';
    $type= 'xml.unittest.DialogType';
    $this->assertEquals(
      Unmarshaller::unmarshal($xml, $type),
      $this->fixture->unmarshalFrom(new StreamInputSource(new MemoryInputStream($xml)), $type)
    );
  }

  #[@test, @expect(XMLFormatException::class)]
  public function malformedString() {
    Unmarshaller::unmarshal(
      '<not-valid-xml', 
      'xml.unittest.DialogType'
    );
  }

  #[@test, @expect(XMLFormatException::class)]
  public function emptyString() {
    Unmarshaller::unmarshal(
      '', 
      'xml.unittest.DialogType'
    );
  }

  #[@test]
  public function nameBasedFactoryToDialog() {
    $object= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<dialog/>')),
      'xml.unittest.NameBasedTypeFactory'
    );
    $this->assertInstanceOf(DialogType::class, $object);
  }

  #[@test]
  public function nameBasedFactoryToButton() {
    $object= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<button/>')),
      'xml.unittest.NameBasedTypeFactory'
    );
    $this->assertInstanceOf(ButtonType::class, $object);
  }

  #[@test, @expect(TargetInvocationException::class)]
  public function nameBasedFactoryToUnknown() {
    $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<unknown/>')),
      'xml.unittest.NameBasedTypeFactory'
    );
  }

  #[@test]
  public function idBasedFactoryToDialog() {
    $object= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<object id="dialog"/>')),
      'xml.unittest.IdBasedTypeFactory'
    );
    $this->assertInstanceOf(DialogType::class, $object);
  }

  #[@test]
  public function idBasedFactoryToButton() {
    $object= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<object id="button"/>')),
      'xml.unittest.IdBasedTypeFactory'
    );
    $this->assertInstanceOf(ButtonType::class, $object);
  }

  #[@test, @expect(TargetInvocationException::class)]
  public function idBasedFactoryToUnknown() {
    $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<object id="unknown"/>')),
      'xml.unittest.IdBasedTypeFactory'
    );
  }

  #[@test]
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

  #[@test, @expect(IllegalArgumentException::class)]
  public function injectionFails() {
    $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<window owner-window="main"/>')),
      'xml.unittest.WindowType'
    );
  }

  #[@test]
  public function namespaces() {
    $app= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<app:application xmlns:app="http://projects.xp-framework.net/xmlns/app"/>')),
      'xml.unittest.ApplicationType'
    );
    $this->assertInstanceOf(ApplicationType::class, $app);
  }

  #[@test]
  public function casting() {
    $t= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<input id="name" disabled="true"/>')),
      'xml.unittest.TextInputType'
    );
    $this->assertInstanceOf(TextInputType::class, $t);
    $this->assertTrue($t->getDisabled());
  }
}
