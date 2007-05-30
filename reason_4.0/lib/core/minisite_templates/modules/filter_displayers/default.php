<?php
/**
 *	Default Filter Display
 * @package Reason_Core
 */
 
$GLOBALS['_reason_filter_displayers'][basename(__FILE__)] = 'defaultFilterDisplay';
 
/**
 * The default filter markup generation class
 *
 * Takes the needed raw data from a reason module and builds html interfaces
 * for the search box and relationship filtering
 * 
 * @author Matt Ryan <mryan@acs.carleton.edu>
 * @date 2006-07-20
 */
class defaultFilterDisplay
{
	/**
	 * The entity that represents the type being filtered
	 * @var entity
	 * @access private
	 */
	var $type;
	/**
	 * An array that sets up which types and relationships to provide replationship filtering on
	 *
	 * In the format:
	 * array('word_to_use_in_html'=>array('type'=>'type_unique_name','relationship'=>'relationship_name',),);
	 *	@var array
	 * @access private
	 */
	var $filter_types = array();
	/**
	 * An array that holds information about the current relationship filters.
	 *
	 * In the format:
	 * array($filter_key=>array('type'=>'filter_type_key','id'=>entity_id,),);
	 *	@var array
	 * @access private
	 */
	var $filters = array();
	/**
	 * True if on textonly page; false if not
	 *	@var bool
	 * @access private
	 */
	var $textonly = false;
	/**
	 * The number of characters provided in the search field
	 *	@var integer
	 * @access private
	 */
	var $search_field_size = 20;
	/**
	 * The current search string
	 *	@var string
	 * @access private
	 */
	var $search_value;
	/**
	 * An array with the current power search strings.
	 *
	 * In the format:
	 * array('psearch_frag'=>'search value')
	 *	@var array
	 * @access private
	 */
	var $psearch_values = array();
	/**
	 * @var array
	 * @access private
	 */
	var $default_links = array();
	/**
	 * Contains all the entities available to be used for relationship filtering.
	 *
	 * A multidimensional array -- 1st key is the filter type; 2nd key is the entity id; values of the 2nd key are entity objects
	 *	@var array
	 * @access private
	 */
	var $filter_entities = array();
	
	// reference to the module that instanciates the filter displayer
	var $module_ref;
	
	/**
	 * Provide a type so the filtering can know what its working on
	 * @param entity $type
	 * @access public
	 * @return void
	 */
	function set_type($type)
	{
		$this->type = $type;
	}
	/**
	 * Provide the filter types so so we know what relationship filtering should do
	 * @param array $filter_types an array of Reason entities
	 * @access public
	 * @return void
	 */
	function set_filter_types($filter_types)
	{
		$this->filter_types = $filter_types;
	}
	/**
	 * Provide the current filters selected
	 * Format: array($filter_key=>array('type'=>'filter_type_key','id'=>entity_id,),);
	 * @param array $filters a 2d array of filter types and ids
	 * @access public
	 * @return void
	 */
	function set_filters($filters)
	{
		$this->filters = $filters;
	}
	/**
	 * Indicate whether current page is text only or not
	 * @param bool $textonly
	 * @access public
	 */
	function set_textonly($textonly)
	{
		$this->textonly = $textonly;
	}
	/**
	 * Indicate how many chars wide the search field should be
	 * @param int $search_field_size
	 * @access public
	 * @return void
	 */
	function set_search_field_size($search_field_size)
	{
		$this->search_field_size = $search_field_size;
	}
	/**
	 * Pass the string that was searched for into the filter display object
	 *
	 * NOTE: this string is raw. 
	 * Don't put it into the code without passing it through htmlspecialchars() 
	 * or urlencode().
	 *
	 * @param string $search_value
	 * @access public
	 * @return void
	 */
	function set_search_value($search_value)
	{
		$this->search_value = $search_value;
	}
	/**
	 * Pass the string that was power searched for into the filter display object
	 *
	 * NOTE: this string is raw. 
	 * Don't put it into the code without passing it through htmlspecialchars() 
	 * or urlencode().
	 *
	 * @param string $key_frag
	 * @param string $psearch_value
	 * @access public
	 * @return void
	 */
	function set_power_search_value($key_frag,$psearch_value)
	{
		$this->psearch_values[$key_frag] = $psearch_value;
	}
	/**
	 * Set the default links
	 *
	 * @todo figure out exactly how the default links work
	 * @param array $default_links
	 * @access public
	 */
	function set_default_links($default_links)
	{
		$this->default_links = $default_links;
	}
	/**
	 * Set the filter entities
	 *
	 * Provides all the entities available to be used for relationship filtering
	 * A multidimensional array -- 1st key is the filter type; 2nd key is the entity id; values of the 2nd key are entity objects
	 * @param array $filter_entities
	 * @access public
	 */
	function set_filter_entities($filter_entities)
	{
		$this->filter_entities = $filter_entities;
	}
	/**
	 * Get the markup for the freetext search interface
	 * @return string
	 * @access public
	 * @param void
	 */
	function get_search_interface()
	{
		return $this->_build_search_interface();
	}
	/**
	 * Get the markup for the relationship filtering interface
	 * @return string
	 * @access public
	 * @param void
	 */
	function get_filter_interface()
	{
		return $this->_build_filter_interface();
	}
	/**
	 * Assemble the markup for the freetext searching interface
	 *
	 * This function may be overloaded in extensions of this class to provide different markup and/or interface for the search feature
	 * @return string
	 * @access private
	 */
	function _build_search_interface()
	{
		if(!empty($this->search_value))
			$v = htmlspecialchars( $this->search_value, ENT_COMPAT, 'UTF-8');
		else
			$v = '';
		$ret = '';
		$ret .= '<form method="get" action="?">'."\n";
		foreach($this->filters as $key=>$vals)
		{
			$ret .= '<input type="hidden" name="filters['.$key.'][type]" value="'.$vals['type'].'">';
			$ret .= '<input type="hidden" name="filters['.$key.'][id]" value="'.$vals['id'].'">';
		}
		if (!empty($this->textonly))
			$ret .= '<input type="hidden" name="textonly" value="1">';
		$ret .= 'Search: <input name="search" value="'.$v.'" size="'.$this->search_field_size.'" />'."\n";
		$ret .= ' <input name="go" type="submit" value="Go" />'."\n";
		if(!empty($this->search_value))
		{
			$link = '?';
			if(!empty($this->default_links))
				$link .= implode('&amp;', $this->default_links);
			if (!empty($this->textonly))
				$link .= '&amp;textonly=1';
			$ret .= ' <a href="'.$link.'" title="Remove this search term">Remove</a>'."\n";
		}
		$ret .= '</form>'."\n";
		return $ret;
	}
	/**
	 * Assemble the markup for the relationship filtering interface
	 * @return string
	 * @access private
	 *
	 * This function may be overloaded in extensions of this class to provide different markup and/or interface for the relationship filtering feature
	 */
	function _build_filter_interface()
	{
		$ret = '';
		if(!empty($this->filter_types) && !empty($this->filter_entities))
		{
			
			$ret .= '<script language="JavaScript">
			<!--
				function MM_jumpMenu(targ,selObj,restore){ //v3.0
				  eval(targ+".location=\'"+selObj.options[selObj.selectedIndex].value+"\'");
				  if (restore) selObj.selectedIndex=0;
				}
			//-->
			</script>';
			$ret .= '<form method="get">'."\n";
			if(count($this->filter_types) != 1)
				$ret .= 'Browse by '.implode('/',array_keys($this->filter_types)).':'."\n";
			foreach($this->filters as $key=>$values)
			{
				$ret .= $this->_build_filter_set($key);
			}
			if(!empty($this->filters))
			{
				$filts = $this->filters;
				krsort($filts);
				reset($filts);
				$top_filter_key = key($filts);
			}
			else
				$top_filter_key = 0;
			$next_filter_key = $top_filter_key + 1;
			$ret .= $this->_build_filter_set($next_filter_key);
			$ret .= '</form>'."\n";
		}
		return $ret;
	}
	/**
	 * Assemble the markup for a particular filter selector
	 * @return string
	 * @access private
	 */
	function _build_filter_set($key)
	{
		$ret = '';
		$other_filter_links = $this->default_links;
		unset($other_filter_links[$key]);
		$combined_other_filter_links = implode('&amp;',$other_filter_links);
	
		$ret .= '<div>';
		$ret .= '<select name="filter_'.$key.'" onChange="MM_jumpMenu(\'parent\',this,0)">'."\n";
		if(empty($this->filters[$key]))
		{
			if(empty($this->filters))
			{
				if(count($this->filter_types) == 1)
					$ret .= '<option value="">Browse by '.current(array_keys($this->filter_types)).':</option>'."\n";
				else
					$ret .= '<option value="">Focus on...</option>'."\n";
			}
			else
				$ret .= '<option value="">Add focus...</option>'."\n";
			$ret .= '<option value=""></option>'."\n";
		}
		foreach($this->filter_types as $filter_name=>$filter_type)
		{
			if(!empty($this->filter_entities[$filter_name]))
			{
				if(count($this->filter_types) != 1)
					$ret .= '<option value="" class="type">'.prettify_string($filter_name).'</option>'."\n";
				foreach($this->filter_entities[$filter_name] as $entity)
				{
					$link = '?';
					if(!empty($other_filter_links))
						$link .= $combined_other_filter_links.'&amp;';
					if(!empty($this->search_value))
						$link .= 'search='.urlencode($this->search_value).'&amp;';
					$link .= 'filters['.$key.'][type]='.$filter_name.'&amp;filters['.$key.'][id]='.$entity->id();
					if (!empty($this->parent->textonly))
						$link .= '&amp;textonly=1';
					if(!empty($this->filters[$key]) && $this->filters[$key]['type'] == $filter_name && $this->filters[$key]['id'] == $entity->id())
						$add = ' selected="selected"';
					else
						$add = '';
					$ret .= '<option value="'.$link.'"'.$add.'> - '.$entity->get_value('name').'</option>'."\n";
				}
				$ret .= '<option value=""></option>'."\n";
			}
		}
		$ret .= '</select>'."\n";
		if(!empty($this->filters[$key]))
		{
			$link = '?';
			if(!empty($this->search_value))
				$link .= 'search='.urlencode($this->search_value).'&amp;';
			if(!empty($other_filter_links))
				$link .= $combined_other_filter_links;
			$ret .= ' <a href="'.$link.'" title="Remove this filter">Remove</a>'."\n";
		}
		$ret .= '</div>'."\n";
		return $ret;
	}
	
	function set_module_ref($ref)
	{
		$this->module_ref = &$ref;
	}
}
?>