<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * TYPOlight webCMS
 * Copyright (C) 2005-2009 Leo Feyer
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at http://www.gnu.org/licenses/.
 *
 * PHP version 5
 * @copyright  Leo Feyer 2005-2009
 * @author     Leo Feyer <leo@typolight.org>
 * @package    News
 * @license    LGPL
 * @filesource
 */


/**
 * Class ModuleNewsMenu
 *
 * Front end module "news archive".
 * @copyright  Leo Feyer 2005-2009
 * @author     Leo Feyer <leo@typolight.org>
 * @package    Controller
 */
class ModuleNewsMenu extends ModuleNews
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_newsmenu';


	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### NEWS ARCHIVE MENU ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'typolight/main.php?do=modules&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		$this->news_archives = $this->sortOutProtected(deserialize($this->news_archives, true));

		if (!is_array($this->news_archives) || count($this->news_archives) < 1)
		{
			return '';
		}

		return parent::generate();
	}


	/**
	 * Generate module
	 */
	protected function compile()
	{
		if ($this->news_format == 'news_year')
		{
			$this->compileYearlyMenu();
		}
		else
		{
			$this->compileMonthlyMenu();
		}
	}


	/**
	 * Generate the yearly menu
	 */
	protected function compileYearlyMenu()
	{
		$time = time();
		$arrData = array();

		$this->Template = new FrontendTemplate('mod_newsmenu_year');

		foreach ($this->news_archives as $id)
		{
			// Get all active months
			$objArchives = $this->Database->prepare("SELECT date FROM tl_news WHERE pid=?" . (!BE_USER_LOGGED_IN ? " AND (start='' OR start<?) AND (stop='' OR stop>?) AND published=1" : "") . " ORDER BY date DESC")
										  ->execute($id, $time, $time);

			while ($objArchives->next())
			{
				++$arrData[date('Y', $objArchives->date)]['count'];
			}
		}

		// Sort data
		krsort($arrData);
		$arrItems = array();

		// Get current "jumpTo" page
		$objPage = $this->Database->prepare("SELECT id, alias FROM tl_page WHERE id=?")
								  ->limit(1)
								  ->execute($this->jumpTo);

		$count = 0;
		$limit = count($arrData);

		// Prepare navigation
		foreach ($arrData as $intYear=>$arrDetails)
		{
			$intDate = $intYear . '01';
			$quantity = sprintf((($arrDetails['count'] < 2) ? $GLOBALS['TL_LANG']['MSC']['entry'] : $GLOBALS['TL_LANG']['MSC']['entries']), $arrDetails['count']);

			$arrItems[$intYear]['date'] = $intDate;
			$arrItems[$intYear]['link'] = $intYear;
			$arrItems[$intYear]['href'] = $this->generateFrontendUrl($objPage->row()) . ($GLOBALS['TL_CONFIG']['disableAlias'] ? '&amp;' : '?') . 'month=' . $intDate;
			$arrItems[$intYear]['title'] = specialchars($intYear . ' (' . $quantity . ')');
			$arrItems[$intYear]['class'] = trim(((++$count == 1) ? 'first ' : '') . (($count == $limit) ? 'last' : ''));
			$arrItems[$intYear]['isActive'] = ($this->Input->get('month') == $intDate);
			$arrItems[$intYear]['quantity'] = $quantity;
		}

		$this->Template->items = $arrItems;
		$this->Template->showQuantity = strlen($this->news_showQuantity) ? true : false;
	}


	/**
	 * Generate the monthly menu
	 */
	protected function compileMonthlyMenu()
	{
		$time = time();
		$arrData = array();

		foreach ($this->news_archives as $id)
		{
			// Get all active months
			$objArchives = $this->Database->prepare("SELECT date FROM tl_news WHERE pid=?" . (!BE_USER_LOGGED_IN ? " AND (start='' OR start<?) AND (stop='' OR stop>?) AND published=1" : "") . " ORDER BY date DESC")
										  ->execute($id, $time, $time);

			while ($objArchives->next())
			{
				++$arrData[date('Y', $objArchives->date)][date('m', $objArchives->date)]['count'];
			}
		}

		// Sort data
		foreach (array_keys($arrData) as $key)
		{
			krsort($arrData[$key]);
		}

		krsort($arrData);
		$arrItems = array();

		// Get current "jumpTo" page
		$objPage = $this->Database->prepare("SELECT id, alias FROM tl_page WHERE id=?")
								  ->limit(1)
								  ->execute($this->jumpTo);

		// Prepare navigation
		foreach ($arrData as $intYear=>$arrMonth)
		{
			$count = 0;
			$limit = count($arrMonth);

			foreach ($arrMonth as $intMonth=>$arrDetails)
			{
				$intDate = $intYear . $intMonth;
				$intMonth = (intval($intMonth) - 1);

				$quantity = sprintf((($arrDetails['count'] < 2) ? $GLOBALS['TL_LANG']['MSC']['entry'] : $GLOBALS['TL_LANG']['MSC']['entries']), $arrDetails['count']);

				$arrItems[$intYear][$intMonth]['date'] = $intDate;
				$arrItems[$intYear][$intMonth]['link'] = $GLOBALS['TL_LANG']['MONTHS'][$intMonth] . ' ' . $intYear;
				$arrItems[$intYear][$intMonth]['href'] = $this->generateFrontendUrl($objPage->row()) . ($GLOBALS['TL_CONFIG']['disableAlias'] ? '&amp;' : '?') . 'month=' . $intDate;
				$arrItems[$intYear][$intMonth]['title'] = specialchars($GLOBALS['TL_LANG']['MONTHS'][$intMonth].' '.$intYear . ' (' . $quantity . ')');
				$arrItems[$intYear][$intMonth]['class'] = trim(((++$count == 1) ? 'first ' : '') . (($count == $limit) ? 'last' : ''));
				$arrItems[$intYear][$intMonth]['isActive'] = ($this->Input->get('month') == $intDate);
				$arrItems[$intYear][$intMonth]['quantity'] = $quantity;
			}
		}

		$this->Template->items = $arrItems;
		$this->Template->showQuantity = strlen($this->news_showQuantity) ? true : false;
	}
}

?>