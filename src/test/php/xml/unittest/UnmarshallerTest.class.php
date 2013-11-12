<?php namespace xml\unittest;
 
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
    $this->assertClass($dialog, 'xml.unittest.DialogType');
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
    $this->assertClass($dialog, 'xml.unittest.DialogType');
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
    $this->assertClass($dialog, 'xml.unittest.DialogType');
    $this->assertTrue($dialog->hasButtons());
    $this->assertEquals(2, $dialog->numButtons());

    with ($ok= $dialog->buttonAt(0), $cancel= $dialog->buttonAt(1)); {
      $this->assertClass($ok, 'xml.unittest.ButtonType');
      $this->assertClass($cancel, 'xml.unittest.ButtonType');
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
    $this->assertClass($dialog, 'xml.unittest.DialogType');
    $this->assertEquals(array('ON_TOP', 'MODAL'), $dialog->getFlags());
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
    $this->assertClass($dialog, 'xml.unittest.DialogType');
    $this->assertEquals(array(
      'width' => '100',
      'height' => '100'
    ), $dialog->getOptions());
  }

  #[@test]
  public function unmarshallingAnInputStream() {
    $dialog= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<dialogtype id="stream.select"/>'), 'memory'),
      'xml.unittest.DialogType'
    );
    $this->assertClass($dialog, 'xml.unittest.DialogType');
    $this->assertEquals('stream.select', $dialog->getId());
  }

  #[@test, @expect('xml.XMLFormatException')]
  public function malformedStream() {
    $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<not-valid-xml'), 'memory'), 
      'xml.unittest.DialogType'
    );
  }

  #[@test, @expect('xml.XMLFormatException')]
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

  #[@test, @expect('xml.XMLFormatException')]
  public function malformedString() {
    Unmarshaller::unmarshal(
      '<not-valid-xml', 
      'xml.unittest.DialogType'
    );
  }

  #[@test, @expect('xml.XMLFormatException')]
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
    $this->assertInstanceOf('xml.unittest.DialogType', $object);
  }

  #[@test]
  public function nameBasedFactoryToButton() {
    $object= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<button/>')),
      'xml.unittest.NameBasedTypeFactory'
    );
    $this->assertInstanceOf('xml.unittest.ButtonType', $object);
  }

  #[@test, @expect('lang.reflect.TargetInvocationException')]
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
    $this->assertInstanceOf('xml.unittest.DialogType', $object);
  }

  #[@test]
  public function idBasedFactoryToButton() {
    $object= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<object id="button"/>')),
      'xml.unittest.IdBasedTypeFactory'
    );
    $this->assertInstanceOf('xml.unittest.ButtonType', $object);
  }

  #[@test, @expect('lang.reflect.TargetInvocationException')]
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
      array('windows' => array(
        'main'     => 1,
        'desktop'  => 0
      ))
    );
    $this->assertEquals(1, $window->getOwnerWindow());
  }

  #[@test, @expect('lang.IllegalArgumentException')]
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
    $this->assertInstanceOf('xml.unittest.ApplicationType', $app);
  }

  #[@test]
  public function casting() {
    $t= $this->fixture->unmarshalFrom(
      new StreamInputSource(new MemoryInputStream('<input id="name" disabled="true"/>')),
      'xml.unittest.TextInputType'
    );
    $this->assertInstanceOf('xml.unittest.TextInputType', $t);
    $this->assertTrue($t->getDisabled());
  }
}
