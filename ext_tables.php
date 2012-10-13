<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Fluid Widgets: Base configuration');
t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/Bootstrap', 'Fluid Widgets: Bootstrap Resources');
t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/AjaxLoader', 'Fluid Widgets: AJAX (jQuery required)');

Tx_Extbase_Utility_Extension::registerPlugin($_EXTKEY, 'SubRequest', 'Fluid Widgets: SubRequest API', NULL);
