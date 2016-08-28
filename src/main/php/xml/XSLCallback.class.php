<?php namespace xml;

use lang\ElementNotFoundException;

/**
 * XSL callback class.
 *
 * @ext   dom
 * @test  xp://net.xp_framework.unittest.xml.XslCallbackTest
 * @see   php://xslt_registerphpfunctions
 */
class XSLCallback extends \lang\Object {
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
   * @param   lang.Object instance
   */
  public function registerInstance($name, $instance) {
    $this->instances[$name]= $instance;
  }
  
  /**
   * Remove all registered instances
   *
   */
  public function clearInstances() {
    $this->instances= [];
  }
  
  /**
   * Invoke method on a registered instance.
   *
   * @param   string instancename
   * @param   string methodname
   * @param   var* method arguments
   * @return  var
   * @throws  lang.IllegalArgumentException if the instance is not known
   * @throws  lang.ElementNotFoundException if the given method does not exist or is not xsl-accessible
   */
  public static function invoke($name, $method, ...$args) {
    if (!isset(self::$instance->instances[$name])) throw new \lang\IllegalArgumentException(
      'No such registered XSL callback instance: "'.$name.'"'
    );

    $instance= self::$instance->instances[$name];
    if (!($instance->getClass()->getMethod($method)->hasAnnotation('xslmethod'))) {
      throw new ElementNotFoundException('Instance "'.$name.'" does not have method "'.$method.'"');
    }

    // Call callback method
    return $instance->{$method}(...$args);
  }
}
