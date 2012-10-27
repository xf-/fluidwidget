<?php
class Tx_Fluidwidget_Controller_UriController extends Tx_Fluidwidget_Core_Widget_AbstractWidgetController implements Tx_Fluidwidget_Core_Widget_WidgetControllerInterface {

	/**
	 * @var string
	 */
	protected $content;

	/**
	 * @param string $content
	 */
	public function setContent($content) {
		$this->content = $content;
	}

	/**
	 * @return string
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * @param Tx_Extbase_MVC_View_ViewInterface $view
	 */
	public function initializeView(Tx_Extbase_MVC_View_ViewInterface $view) {
		$typoScriptSettings = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
		$paths = $typoScriptSettings['plugin.']['tx_fluidwidget.']['view.'];
		$paths = Tx_Fluidwidget_Utility_Path::translatePath($paths);
		$view->setTemplateRootPath($paths['templateRootPath']);
		$view->setPartialRootPath($paths['partialRootPath']);
		$view->setLayoutRootPath($paths['layoutRootPath']);
		$view->setControllerContext($this->controllerContext);
		$this->view = $view;
	}

	/**
	 * Renders the template according to parameters
	 */
	public function indexAction() {
		foreach ($this->widgetConfiguration as $name=>$value) {
			$this->view->assign($name, $value);
		}
		$this->view->assign('return', in_array('Uri', explode('_', get_class($this))));
		$this->view->assign('content', $this->content);
		return trim($this->view->render());
	}

	/**
	 * @return string
	 */
	public function downloadAction() {
		$target = $this->widgetConfiguration['target'];
		if ($this->widgetConfiguration['mode'] != 'passthrough') {
			header('Location: ' . $target);
		} else {
			$absouteTargetPath = t3lib_div::getFileAbsFileName($target);
			header('Content-type: ' . mime_content_type($absouteTargetPath));
			$fp = fopen($absouteTargetPath, 'r');
			fpassthru($fp);
		}
		exit();
	}

}
