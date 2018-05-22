<?php
/**
 * @copyright      Copyright (C) 2016-2018 Nikita Bystrov (Arttse). All rights reserved.
 * @license        License GNU General Public License version 3
 * @author         Nikita Bystrov (Arttse)
 */

defined ( '_JEXEC' ) or die;

class modArticlesPlusHelper {

  /**
   * All params of module
   *
   * @var object
   */
  public $params;

  /**
   * Data of module
   *
   * @var object
   */
  public $module;

  /**
   * Categories
   *
   * @var array
   */
  public $cat_ids;

  /**
   * Categories exception
   *
   * @var array
   */
  public $cat_ids_exception;

  /**
   * Tags
   *
   * @var array
   */
  public $tag_ids;

  /**
   * Tags exception
   *
   * @var array
   */
  public $tag_ids_exception;

  /**
   * Status of articles
   *
   * @var array
   */
  public $status;

  /**
   * Featured
   *
   * @var int
   */
  public $featured;

  /**
   * Offset of items
   *
   * @var int
   */
  public $offset;

  /**
   * Limit of items
   *
   * @var int
   */
  public $limit;

  /**
   * Order by
   *
   * @var string
   */
  public $order;

  /**
   * Direction. ASC|DESC
   *
   * @var string
   */
  public $direction;

  /**
   * Select fields.
   * Required fields 'a.id', 'a.catid', 'a.state', 'a.featured'
   *
   * @var array
   */
  public $select_fields = [
    'a.id',
    'a.title',
    'a.alias',
    'a.introtext',
    'a.fulltext',
    'a.state',
    'a.catid',
    'a.created',
    'a.created_by',
    'a.created_by_alias',
    'a.modified',
    'a.modified_by',
    'a.publish_up',
    'a.publish_down',
    'a.images',
    'a.urls',
    'a.attribs',
    'a.metadata',
    'a.metakey',
    'a.metadesc',
    'a.access',
    'a.hits',
    'a.featured',
    'a.language',
  ];


  /**
   * Initialization.
   *
   * @param $module - data module
   * @param $params - module params
   */
  function __construct ( $module, $params )
  {
    $this->module = $module;
    $this->params = $params;

    // Filters
    $this->cat_ids = (array)$params->get ( 'cat_ids', [] );
    $this->tag_ids = (array)$params->get ( 'tag_ids', [] );
    $this->status = (array)$params->get ( 'status', [] );
    $this->featured = (int)$params->get ( 'featured', 0 );

    // Exceptions
    $this->cat_ids_exception = (array)$params->get ( 'cat_ids_exception', [] );
    $this->tag_ids_exception = (array)$params->get ( 'tag_ids_exception', [] );

    // Other
    $this->offset = (int)$params->get ( 'offset', 0 );
    $this->limit = (int)$params->get ( 'limit', 4 );
    $this->order = (string)$params->get ( 'order', 'a.publish_up' );
    $this->direction = (string)$params->get ( 'direction', 'DESC' );
  }


  /**
   * Get list items
   *
   * @return array - list of items
   */
  public function getItems ()
  {
    /** Check and remove intersecting values in Categories */
    if ( count ( $this->cat_ids ) AND count ( $this->cat_ids_exception ) )
    {
      $this->cat_ids = $this->intersectExclude ( $this->cat_ids, $this->cat_ids_exception );
    }

    /** Check and remove intersecting values in Tags */
    if ( count ( $this->tag_ids ) AND count ( $this->tag_ids_exception ) )
    {
      $this->tag_ids = $this->intersectExclude ( $this->tag_ids, $this->tag_ids_exception );
    }

    /** Work with DataBase */
    $db = JFactory::getDbo ();
    $query = $db->getQuery ( true );

    /** Standart query */
    $query
      ->select ( $db->quoteName ( $this->select_fields ) )
      ->from ( $db->quoteName ( '#__content', 'a' ) )
      ->order ( ( $this->order == 'random' ? 'RAND()' : $db->quoteName ( $this->order ) . ' ' . $this->direction ) )
      ->group ( $db->quoteName ( 'a.id' ) );

    /** Additional query for tags */
    $query
      ->select ( 'GROUP_CONCAT(DISTINCT ' . $db->quoteName ( 'b.tag_id' ) . ' SEPARATOR \',\') AS ' . $db->quoteName ( 'tags' ) )
      ->join (
        'LEFT',
        $db->quoteName ( '#__contentitem_tag_map', 'b' ) .
        ' ON (' . $db->quoteName ( 'a.id' ) . ' = ' . $db->quoteName ( 'b.content_item_id' ) . ')'
      );

    /** Set a limit with offset */
    if ( $this->limit <= 0 AND $this->offset )
    {
      $query->setLimit ( 9223372036854775807, $this->offset );
    }
    elseif ( $this->limit > 0 )
    {
      $query->setLimit ( $this->limit, $this->offset );
    }

    /** Filter by Categories */
    if ( count ( $this->cat_ids ) )
    {
      $query->where ( '(' . $this->_mySqlClause ( $this->cat_ids, $db->quoteName ( 'a.catid' ) ) . ')' );
    }

    /** Filter exception by Categories */
    if ( count ( $this->cat_ids_exception ) )
    {
      $query->where ( '(' . $this->_mySqlClause ( $this->cat_ids_exception, $db->quoteName ( 'a.catid' ), '!=', 'AND' ) . ')' );
    }

    /** Filter by Tags */
    if ( count ( $this->tag_ids ) )
    {
      $query->where ( '(' . $this->_mySqlClause ( $this->tag_ids, $db->quoteName ( 'b.tag_id' ) ) . ')' );
    }

    /** Filter exception by Tags */
    if ( count ( $this->tag_ids_exception ) )
    {
      $query->where ( '(' . $this->_mySqlClause ( $this->tag_ids_exception, $db->quoteName ( 'b.tag_id' ), '!=', 'AND' ) . ')' );
    }

    /** Filter by Status (state) */
    if ( count ( $this->status ) > 0 AND count ( $this->status ) < 4 )
    {
      $query->where ( '(' . $this->_mySqlClause ( $this->status, $db->quoteName ( 'a.state' ) ) . ')' );
    }

    /** Filter by featured */
    if ( (bool)$this->featured )
    {
      if ( $this->featured === 1 )
      {
        $query->where ( '(' . $db->quoteName ( 'a.featured' ) . ' != \'1\')' );
      }
      elseif ( $this->featured === 2 )
      {
        $query->where ( '(' . $db->quoteName ( 'a.featured' ) . ' = \'1\')' );
      }
    }

    $db->setQuery ( $query );

    $items = $db->loadObjectList ();

    /** Fields introduced in Joomla since 3.7.0 */
    if ( class_exists ( 'FieldsHelper' ) )
    {
      foreach ( $items as &$item )
      {
        $item->jcfields = FieldsHelper::getFields ( 'com_content.article', $item, true );
      }
      unset( $item );
    }

    return $items;
  }


  /**
   * Checks for intersecting values and removes them from the main array
   *
   * @param array $main       - the array with master values to check.
   * @param array $comparable - an array to compare values against.
   *
   * @return array - main array with excluded intersecting values
   */
  public function intersectExclude ( array $main, array $comparable )
  {
    foreach ( array_intersect ( $main, $comparable ) as $k => $v )
    {
      unset( $main[$k] );
    }

    return array_values ( $main );
  }


  /**
   * MySQL Clause from array of elements
   *
   * @param array  $elements  - elements for compliance with field $where
   * @param string $where     - field, which will make the appropriate $elements
   * @param string $operator  - operator, which can be used with clause
   * @param string $condition - `AND` or `OR`
   *
   * @return string
   */
  private function _mySqlClause ( array $elements, $where, $operator = '=', $condition = 'OR' )
  {
    if ( count ( $elements ) == 0 OR empty( $where ) )
    {
      return '';
    }

    $clause = '';

    foreach ( $elements as $i => $element )
    {
      $element = '\'' . str_replace ( '\'', '\\\'', $element ) . '\'';

      if ( $i == 0 )
      {
        $clause = $where . $operator . $element;
      }
      else
      {
        $clause .= ' ' . trim ( $condition ) . ' ' . $where . $operator . $element;
      }
    }

    return $clause;
  }

}
