<?php
class Tx_Fluidwidget_ViewHelpers_Request_DispatchViewHelper extends Tx_Fluidwidget_Core_ViewHelper_AbstractTagBasedWidgetViewHelper {

	/**
	 * @var Tx_Fluidwidget_Controller_SubRequestController
	 */
	protected $controller;

	/**
	 * @var string
	 */
	protected $tagName = 'section';

	/**
	 * @param Tx_Fluidwidget_Controller_SubRequestController $controller
	 */
	public function injectController(Tx_Fluidwidget_Controller_SubRequestController $controller) {
		$this->controller = $controller;
	}

	/**
	 * Initialize possible arguments
	 */
	public function initializeArguments() {
		$this->registerArgument('action', 'string', 'Action to be performed, defaults to the default action if pluginName is specified, if not then the current Request action is inherited');
		$this->registerArgument('controller', 'string', 'Controller to be requested, defaults to the default controller if pluginName is specified, if not then the current Request controller is inherited');
		$this->registerArgument('pluginName', 'string', 'Plugin name to be requested - if left out, current pluginName is inherited');
		$this->registerArgument('extensionName', 'string', 'Extension name of extension containing the plugin to be requested. If left out, current extensionName is inherited');
		$this->registerArgument('arguments', 'array', 'Arguments to be passed to the action. If a non-array value is encountered, an array transformation is attempted. If unsuccesful, fallback to an empty array is performed to silently suppress errors. Enable "debug" on this ViewHelper to switch this suppression off.');
		$this->registerArgument('pageUid', 'integer', 'Optional page UID to use when dispatching the Request (note: if this argument is used, the target URL is requested over HTTP and will contain design output unless this is prevented! This is done in order to completely isolate the Request)', FALSE, 0);
		$this->registerArgument('typeNum', 'integer', 'Optional typeNum to use in the Request - requires that this typeNum is registered in order to function');
		$this->registerArgument('format', 'string', 'Format to use in the Request. Default value is inherited from the current context', FALSE, NULL);
		$this->registerArgument('ajax', 'boolean', 'If TRUE, only an empty container is output and AJAX is used to request the final Request URI when the page is loaded. jQuery is required!', FALSE, FALSE);
		$this->registerArgument('delay', 'integer', 'Optional delay before loading through AJAX - only applies if AJAX is enabled.');
		$this->registerArgument('debug', 'boolean', 'If enabled, avoids supressing errors that may be encountered while building/dispatching the sub request', FALSE, FALSE);
	}

	/**
	 * Dispatches
	 */
	public function render() {
		$actionName = $this->arguments['action'];
		$controllerName = $this->getArgumentValueWithFallbackFromCurrentRequest('controller', 'controllerName');
		$pluginName = $this->getArgumentValueWithFallbackFromCurrentRequest('pluginName');
		$extensionName = $this->getArgumentValueWithFallbackFromCurrentRequest('extensionName');
		$arguments = $this->getArgumentValueWithFallbackFromCurrentRequest('arguments');
		$format = $this->getArgumentValueWithFallbackFromCurrentRequest('format');
		$requestPageUid = (TYPO3_MODE === 'FE' ? $GLOBALS['TSFE']->id : t3lib_div::_GET('id'));
		$pageUid = $this->arguments['pageUid'] ? $this->arguments['pageUid'] : $requestPageUid;
		if (is_array($arguments) === FALSE) {
			if ($this->arguments['debug']) {
				throw new Exception('DispatchViewHelper attribute "arguments" was not an array. The value that was used may not be output friendly - please use f:debug on the value manually', 1350072226);
			}
		}
		try {
			if ($this->arguments['ajax'] || ($pageUid !== $requestPageUid && $this->arguments['pageUid'])) {
					// AJAX request; let the client browser perform the request and fill a container
				$settings = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'FluidWidget', 'SubRequest');
				$uriBuilder = $this->controller->getUriBuilder();
				$arguments['extensionName'] = $extensionName;
				$arguments['pluginName'] = $pluginName;
				$uri = $uriBuilder->setRequest($this->controllerContext->getRequest())
					->setTargetPageType($settings['ajaxTypeNum'])
					->setFormat($format)->setArguments($arguments)
					->setCreateAbsoluteUri(TRUE)->setTargetPageUid($pageUid)
					->uriFor($actionName, $arguments, $controllerName, $extensionName, $pluginName);
				$this->tag->addAttribute('data-url', $uri);
				if ($this->arguments['delay']) {
					$this->tag->addAttribute('data-delay', $this->arguments['delay']);
				}
				$this->tag->addAttribute('role', 'ajax-loader');
				$this->tag->forceClosingTag(TRUE);
				$this->tag->setContent($this->renderChildren());
				return $this->tag->render();
			}
			return $this->controller->dispatchRequest($actionName, $controllerName, $extensionName, $pluginName, $arguments)->getContent();
		} catch (Exception $error) {
			if ($this->arguments['debug']) {
				throw $error;
			}
			return $error->getMessage() . ' (' . $error->getCode() . ')';
		}
	}

	/**
	 * Fetches the specified value, or fallback value, of the requested $argumentName
	 *
	 * @param string $argumentName The argument to fetch
	 * @param string $requestArgumentName If specified, reads this attribute instead of $argumentName from the Request; usually a "Name" suffix would be used in the Request
	 * @return mixed
	 */
	protected function getArgumentValueWithFallbackFromCurrentRequest($argumentName, $requestArgumentName=NULL) {
		if ($this->arguments[$argumentName]) {
			return $this->arguments[$argumentName];
		}
		$request = $this->controllerContext->getRequest();
		try {
			return Tx_Extbase_Reflection_ObjectAccess::getProperty($request, $requestArgumentName !== NULL ? $requestArgumentName : $argumentName);
		} catch (Exception $error) {
			if ($this->arguments['debug']) {
				throw $error;
			}
		}
		return NULL;
	}

}
