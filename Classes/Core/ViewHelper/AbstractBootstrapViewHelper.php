<?php
class Tx_Fluidwidget_Core_ViewHelper_AbstractBootstrapViewHelper extends Tx_Fluid_Core_Widget_AbstractWidgetViewHelper {

	/**
	 * Widget type - overridden by subclasses
	 *
	 * @var string
	 */
	protected $widgetType;

	/**
	 * @var Tx_Fluidwidget_Controller_BootstrapController
	 */
	protected $controller;

	/**
	 * @param Tx_Fluidwidget_Controller_BootstrapController $controller
	 */
	public function injectController(Tx_Fluidwidget_Controller_BootstrapController $controller) {
		$this->controller = $controller;
	}

	/**
	 * Initialize arguments
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerArgument('type', 'string', 'Widget type - match plugin function name in JS', FALSE, $this->widgetType);
		$this->registerArgument('for', 'string', 'DOM element ID associated with this Widget');
		$this->registerArgument('options', 'mixed', 'Options for the Widget. If this is not an array it will be treated as NULL');
	}

	/**
	 * Common render method. Does not output any content - just registers the Widget in JS
	 * @return void
	 */
	public function render() {

	}

}
