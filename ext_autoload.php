<?php
$classPrefix = 'Tx_Fluidwidget_';
$classPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('fluidwidget', 'Classes/');
if ($GLOBALS['autoload_cache'][$classPath]) {
	return $GLOBALS['autoload_cache'][$classPath];
}
$files = \TYPO3\CMS\Core\Utility\GeneralUtility::getAllFilesAndFoldersInPath(array(), $classPath);
$autoloadRegistry = array();
foreach ($files as $filename) {
	$relativeName = substr($filename, strlen($classPath));
	$relativeName = substr($relativeName, 0, -4);
	$className = $classPrefix . str_replace('/', '_', $relativeName);
	$key = strtolower($className);
	$autoloadRegistry[$key] = $filename;
}
$GLOBALS['autoload_cache'][$classPath] = $autoloadRegistry;
return $autoloadRegistry;
?>