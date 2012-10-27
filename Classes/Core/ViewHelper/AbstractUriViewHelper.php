<?php
abstract class Tx_Fluidwidget_Core_ViewHelper_AbstractUriViewHelper extends Tx_Fluidwidget_Core_Widget_AbstractWidgetViewHelper {

	/**
	 * @var Tx_Fluidwidget_Controller_UriController
	 */
	protected $controller;

	/**
	 * Initialize arguments
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerArgument('mode', 'string', 'Linkage mode - use "passthrough" or "direct". Using passthrough, \
			the url or target file will be passed through this Widget before download starts. This is a way to enforce \
			access protection - for example, a file linked with "passthrough" would only be downloadable if the \
			plugin/content that displays this Widget is also accessible.', FALSE, 'direct');
		$this->registerArgument('target', 'string', 'URI resource style target. Absolute paths supported when using "passthrough"');
		$this->registerArgument('mimeType', 'string', 'If set, overrides automatically detected MIME type. Only applies to "passthrough" mode');
	}

	/**
	 * Common render method. Does not output any content - just registers the Widget in JS
	 * @return string
	 */
	public function render() {
		$this->arguments['content'] = $this->renderChildren();
		return $this->initiateSubRequest();
	}

}
