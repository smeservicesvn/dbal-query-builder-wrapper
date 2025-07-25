<?php
/**
 * Created by PhpStorm.
 * User: canngo
 * Date: 8/18/18
 * Time: 2:45 PM
 */

namespace QueryBuilderBundle;


class SubQueryCondition extends Condition {

    private static $count = 0;

    protected $condition_uid = ":subquery_";

    protected $comparison_type;
    /**
     * @var QueryBuilderTool $subQuery
     */
    protected $subQuery;

    public function __construct($field, $alias, QueryBuilderTool $subQuery, $comparison_type = self::IN, $connect_type = QueryBuilderTool::CONNECT_AND) {
        $type = self::NO_TYPE;
        $label = $this->getUniqueLabel();
        $this->comparison_type = $comparison_type;
        $this->subQuery = $subQuery;
        parent::__construct($type, $field, $alias, $label, null, $connect_type);
    }

    public function getCondition() {
        $this->parameters = $this->subQuery->getParameters();
        $alias = empty($this->alias) ? "" : $this->alias . '.';
        return "{$alias}{$this->field} {$this->comparison_type} ({$this->subQuery->getQuery()}) ";
    }

    private function getUniqueLabel() {
        return $this->condition_uid . ++self::$count;
    }

    /**
     * @return QueryBuilderTool
     */
    public function getSubQuery() {
        return $this->subQuery;
    }

    /**
     * @param QueryBuilderTool $subQuery
     */
    public function setSubQuery($subQuery) {
        $this->subQuery = $subQuery;
    }

    public function getParameters() {
        return $this->subQuery->getParameters();
    }

}