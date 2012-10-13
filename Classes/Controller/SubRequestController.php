<?php
class Tx_Fluidwidget_Controller_SubRequestController extends Tx_Fluid_Core_Widget_AbstractWidgetController implements t3lib_Singleton {

	/**
	 * @var Tx_Extbase_MVC_Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @var string
	 */
	protected $requestType = 'Tx_Extbase_MVC_Web_Request';

	/**
	 * @var string
	 */
	protected $responseType = 'Tx_Extbase_MVC_Web_Response';

	/**
	 * @param Tx_Extbase_MVC_Dispatcher $dispatcher
	 */
	public function injectDispatcher(Tx_Extbase_MVC_Dispatcher $dispatcher) {
		$this->dispatcher = $dispatcher;
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
		$controllerInstance = $this->objectManager->create($controllerClassName);
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
		/** @var Tx_Extbase_MVC_Controller_AbstractController $controllerInstance */
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
	 * @param string|NULL $pluginName
	 * @param string|NULL $extensionName
	 * @param array $arguments
	 * @param integer $pageUid
	 * @return Tx_Extbase_MVC_ResponseInterface
	 * @throws Exception
	 * @api
	 */
	public function dispatchRequest($actionName=NULL, $controllerName=NULL, $pluginName=NULL, $extensionName=NULL, array $arguments=array(), $pageUid=0) {
		$contentObjectBackup = $this->configurationManager->getContentObject();
		if ($this->request) {
			$configurationBackup = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, $this->request->getControllerExtensionName(), $this->request->getPluginName());
		}
		$temporaryContentObject = new tslib_cObj();
		/** @var Tx_Extbase_MVC_Web_Request $request */
		$request = $this->objectManager->create($this->requestType);
		$request->setControllerActionName($actionName);
		$request->setControllerName($controllerName);
		$request->setPluginName($pluginName);
		$request->setControllerExtensionName($extensionName);
		$request->setArguments($arguments);
		/** @var Tx_Extbase_MVC_ResponseInterface $response */
		$response = $this->objectManager->create($this->responseType);
		$this->configurationManager->setContentObject($temporaryContentObject);
		$this->configurationManager->setConfiguration($this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, $extensionName, $pluginName));
		$this->dispatcher->dispatch($request, $response);
		$this->configurationManager->setContentObject($contentObjectBackup);
		if (isset($configurationBackup)) {
			$this->configurationManager->setConfiguration($configurationBackup);
		}
		return $response;
	}

	/**
	 * @return Tx_Extbase_MVC_Web_Routing_UriBuilder
	 */
	public function getUriBuilder() {
		return $this->uriBuilder;
	}

}