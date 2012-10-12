<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Fluid Widgets: Base configuration');
t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/Bootstrap', 'Fluid Widgets: Bootstrap Resources');
