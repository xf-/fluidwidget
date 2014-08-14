<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2014 Claus Due <claus@namelesscoder.net>
*
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 *
 * @author Claus Due
 * @package Fluidwidget
 * @subpackage Utility
 */
class Tx_Fluidwidget_Utility_Path implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Translates an array of paths or single path into absolute paths/path
	 *
	 * @param mixed $path
	 * @return mixed
	 */
	public static function translatePath($path) {
		if (is_array($path) == FALSE) {
			return \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($path);
		} else {
			foreach ($path as $key=>$subPath) {
				$path[$key] = self::translatePath($subPath);
			}
		}
		return $path;
	}

}
