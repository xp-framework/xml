<?php namespace xml\unittest;

/**
 * Test class for Marshaller / Unmarshaller tests
 *
 * @see  xp://xml.unittest.UnmarshallerTest
 * @see  xp://xml.unittest.MarshallerTest
 */
class WindowType {
  protected $window= null;

  /**
   * Inject a window
   *
   * @param   string $name
   * @param   [:int] $windows handle lookup
   */
  #[@xmlmapping(['element' => '@owner-window', 'inject' => ['windows']])]
  public function setOwnerWindowNamed($name, array $windows) {
    $this->window= $windows[$name];
  }

  /**
   * Returns owner window ID
   *
   * @param   [:int] $windows handle lookup
   * @return  string name
   */
  #[@xmlfactory(['element' => '@owner-window', 'inject' => ['windows']])]
  public function getOwnerWindowName(array $windows) {
    return array_search($this->window, $windows);
  }

  /**
   * Sets window
   *
   * @param   int $id
   */
  public function setOwnerWindow($id) {
    $this->window= $id;
  }

  /**
   * Sets window
   *
   * @param   int $id
   * @return  xml.unittest.WindowType this
   */
  public function withOwnerWindow($id) {
    $this->window= $id;
    return $this;
  }

  /**
   * Gets window
   *
   * @return  int id
   */
  public function getOwnerWindow() {
    return $this->window;
  }
}