<?php
class Tx_Fluidwidget_Controller_SubRequestController extends Tx_Fluidwidget_Core_Widget_AbstractWidgetController implements Tx_Fluidwidget_Core_Widget_WidgetControllerInterface {

	/**
	 * @var array
	 */
	protected $supportedRequestTypes = array('TYPO3\CMS\Fluid\Core\Widget\WidgetRequest', 'TYPO3\CMS\Extbase\Mvc\Web\Request');

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @var string
	 */
	protected $requestType = 'TYPO3\CMS\Extbase\Mvc\Web\Request';

	/**
	 * @var string
	 */
	protected $responseType = 'TYPO3\CMS\Extbase\Mvc\Web\Response';

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
	 */
	protected $uriBuilder;

	/**
	 * CONSTRUCTOR
	 */
	public function initializeObject() {
		$this->uriBuilder = $this->objectManager->get('TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder');
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Mvc\Dispatcher $dispatcher
	 */
	public function injectDispatcher(\TYPO3\CMS\Extbase\Mvc\Dispatcher $dispatcher) {
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Handles a request. The result output is returned by altering the given response.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The request object
	 * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response The response, modified by this handler
	 * @throws TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
	 * @return void
	 * @api
	 */
	public function processRequest(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response) {
		if ($request instanceof \TYPO3\CMS\Fluid\Core\Widget\WidgetRequest) {
			$this->widgetConfiguration = $request->getWidgetContext()->getWidgetConfiguration();
		}
		if (!$this->canProcessRequest($request)) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException(get_class($this) . ' does not support requests of type "' . get_class($request) . '". Supported types are: ' . implode(' ', $this->supportedRequestTypes), 1187701131);
		}

		$this->request = $request;
		$this->request->setDispatched(TRUE);
		$this->response = $response;

		$this->uriBuilder = $this->objectManager->get('TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder');
		$this->uriBuilder->setRequest($request);

		$this->actionMethodName = $this->resolveActionMethodName();

		$this->initializeActionMethodArguments();
		$this->initializeActionMethodValidators();

		$this->initializeAction();
		$actionInitializationMethodName = 'initialize' . ucfirst($this->actionMethodName);
		if (method_exists($this, $actionInitializationMethodName)) {
			call_user_func(array($this, $actionInitializationMethodName));
		}

		$this->mapRequestArgumentsToControllerArguments();
		$this->checkRequestHash();
		$this->controllerContext = $this->buildControllerContext();
		$this->view = $this->resolveView();
		if ($this->view !== NULL) {
			$this->initializeView($this->view);
		}
		$this->callActionMethod();
	}

	/**
	 * Allows the widget template root path to be overriden via the framework configuration,
	 * e.g. plugin.tx_extension.view.widget.<WidgetViewHelperClassName>.templateRootPath
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view
	 * @return void
	 */
	protected function setViewConfiguration(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view) {
		$extbaseFrameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if ($this->request instanceof \TYPO3\CMS\Fluid\Core\Widget\WidgetRequest) {
			$widgetViewHelperClassName = $this->request->getWidgetContext()->getWidgetViewHelperClassName();
		}

		if (isset($extbaseFrameworkConfiguration['view']['widget'][$widgetViewHelperClassName]['templateRootPath'])
			&& strlen($extbaseFrameworkConfiguration['view']['widget'][$widgetViewHelperClassName]['templateRootPath']) > 0
			&& method_exists($view, 'setTemplateRootPath')) {
			$view->setTemplateRootPath(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($extbaseFrameworkConfiguration['view']['widget'][$widgetViewHelperClassName]['templateRootPath']));
		}
	}

	/**
	 * @return mixed
	 */
	public function requestAction() {
		$requestArguments = $_REQUEST;
		$extensionName = $requestArguments['extensionName'];
		$pluginName = $requestArguments['pluginName'];
		unset($requestArguments['type'], $requestArguments['cHash'], $requestArguments['extensionName'], $requestArguments['pluginName']);
		$scope = key($requestArguments);
		foreach (array_keys($requestArguments) as $key) {
			if (strpos($key, 'tx_') === 0) {
				$scope = $key;
				break;
			}
		}
		$arguments = $requestArguments[$scope];
		return $this->dispatchRequest($arguments['action'], $arguments['controller'], $extensionName, $pluginName, $arguments);
	}

	/**
	 * Inline Controller Action
	 *
	 * Inlines (outputs/returns) the result of calling a Controller
	 * action without creating new contexts for any part of the MVC.
	 *
	 * This method...
	 *
	 * - is ideal for quickly calling Controllers in the same plugin being rendered
	 * - will bleed template variables into the templates being rendered by the target Controller
	 * - will throw an Exception if one is encountered
	 * - will return whichever data type is returned by the controller
	 * - will not transform provided arguments according to annotations (but method dispatchRequest will)
	 *
	 * @param $controllerClassName
	 * @param $actionName
	 * @param array $arguments
	 * @param boolean $returnPreparedInstanceAndArguments If TRUE, returns array($controllerInstance, $actionMethodName, $sortedParameters) instead of calling the method
	 * @return mixed
	 * @throws Exception
	 * @api
	 */
	public function inlineControllerAction($controllerClassName, $actionName, array $arguments=array(), $returnPreparedInstanceAndArguments=FALSE) {
		$actionMethodName = $actionName . 'Action';
		$controllerClassReflection = new ReflectionClass($controllerClassName);
		$actionMethodReflection = $controllerClassReflection->getMethod($actionMethodName);
		$argumentsReflection = $actionMethodReflection->getParameters();
		$sortedParameters = array();
		foreach ($argumentsReflection as $argumentReflection) {
			$argumentName = $argumentReflection->getName();
			$defaultValue = $argumentReflection->getDefaultValue();
			if (isset($arguments[$argumentName])) {
				$sortedParameters[] = $arguments[$argumentName];
			} elseif ($argumentReflection->isDefaultValueAvailable()) {
				$sortedParameters[] = $defaultValue;
			} elseif ($argumentReflection->isOptional() === FALSE) {
				throw new Exception('Missing required argument ' . $argumentName . ' for ' . $controllerClassName . '::' . $actionMethodName, 1350161435);
			}
		}
		$controllerInstance = $this->objectManager->get($controllerClassName);
		if ($returnPreparedInstanceAndArguments) {
			return array($controllerInstance, $actionMethodName, $sortedParameters);
		} else {
			return call_user_func_array(array($controllerInstance, $actionName), $sortedParameters);
		}
	}

	/**
	 * Dispatch Controller Action
	 *
	 * Dispatches (executes) a Controller action on the desired
	 * Controller class. If the action does not exist an Exception
	 * is thrown.
	 *
	 * This method...
	 *
	 * - does not load special Request-related meta information
	 * - will create a new ControllerContext
	 * - will create a new RenderingContext
	 * - will catch Exceptions (which are not resolve-Exceptions) and return error string
	 * - works across request types (CLI, Web, etc)
	 * - will not transform provided arguments according to annotations (but method dispatchRequest will)
	 * - internally uses $this->inlineControllerAction after replicating the necessary environment
	 *
	 * @param string $controllerClassName
	 * @param string $actionName
	 * @param array $arguments
	 * @return mixed
	 * @throws Exception
	 * @api
	 */
	public function executeControllerAction($controllerClassName, $actionName, array $arguments=array()) {
		$returnPreparedInstanceArguments = TRUE;
		/** @var \TYPO3\CMS\Extbase\Mvc\Controller\AbstractController $controllerInstance */
		list ($controllerInstance, $actionMethodName, $sortedParameters) = $this->inlineControllerAction($controllerClassName, $actionName, $arguments, $returnPreparedInstanceArguments);
			// TODO: create and implement ad-hoc context for execution
		return call_user_func_array(array($controllerInstance, $actionMethodName), $sortedParameters);
	}

	/**
	 * Dispatch Request
	 *
	 * Dispatches (as a completely new Request) a Request that will
	 * execute a configured Plugin->Controller->action() which means
	 * that the Plugin, Controller and Action you use must be allowed
	 * by the plugin configuration of the target controller.
	 *
	 * This method...
	 *
	 * - is the most isolated method to use
	 * - creates completely new contexts for every part of the MVC
	 * - requires a Request type (default is WebRequest, see class properties)
	 * - only works in the same context as the intended target, i.e. CLI Controllers only possible with CLI request
	 * - transforms arguments as necessary into the types required by the action
	 *
	 * @param string|NULL $actionName
	 * @param string|NULL $controllerName
	 * @param string|NULL $extensionName
	 * @param string|NULL $pluginName
	 * @param array $arguments
	 * @param integer $pageUid
	 * @return \TYPO3\CMS\Extbase\Mvc\ResponseInterface
	 * @throws Exception
	 * @api
	 */
	public function dispatchRequest($actionName=NULL, $controllerName=NULL, $extensionName=NULL, $pluginName=NULL, array $arguments=array(), $pageUid=0) {
		$contentObjectBackup = $this->configurationManager->getContentObject();
		if ($this->request) {
			$configurationBackup = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, $this->request->getControllerExtensionName(), $this->request->getPluginName());
		}
		$temporaryContentObject = new \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer();
		/** @var \TYPO3\CMS\Extbase\Mvc\Web\Request $request */
		$request = $this->objectManager->get($this->requestType);
		$request->setControllerActionName($actionName);
		$request->setControllerName($controllerName);
		$request->setPluginName($pluginName);
		$request->setControllerExtensionName($extensionName);
		$request->setArguments($arguments);
		/** @var \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response */
		$response = $this->objectManager->get($this->responseType);
		$this->configurationManager->setContentObject($temporaryContentObject);
		$this->configurationManager->setConfiguration($this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, $extensionName, $pluginName));
		$this->dispatcher->dispatch($request, $response);
		$this->configurationManager->setContentObject($contentObjectBackup);
		if (isset($configurationBackup)) {
			$this->configurationManager->setConfiguration($configurationBackup);
		}
		unset($pageUid);
		return $response;
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
	 */
	public function getUriBuilder() {
		return $this->uriBuilder;
	}

}