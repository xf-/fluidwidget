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
	 * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view
	 */
	public function initializeView(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view) {
		$typoScriptSettings = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
		$paths = $typoScriptSettings['plugin.']['tx_fluidwidget.']['view.'];
		foreach ($paths as $key => $subPath) {
			$paths[$key] = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($subPath);
		}
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
		$widgetViewHelperClassName = $this->request->getWidgetContext()->getWidgetViewHelperClassName();
		$this->view->assign('return', in_array('Uri', explode('_', $widgetViewHelperClassName)));
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
			$absoluteTargetPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($target);
			if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
				header('Pragma: public');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Content-type: application-download');
				header('Content-length: ' . filesize($absoluteTargetPath));
				header('Content-Transfer-Encoding: binary');
			} else {
				header('Content-type: ' . mime_content_type($absoluteTargetPath));
			}
			$handle = fopen($absoluteTargetPath, 'rb');
			$chunkSize = 1048576;
			header('Content-disposition: attachment; filename="' . basename($absoluteTargetPath) . '"');
			while (FALSE === feof($handle)) {
				echo fread($handle, $chunkSize);
				ob_flush();
				flush();
			}
			fclose($handle);
		}
		exit();
	}

}
