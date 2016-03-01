<?php
/**
 * @copyright      Copyright (C) 2016 Nikita «Arttse» Bystrov. All rights reserved.
 * @license        License GNU General Public License version 3
 * @author         Nikita «Arttse» Bystrov
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
     * Tags
     *
     * @var array
     */
    public $tag_ids;

    /**
     * Limit of items
     *
     * @var int
     */
    public $limit;

    /**
     * Select fields
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

        $this->cat_ids = (array)$params->get ( 'cat_ids', [ ] );
        $this->tag_ids = (array)$params->get ( 'tag_ids', [ ] );
        $this->limit = (int)$params->get ( 'limit', 4 );
    }


    /**
     * Get list items
     *
     * @return array - list of items
     */
    function getItems ()
    {
        $db = JFactory::getDbo ();
        $query = $db->getQuery ( true );

        /** Standart query */
        $query
            ->select ( $db->quoteName ( $this->select_fields ) )
            ->from ( $db->quoteName ( '#__content', 'a' ) )
            ->order ( $db->quoteName ( 'a.created' ) . ' DESC' )
            ->group ( $db->quoteName ( 'a.id' ) );

        /** Additional query for tags */
        $query
            ->select ( 'GROUP_CONCAT(DISTINCT ' . $db->quoteName ( 'b.tag_id' ) . ' SEPARATOR \',\') AS ' . $db->quoteName ( 'tags' ) )
            ->join (
                'LEFT',
                $db->quoteName ( '#__contentitem_tag_map', 'b' ) .
                ' ON (' . $db->quoteName ( 'a.id' ) . ' = ' . $db->quoteName ( 'b.content_item_id' ) . ')'
            );

        /** Set a limit */
        if ( $this->limit )
        {
            $query->setLimit ( $this->limit );
        }

        /** Filter by Categories */
        if ( count ( $this->cat_ids ) )
        {
            $query->where ( '(' . $this->_mySqlClause ( $this->cat_ids, $db->quoteName ( 'a.catid' ) ) . ')' );
        }

        /** Filter by Tags */
        if ( count ( $this->tag_ids ) )
        {
            $query->where ( '(' . $this->_mySqlClause ( $this->tag_ids, $db->quoteName ( 'b.tag_id' ) ) . ')' );
        }

        $db->setQuery ( $query );

        return $db->loadObjectList ();

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
            if ( $i == 0 )
            {
                $clause = $where . $operator . '\'' . str_replace( '\'', '\\\'', $element ) . '\'';
            }
            else
            {
                $clause .= ' ' . trim ( $condition ) . ' ' . $where . $operator . '\'' . str_replace( '\'', '\\\'', $element ) . '\'';
            }
        }

        return $clause;
    }

}