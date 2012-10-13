<?php
class Tx_Fluidwidget_ViewHelpers_Content_AjaxFluxContentViewHelper extends Tx_Fluidwidget_ViewHelpers_Request_DispatchViewHelper {

	/**
	 * @var Tx_Fluidwidget_Controller_SubRequestController
	 */
	protected $controller;

	/**
	 * @var string
	 */
	protected $tagName = 'section';

	/**
	 * @var boolean
	 */
	protected $ajaxWidget = TRUE;

	/**
	 * @param Tx_Fluidwidget_Controller_SubRequestController $controller
	 */
	public function injectController(Tx_Fluidwidget_Controller_SubRequestController $controller) {
		$this->controller = $controller;
	}

	/**
	 * Initialize arguments
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerArgument('parentUid', 'integer', 'UID of the parent content element', TRUE);
		$this->registerArgument('area', 'string', 'Name of the area from which to render content', TRUE);
		$this->overrideArgument('controller', 'string', 'Controller', FALSE, 'Flux');
		$this->overrideArgument('pluginName', 'string', 'Plugin name', FALSE, 'API');
		$this->overrideArgument('extensionName', 'string', 'Extension name', FALSE, 'Flux');
		$this->overrideArgument('ajax', 'boolean', 'On by default; this is after all an Ajax Widget', FALSE, TRUE);
	}

	/**
	 * @return string
	 */
	public function render() {
		$this->arguments['arguments']['localizedUid'] = $this->arguments['parentUid'];
		$this->arguments['arguments']['area'] = $this->arguments['area'];
		$this->arguments['action'] = 'renderChildContent';
		return parent::render();
	}

}