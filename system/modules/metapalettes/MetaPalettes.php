<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * htaccess Generator
 * Copyright (C) 2011 Tristan Lins
 *
 * Extension for:
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  InfinitySoft 2011
 * @author     Tristan Lins <tristan.lins@infinitysoft.de>
 * @package    MetaPalettes
 * @license    LGPL
 * @filesource
 */


/**
 * Class MetaPalettes
 *
 * Generates the palettes from the meta information.
 *
 * @copyright  InfinitySoft 2011
 * @author     Tristan Lins <tristan.lins@infinitysoft.de>
 * @package    MetaPalettes
 */
class MetaPalettes
{
	/**
	 * @var MetaPalettes
	 */
	protected static $objInstance = null;

	/**
	 * Get the static instance.
	 *
	 * @static
	 * @return MetaPalettes
	 */
	public static function getInstance()
	{
		if (self::$objInstance == null) {
			self::$objInstance = new MetaPalettes();
		}
		return self::$objInstance;
	}

	/**
	 * Protected constructor for singleton instance.
	 */
	protected function __construct() {}


	/**
	 * @param $strTable
	 * @return void
	 */
	public function generatePalettes($strTable)
	{
		// check if any meta palette information exists
		if (isset($GLOBALS['TL_DCA'][$strTable]['metapalettes']) && is_array($GLOBALS['TL_DCA'][$strTable]['metapalettes']))
		{
			// walk over the meta palette
			foreach ($GLOBALS['TL_DCA'][$strTable]['metapalettes'] as $strPalette=>$arrMeta)
			{
				// only generate if not palette exists
				if (!isset($GLOBALS['TL_DCA'][$strTable]['palettes'][$strPalette]) && is_array($arrMeta))
				{
					// set the palette
					$GLOBALS['TL_DCA'][$strTable]['palettes'][$strPalette] = self::generatePalette($arrMeta);
				}
			}
		}

		// check if any meta palette information exists
		if (isset($GLOBALS['TL_DCA'][$strTable]['metasubpalettes']) && is_array($GLOBALS['TL_DCA'][$strTable]['metasubpalettes']))
		{
			// walk over the meta palette
			foreach ($GLOBALS['TL_DCA'][$strTable]['metasubpalettes'] as $strPalette=>$arrFields)
			{
				// only generate if not palette exists
				if (!isset($GLOBALS['TL_DCA'][$strTable]['subpalettes'][$strPalette]) && is_array($arrFields))
				{
					// only generate if there are any fields
					if (is_array($arrFields) && count($arrFields) > 0)
					{
						// generate subpalettes selectors
						$GLOBALS['TL_DCA'][$strTable]['palettes']['__selector__'][] = $strPalette;

						// set the palette
						$GLOBALS['TL_DCA'][$strTable]['subpalettes'][$strPalette] = implode(',', $arrFields);
					}
				}
			}
		}
	}

	/**
	 * Filter meta fields, starting with ":" from an array.
	 *
	 * @param $strField string
	 * @return bool
	 */
	public function filterFields($strField) {
		return $strField[0] != ':';
	}

	protected static function generatePalette($arrMeta)
	{
		$arrBuffer = array();

		// walk over the chapters
		foreach ($arrMeta as $strLegend=>$arrFields)
		{
			if (is_array($arrFields))
			{
				// generate palettes legend
				$strBuffer = sprintf('{%s_legend%s},', $strLegend, in_array(':hide', $arrFields) ? ':hide' : '');

				// filter meta description (fields starting with ":")
				$arrFields = array_filter($arrFields, array(self::getInstance(), 'filterFields'));

				// only generate chapter if there are any fields
				if (count($arrFields) > 0)
				{
					$strBuffer .= implode(',', $arrFields);
					$arrBuffer[] = $strBuffer;
				}
			}
		}

		return implode(';', $arrBuffer);
	}

}
