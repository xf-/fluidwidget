<?php

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Note: Re-authored, Claus Due <claus@namelesscoder.net>
 *
 * Note: Re-authoring implies completely detaching from Fluid's
 * core AbstractWidgetViewHelper class. This does not cause problems in
 * any internal logic but you should be aware that this detachment can
 * cause false negatives when checking if a ViewHelper object is of a
 * Widget-compatible type. The methods in THIS particular implementation
 * have been changed to accomodate but outside accessors can under some
 * circumstances gain access to the ViewHelper object, making it a potential
 * scenario that some developer somewhere could decide to check for
 * inheritance from Fluid's AbstractWidgetViewHelper (instead of using
 * an interface which is the recommended solution).
 *
 * Note: Changes the standard Fluid logic slighly but unnoticably and
 * compatibly if rebasing an existing Widget onto this class - with one
 * notable impact: you will receive "bad code smell" log messages if you
 * attempt to inject your controller. If you are rebasing an existing
 * Widget you should simply remove the inject method and leave everything
 * else in place (and remember to clear reflection caches).
 *
 * Note:
 *
 * @api
 */
abstract class Tx_Fluidwidget_Core_Widget_AbstractWidgetViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper implements \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\ChildNodeAccessInterface {

	/**
	 * The Controller associated to this widget.
	 * Set a proper class or Interface name here. If Interface names are used
	 * then make sure you have registered your custom implementation or overridden
	 * the standard implementation.
	 *
	 * @var Tx_Fluidwidget_Core_Widget_WidgetControllerInterface
	 * @api
	 */
	protected $controller;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
		$this->widgetContext = $this->objectManager->get('TYPO3\CMS\Fluid\Core\Widget\WidgetContext');
	}

	/**
	 * Initialize this ViewHelper instance
	 *
	 * @throws Exception
	 * @return void
	 */
	public function initialize() {
		$controllerClassReflection = new ReflectionClass($this);
		$methodReflection = $controllerClassReflection->getProperty('controller');
		$docComment = $methodReflection->getDocComment();
		$matches = array();
		preg_match('/@var[\s]{1,}([a-zA-Z_\\^\s]+)/', $docComment, $matches);
		$controllerClassName = trim($matches[1]);
		if (class_exists($controllerClassName) === FALSE) {
			throw new Exception('Unknown Controller class: ' . $controllerClassName, 1351355695);
		}
			// fallback enabled for Singleton Controllers; however, the initializeContorller method is
			// also enabled for use by classes which have not yet removed their controller inject methods.
		if (method_exists($this, 'injectController') && is_a($controllerClassName, 't3lib_Singleton')) {
			$controllerInstance = $this->objectManager->get($controllerClassName);
			$this->injectController($controllerInstance);
		} else {
			$controllerInstance = $this->objectManager->get($controllerClassName);
		}
		$this->initializeController($controllerInstance);
		$this->controller = $controllerInstance;
	}

	/**
	 * Initialize the Controller used by this Widget.
	 *
	 * Note that this is in all respects just an inject-method
	 * which is not executed manually; one that allows the
	 * Controller to be "injected" automatically based on
	 * the variable type of $this->controller (as detected by
	 * Reflection) while allowing the method to be overridden
	 * exactly like an initializeView() method is overridden.
	 *
	 * @param Tx_Fluidwidget_Core_Widget_WidgetControllerInterface $controller
	 */
	public function initializeController(Tx_Fluidwidget_Core_Widget_WidgetControllerInterface $controller) {
		$this->controller = $controller;
	}
	/**
	 * If set to TRUE, it is an AJAX widget.
	 *
	 * @var boolean
	 * @api
	 */
	protected $ajaxWidget = FALSE;

	/**
	 * @var \TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder
	 */
	private $ajaxWidgetContextHolder;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\ExtensionService
	 */
	protected $extensionService;

	/**
	 * @var \TYPO3\CMS\Fluid\Core\Widget\WidgetContext
	 */
	private $widgetContext;

	/**
	 * @param \TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder $ajaxWidgetContextHolder
	 * @return void
	 */
	public function injectAjaxWidgetContextHolder(\TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder $ajaxWidgetContextHolder) {
		$this->ajaxWidgetContextHolder = $ajaxWidgetContextHolder;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Service\ExtensionService $extensionService
	 * @return void
	 */
	public function injectExtensionService(\TYPO3\CMS\Extbase\Service\ExtensionService $extensionService) {
		$this->extensionService = $extensionService;
	}

	/**
	 * Initialize the arguments of the ViewHelper, and call the render() method of the ViewHelper.
	 *
	 * @return string the rendered ViewHelper.
	 */
	public function initializeArgumentsAndRender() {
		$this->validateArguments();
		$this->initialize();
		$this->initializeWidgetContext();

		return $this->callRenderMethod();
	}

	/**
	 * Initialize the Widget Context, before the Render method is called.
	 *
	 * @return void
	 */
	private function initializeWidgetContext() {
		$this->widgetContext->setWidgetConfiguration($this->getWidgetConfiguration());
		$this->initializeWidgetIdentifier();

		$controllerObjectName = ($this->controller instanceof Tx_Fluid_AOP_ProxyInterface) ? $this->controller->FLOW3_AOP_Proxy_getProxyTargetClassName() : get_class($this->controller);
		$this->widgetContext->setControllerObjectName($controllerObjectName);

		$extensionName = $this->controllerContext->getRequest()->getControllerExtensionName();
		$pluginName = $this->controllerContext->getRequest()->getPluginName();
		$this->widgetContext->setParentExtensionName($extensionName);
		$this->widgetContext->setParentPluginName($pluginName);
		$pluginNamespace = $this->extensionService->getPluginNamespace($extensionName, $pluginName);
		$this->widgetContext->setParentPluginNamespace($pluginNamespace);

		$this->widgetContext->setWidgetViewHelperClassName(get_class($this));
		if ($this->ajaxWidget === TRUE) {
			$this->ajaxWidgetContextHolder->store($this->widgetContext);
		}
	}

	/**
	 * Stores the syntax tree child nodes in the Widget Context, so they can be
	 * rendered with <f:widget.renderChildren> lateron.
	 *
	 * @param array $childNodes The SyntaxTree Child nodes of this ViewHelper.
	 * @return void
	 */
	public function setChildNodes(array $childNodes) {
		$rootNode = $this->objectManager->get('TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode');
		foreach ($childNodes as $childNode) {
			$rootNode->addChildNode($childNode);
		}
		$this->widgetContext->setViewHelperChildNodes($rootNode, $this->renderingContext);
	}

	/**
	 * Generate the configuration for this widget. Override to adjust.
	 *
	 * @return array
	 * @api
	 */
	protected function getWidgetConfiguration() {
		return $this->arguments;
	}

	/**
	 * Initiate a sub request to $this->controller. Make sure to fill $this->controller
	 * via Dependency Injection.
	 *
	 * @throws TYPO3\CMS\Fluid\Core\Widget\Exception\MissingControllerException
	 * @return \TYPO3\CMS\Extbase\Mvc\ResponseInterface the response of this request.
	 * @api
	 */
	protected function initiateSubRequest() {
		if (!($this->controller instanceof \TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController) && !($this->controller instanceof Tx_Fluidwidget_Core_Widget_WidgetControllerInterface)) {
			if (isset($this->controller)) {
				throw new \TYPO3\CMS\Fluid\Core\Widget\Exception\MissingControllerException('initiateSubRequest() can not be called if there is no valid controller extending \TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController or implementing Tx_Fluidwidget_Core_Widget_WidgetControllerInterface. Got "' . get_class($this->controller) . '" in class "' . get_class($this) . '".', 1289422564);
			}
			throw new \TYPO3\CMS\Fluid\Core\Widget\Exception\MissingControllerException('initiateSubRequest() can not be called if there is no controller inside $this->controller. Make sure to add a corresponding injectController method to your WidgetViewHelper class "' . get_class($this) . '".', 1284401632);
		}

		$subRequest = $this->objectManager->get('TYPO3\CMS\Fluid\Core\Widget\WidgetRequest');
		$subRequest->setWidgetContext($this->widgetContext);
		$this->passArgumentsToSubRequest($subRequest);

		$subResponse = $this->objectManager->get('TYPO3\CMS\Extbase\Mvc\Web\Response');
		$this->controller->processRequest($subRequest, $subResponse);
		return $subResponse;
	}

	/**
	 * Pass the arguments of the widget to the subrequest.
	 *
	 * @param \TYPO3\CMS\Fluid\Core\Widget\WidgetRequest $subRequest
	 * @return void
	 */
	private function passArgumentsToSubRequest(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest $subRequest) {
		$arguments = $this->controllerContext->getRequest()->getArguments();
		$widgetIdentifier = $this->widgetContext->getWidgetIdentifier();
		if (isset($arguments[$widgetIdentifier])) {
			if (isset($arguments[$widgetIdentifier]['action'])) {
				$subRequest->setControllerActionName($arguments[$widgetIdentifier]['action']);
				unset($arguments[$widgetIdentifier]['action']);
			}
			$subRequest->setArguments($arguments[$widgetIdentifier]);
		}
	}

	/**
	 * The widget identifier is unique on the current page, and is used
	 * in the URI as a namespace for the widget's arguments.
	 *
	 * @return string the widget identifier for this widget
	 * @return void
	 * @todo clean up, and make it somehow more routing compatible.
	 */
	private function initializeWidgetIdentifier() {
		if (!$this->viewHelperVariableContainer->exists('TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetViewHelper', 'nextWidgetNumber')) {
			$widgetCounter = 0;
		} else {
			$widgetCounter = $this->viewHelperVariableContainer->get('TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetViewHelper', 'nextWidgetNumber');
		}
		$widgetIdentifier = '@widget_' . $widgetCounter;
		$this->viewHelperVariableContainer->addOrUpdate('TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetViewHelper', 'nextWidgetNumber', $widgetCounter + 1);

		$this->widgetContext->setWidgetIdentifier($widgetIdentifier);
	}

}
