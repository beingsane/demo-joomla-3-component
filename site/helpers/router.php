<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				Vast Development Method 
/-------------------------------------------------------------------------------------------------------/

	@version		1.0.3 - 24th August, 2015
	@package		Demo
	@subpackage		router.php
	@author			Llewellyn van der Merwe <https://www.vdm.io/>	
	@copyright		Copyright (C) 2015. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
  ____  _____  _____  __  __  __      __       ___  _____  __  __  ____  _____  _  _  ____  _  _  ____ 
 (_  _)(  _  )(  _  )(  \/  )(  )    /__\     / __)(  _  )(  \/  )(  _ \(  _  )( \( )( ___)( \( )(_  _)
.-_)(   )(_)(  )(_)(  )    (  )(__  /(__)\   ( (__  )(_)(  )    (  )___/ )(_)(  )  (  )__)  )  (   )(  
\____) (_____)(_____)(_/\/\_)(____)(__)(__)   \___)(_____)(_/\/\_)(__)  (_____)(_)\_)(____)(_)\_) (__) 

/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// Component Helper
jimport('joomla.application.component.helper');
jimport('joomla.application.categories');

/**
 * Demo Route Helper
 **/
abstract class DemoHelperRoute
{
	protected static $lookup;

	/**
	 * Get the URL route for demo category from a category ID and language
	 *
	 * @param   mixed    $catid     The id of the items's category either an integer id or a instance of JCategoryNode
	 * @param   mixed    $language  The id of the language being used.
	 *
	 * @return  string  The link to the contact
	 *
	 * @since   1.5
	 */
	public static function getCategoryRoute($catid, $language = 0)
	{
    	if ($catid instanceof JCategoryNode)
		{
			$id = $catid->id;			
			$category = $catid;			 
		}
		else
		{			
			throw new Exception('First parameter must be JCategoryNode');			
		}
	
		$views = array();
		$view = $views[$category->extension];
       
		if ($id < 1 || !($category instanceof JCategoryNode))
		{
			$link = '';
		}
		else
		{
			//Create the link
			$link = 'index.php?option=com_demo&view='.$view.'&category='.$category->slug;
			
			$needles = array(
					$view => array($id),
					'category' => array($id)
			);
	
			if ($language && $language != "*" && JLanguageMultilang::isEnabled())
			{
				$db		= JFactory::getDbo();
				$query	= $db->getQuery(true)
				->select('a.sef AS sef')
				->select('a.lang_code AS lang_code')
				->from('#__languages AS a');
	
				$db->setQuery($query);
				$langs = $db->loadObjectList();
				foreach ($langs as $lang)
				{
					if ($language == $lang->lang_code)
					{
						$link .= '&lang='.$lang->sef;
						$needles['language'] = $language;
					}
				}
			}
	
			if ($item = self::_findItem($needles,'category'))
			{

				$link .= '&Itemid='.$item;				
			}
			else
			{
				if ($category)
				{
					$catids = array_reverse($category->getPath());
					$needles = array(
							'category' => $catids
					);
					if ($item = self::_findItem($needles,'category'))
					{
						$link .= '&Itemid='.$item;
					}
					elseif ($item = self::_findItem(null, 'category'))
					{
						$link .= '&Itemid='.$item;
					}
				}
			}
		}
		
		return $link;
	}	
	
	protected static function _findItem($needles = null, $identifier = 'id')
	{
		$app		= JFactory::getApplication();
		$menus		= $app->getMenu('site');
		
		$language	= isset($needles['language']) ? $needles['language'] : '*';

		// Prepare the reverse lookup array.
		if (!isset(self::$lookup[$language]))
		{
			self::$lookup[$language] = array();

			$component	= JComponentHelper::getComponent('com_demo');

			$attributes = array('component_id');
			$values = array($component->id);

			if ($language != '*')
			{
				$attributes[] = 'language';
				$values[] = array($needles['language'], '*');
			}

			$items = $menus->getItems($attributes, $values);
			
			foreach ($items as $item)
			{
				if (isset($item->query) && isset($item->query['view']))
				{
					$view = $item->query['view'];
					if (!isset(self::$lookup[$language][$view]))
					{
						self::$lookup[$language][$view] = array();
					}
					if (isset($item->query[$identifier]))
					{

						// here it will become a bit tricky
						// language != * can override existing entries
						// language == * cannot override existing entries
						if (!isset(self::$lookup[$language][$view][$item->query[$identifier]]) || $item->language != '*')
						{
							if($item->query[$identifier] != 'all') {
								self::$lookup[$language][$view][$item->query[$identifier]] = $item->id;
							}
						}
					}
				}
			}
		}
		
		
		if ($needles)
		{
			foreach ($needles as $view => $ids)
			{
				if (isset(self::$lookup[$language][$view]))
				{
					foreach ($ids as $id)
					{
						if (isset(self::$lookup[$language][$view][(int) $id]))
						{
							if ($id != 'all') {
								return self::$lookup[$language][$view][(int) $id];
							}
						}
					}
				}
			}
		}

		$active = $menus->getActive();
		if ($active && ($active->language == '*' || !JLanguageMultilang::isEnabled()))
		{
			return $active->id;
		}

		// if not found, return language specific home link
		$default = $menus->getDefault($language);
		return !empty($default->id) ? $default->id : null;
	}
}
