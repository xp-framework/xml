<?php namespace xml\unittest;

use io\streams\MemoryInputStream;
use lang\IllegalArgumentException;
use lang\reflect\TargetInvocationException;
use test\{Assert, Before, Expect, Test, TestCase};
use xml\XMLFormatException;
use xml\meta\Unmarshaller;
use xml\parser\StreamInputSource;
use xml\unittest\{ApplicationType, ButtonType, DialogType, TextInputType};

class UnmarshallerTest {
  protected $fixture= null;

  /**
   * Creates fixture
   */
  #[Before]
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
    Assert::instance(DialogType::class, $dialog);
    Assert::equals('file.open', $dialog->getId());
  }

  #[Test]
  public function captionNode() {
    $dialog= $this->fixture->unmarshalFrom(new StreamInputSource(new MemoryInputStream('
      <dialogtype id="">
        <caption>Open a file &gt; Choose</caption>
      </dialogtype>')),
      'xml.unittest.DialogType'
    );
    Assert::instance(DialogType::class, $dialog);
    Assert::equals('Open a file > Choose', $dialog->getCaption());
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
    Assert::instance(DialogType::class, $dialog);
    Assert::true($dialog->hasButtons());
    Assert::equals(2, $dialog->numButtons());

    with ($ok= $dialog->buttonAt(0), $cancel= $dialog->buttonAt(1)); {
      Assert::instance(ButtonType::class, $ok);
      Assert::instance(ButtonType::class, $cancel);
      Assert::equals('ok', $ok->getId());
      Assert::equals('cancel', $cancel->getId());
      Assert::equals('Yes, go ahead', $ok->getCaption());
      Assert::equals('No, please don\'t!', $cancel->getCaption());
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
    Assert::instance(DialogType::class, $dialog);
    Assert::equals(['ON_TOP', 'MODAL'], $dialog->getFlags());
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
    Assert::instance(DialogType::class, $dialog);
    Assert::equals([
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
    Assert::instance(DialogType::class, $dialog);
    Assert::equals('stream.select', $dialog->getId());
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
    Assert::equals(
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
    Assert::instance(DialogType::class, $object);
  }

  #[Test]
  public function nameBasedFactoryToButton() {
    $object= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<button/>')),
      'xml.unittest.NameBasedTypeFactory'
    );
    Assert::instance(ButtonType::class, $object);
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
    Assert::instance(DialogType::class, $object);
  }

  #[Test]
  public function idBasedFactoryToButton() {
    $object= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<object id="button"/>')),
      'xml.unittest.IdBasedTypeFactory'
    );
    Assert::instance(ButtonType::class, $object);
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
    Assert::equals(1, $window->getOwnerWindow());
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
    Assert::instance(ApplicationType::class, $app);
  }

  #[Test]
  public function casting() {
    $t= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<input id="name" disabled="true"/>')),
      'xml.unittest.TextInputType'
    );
    Assert::instance(TextInputType::class, $t);
    Assert::true($t->getDisabled());
  }
}