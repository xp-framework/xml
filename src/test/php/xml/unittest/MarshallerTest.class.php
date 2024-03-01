<?php namespace xml\unittest;

use lang\IllegalArgumentException;
use unittest\Assert;
use unittest\{Expect, Test, TestCase};
use xml\Node;
use xml\meta\Marshaller;

class MarshallerTest {
  protected $fixture= null;

  /**
   * Creates fixture
   */
  #[Before]
  public function setUp() {
    $this->fixture= new Marshaller();
  }

  /**
   * Compares XML after stripping all whitespace between tags of both 
   * expected and actual strings.
   *
   * @see     xp://unittest.TestCase#assertEquals
   * @param   string $expect
   * @param   xml.Node $node
   * @throws  unittest.AssertionFailedError
   */
  public function assertMarshalled($expect, $node) {
    Assert::equals(
      preg_replace('#>[\s\r\n]+<#', '><', trim($expect)),
      preg_replace('#>[\s\r\n]+<#', '><', trim($node->getSource(INDENT_DEFAULT)))
    );
  }

  #[Test]
  public function marshalToReturnsGivenNode() {
    $n= new Node('node');
    Assert::equals($n, $this->fixture->marshalTo($n, new Some()));
  }

  #[Test]
  public function nameOfNodeUsed() {
    $dialog= new DialogType();
    $this->assertMarshalled('
      <dialogtype id="">
        <caption/>
        <flags/>
        <options/>
      </dialogtype>',
      $this->fixture->marshalTo(new Node('dialogtype'), $dialog)
    );
  }

  #[Test]
  public function marshalToCreatesNewNodeWhenNoneGiven() {
    Assert::equals(new Node('some'), $this->fixture->marshalTo(null, new Some()));
  }

  #[Test]
  public function classAnnotationSuppliesName() {
    Assert::equals(new Node('scroll'), $this->fixture->marshalTo(null, new ScrollBarType()));
  }

  #[Test]
  public function idAttribute() {
    $dialog= new DialogType();
    $dialog->setId('file.open');
    
    $this->assertMarshalled('
      <dialogtype id="file.open">
        <caption/>
        <flags/>
        <options/>
      </dialogtype>', 
      $this->fixture->marshalTo(new Node('dialogtype'), $dialog)
    );
  }
  
  #[Test]
  public function captionNode() {
    $dialog= new DialogType();
    $dialog->setCaption('Open a file > Choose');
    
    $this->assertMarshalled('
      <dialogtype id="">
        <caption>Open a file &gt; Choose</caption>
        <flags/>
        <options/>
      </dialogtype>', 
      $this->fixture->marshalTo(new Node('dialogtype'), $dialog)
    );
  }

  #[Test]
  public function buttonsNodeSet() {
    $dialog= new DialogType();
    $dialog->setCaption('Really delete the file "Ü"?');

    with ($ok= $dialog->addButton(new ButtonType())); {
      $ok->setId('ok');
      $ok->setCaption('Yes, go ahead');
    }
    with ($cancel= $dialog->addButton(new ButtonType())); {
      $cancel->setId('cancel');
      $cancel->setCaption('No, please don\'t!');
    }

    $this->assertMarshalled('
      <dialogtype id="">
        <caption>Really delete the file &quot;Ü&quot;?</caption>
        <button id="ok">Yes, go ahead</button>
        <button id="cancel">No, please don\'t!</button>
        <flags/>
        <options/>
      </dialogtype>', 
      $this->fixture->marshalTo(new Node('dialogtype'), $dialog)
    );
  }
  
  #[Test]
  public function emptyMembers() {
    $dialog= new DialogType();
    $this->assertMarshalled('
      <dialogtype id="">
        <caption/>
        <flags/>
        <options/>
      </dialogtype>', 
      $this->fixture->marshalTo(new Node('dialogtype'), $dialog)
    );
  }

  #[Test]
  public function asTree() {
    $dialog= new DialogType();
    $dialog->setId('file.open');

    $node= $this->fixture->marshalTo(new Node('dialog'), $dialog);
    Assert::instance(Node::class, $node);
    Assert::equals('dialog', $node->getName());
    Assert::equals('file.open', $node->getAttribute('id'));
  }

  #[Test]
  public function deprecatedUsage() {
    $dialog= new DialogType();
    Assert::equals(
      Marshaller::marshal($dialog),
      $this->fixture->marshalTo(new Node('dialogtype'), $dialog)->getSource(INDENT_DEFAULT)
    );
  }

  #[Test]
  public function deprecatedUsageWithNamespace() {
    $app= new ApplicationType();
    Assert::equals(
      Marshaller::marshal($app),
      $this->fixture->marshalTo(new Node('ApplicationType'), $app)->getSource(INDENT_DEFAULT)
    );
  }

  #[Test]
  public function inject() {
    $window= (new WindowType())->withOwnerWindow(1);
    $this->assertMarshalled(
      '<window owner-window="main"/>',
      $this->fixture->marshalTo(new Node('window'), $window, ['windows' => [
        'main'     => 1,
        'desktop'  => 0
      ]])
    );
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function injectionFails() {
    $window= (new WindowType())->withOwnerWindow(1);
    $this->fixture->marshalTo(new Node('window'), $window);
  }

  #[Test]
  public function namespaces() {
    $this->assertMarshalled(
      '<app:application xmlns:app="http://projects.xp-framework.net/xmlns/app"/>',
      $this->fixture->marshalTo(new Node('application'), new ApplicationType())
    );
  }

  #[Test]
  public function casting() {
    $t= new TextInputType();
    $t->setId('name');
    $t->setDisabled(true);

    $this->assertMarshalled(
      '<input id="name" disabled="true"/>',
      $this->fixture->marshalTo(new Node('input'), $t)
    );
  }
}