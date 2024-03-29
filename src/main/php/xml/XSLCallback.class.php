<?php namespace xml;

use lang\{ElementNotFoundException, IllegalArgumentException, Reflection};

/**
 * XSL callback class.
 *
 * @ext   xsl
 * @test  xp://xml.unittest.XslCallbackTest
 * @see   php://xslt_registerphpfunctions
 */
class XSLCallback {
  private $instances= [];
  private static $instance;
    
  static function __static() {
    self::$instance= new self();
  }
  
  /**
   * Retrieve instance
   *
   * @return  xml.XSLCallback
   */
  public static function getInstance() {
    return self::$instance;
  }
  
  /**
   * Register new instance
   *
   * @param   string name
   * @param   object instance
   */
  public function registerInstance($name, $instance) {
    $methods= [];
    foreach (Reflection::type($instance)->methods()->annotated(Xslmethod::class) as $method => $_) {
      $methods[$method]= true;
    }
    $this->instances[$name]= [$instance, $methods];
  }
  
  /**
   * Remove all registered instances
   *
   * @return void
   */
  public function clearInstances() {
    $this->instances= [];
  }
  
  /**
   * Invoke method on a registered instance.
   *
   * @param   string $name
   * @param   string $method
   * @param   var... $arguments
   * @return  var
   * @throws  lang.IllegalArgumentException if the instance is not known
   * @throws  lang.ElementNotFoundException if the given method does not exist or is not xsl-accessible
   */
  public static function invoke($name, $method, ...$args) {
    if (null === ($instance= self::$instance->instances[$name] ?? null)) {
      throw new IllegalArgumentException('No such registered XSL callback instance: "'.$name.'"');
    }

    if (!isset($instance[1][$method])) {
      throw new ElementNotFoundException('Instance "'.$name.'" does not have method "'.$method.'"');
    }

    return $instance[0]->{$method}(...$args);
  }
}