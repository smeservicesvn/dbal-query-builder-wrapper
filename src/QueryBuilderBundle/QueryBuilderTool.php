<?php

/**
 * Created by PhpStorm.
 * User: canngo
 * Date: 8/17/18
 * Time: 9:30 PM
 */

namespace QueryBuilderBundle;

use Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Statement;

class QueryBuilderTool {

    const LEFT_JOIN = "LEFT JOIN";
    const INNER_JOIN = "INNER JOIN";
    const RIGHT_JOIN = "RIGHT JOIN";
    const ORDER_ASC = "ASC";
    const ORDER_DESC = "DESC";
    const CONNECT_AND = " AND ";
    const CONNECT_OR = " OR ";
    protected $useBlockNestedLoop = true;
    private $select = "";
    private $bind_params = array();

    /** @var $conditions Condition[] */
    private $conditions = array();
    private $from = "";
    private $joins = array();
    private $groupBy = "";
    private $orderBy = "";
    private $limit = "";
    private $from_alias = "";
    private $count_select = "";

    /**
     * Add select fields
     * @param string $select
     */
    public function addToSelect($select) {
        $this->select .= empty($this->select) ? "SELECT $select" : ", $select";
    }

    public function addToCountSelect($select){
        $this->count_select .= empty($this->count_select) ? "$select" : ", $select";
    }

    /**
     * Add a parameter
     * @param string $label
     * @param $value
     */
    public function addParameters($label, $value) {
        $param = new \stdClass();
        $param->label = $label;
        $param->value = $value;
        $this->bind_params[] = $param;
    }

    /**
     * Add a condition of type integer
     * @param string $field
     * @param string $alias
     * @param $value
     * @param (EQUAL / NOT_EQUAL)
     * @param $connect (CONNECT_AND / CONNECT_OR)
     */
    public function addIntCondition($field, $alias, $value, $type = Condition::EQUAL , $connect = QueryBuilderTool::CONNECT_AND) {
        $label = ":value".(1+count($this->conditions) );
        $this->addParameters($label, $value);
        $condition = new Condition($type, $field, $alias, $label);
        $condition->setConnectType($connect);
        $this->conditions[] = $condition;
    }

    public function addCondition(Condition $condition, $isgroup = false) {
        if ($condition->getType() == Condition::NO_TYPE) {
            foreach ($condition->getParameters() as $parameter) {
                $this->addParameters($parameter->label, $parameter->value);
            }
        } else if($condition->getType() != Condition::FUNC && $condition->getType() != Condition::IS_NULL && $condition->getType() != Condition::IS_NOT_NULL && $condition->getType() != Condition::NO_PARAM) {
            if($condition->getType() == Condition::TEXT || $condition->getType() == Condition::TEXT_IN_TEXT) {
                $label = ":".$condition->getField();
            } elseif($condition->getType() == Condition::GROUP) {
                foreach($condition->getGroup() as $cpt => $group_condition) {
                    if($group_condition->getType() == Condition::GROUP) {
                        $this->addCondition($group_condition, true);
                    } elseif($group_condition->getType() == Condition::IN) {
                        $cndLabel = $group_condition->getLabel();
                        if(is_array($cndLabel) ) {
                            $i = 0;
                            foreach($group_condition->getValue() as $cpt => $value) {
                                $label = $cndLabel[$i];
                                $this->addParameters($label, $value);
                                $i++;
                            }
                        } else {
                            $condition_label = array();
                            foreach($group_condition->getValue() as $cpt => $value) {
                                $label =  $group_condition->getLabel()."invalue".$cpt;
                                $condition_label[] = $label;
                                $this->addParameters($label, $value);
                            }
                            $group_condition->setLabel($condition_label);
                        }
                    }else if ($group_condition->getType() == Condition::IS_NOT_NULL || $group_condition->getType() == Condition::IS_NULL){
                        $label = $condition->getLabel()."gvalue$cpt";
                        $group_condition->setLabel($label);
                    } else {
                        $label = $condition->getLabel()."gvalue$cpt";
                        $group_condition->setLabel($label);
                        if($group_condition->getType() != Condition::TEXT && $group_condition->getType() != Condition::TEXT_IN_TEXT && $group_condition->getType() != Condition::IS_NULL) {
                            $this->addParameters($label, $group_condition->getValue());
                        }
                    }
                }
            } elseif($condition->getType() == Condition::IN || $condition->getType() == Condition::NOT_IN || $condition->getType() == Condition::TEXT_IN) {
                $cndLabel = $condition->getLabel();
                if(is_array($cndLabel) ) {
                    $i = 0;
                    foreach($condition->getValue() as $cpt => $value) {
                        $label = $cndLabel[$i];
                        $this->addParameters($label, $value);
                        $i++;
                    }
                } else {
                    $condition_label = array();
                    foreach($condition->getValue() as $cpt => $value) {
                        $label =  $condition->getLabel()."invalue".$cpt;
                        $condition_label[] = $label;
                        $this->addParameters($label, $value);
                    }
                    $condition->setLabel($condition_label);
                }
            } else {
                $label = $condition->getLabel();
                if (empty($label)) {
                    $label = ":value" . (1 + count($this->conditions));
                }
            }
            if($condition->getType() != Condition::GROUP && $condition->getType() != Condition::IN && $condition->getType() != Condition::NOT_IN && $condition->getType() != Condition::TEXT_IN) {
                $condition->setLabel($label);
                $this->addParameters($label, $condition->getValue());
            }
        }
        if(!$isgroup) {
            $this->conditions[] = $condition;
        }
    }

    /**
     * @return Condition[]
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    public function getCondition($fieldKey)
    {
        foreach ($this->conditions as $key => $condition) {
            if($condition->getField() == $fieldKey) {
                return $condition;
            }
        }
    }


    /**
     * Provide a way to remove conditions from the current scope
     *
     * @param Condition $condition
     * @return bool
     */
    public function removeCondition(Condition $condition)
    {
        $searchKey = $condition->getField();
        foreach ($this->conditions as $key => $existingCondition) {
            if($existingCondition->getField() == $searchKey) {
                unset($this->conditions[$key]);
                return true;
            }
        }
    }


    /**
     * Set from part of the query
     * @param string $tablename (or from string)
     * @param string $alias
     */
    public function setFrom($tablename, $alias) {
        $this->from_alias = ($alias != "") ? $alias : $tablename;
        $this->from = " FROM $tablename $alias ";
    }

    /**
     * Add a join to the query
     * @param $join_type (LEFT_JOIN or INNER_JOIN)
     * @param string $tablename
     * @param string $alias
     * @param string $condition
     */
    public function addJoins($join_type, $tablename, $alias, $condition) {
        $this->joins[] = empty($condition) ? " $join_type $tablename $alias " : " $join_type $tablename $alias ON $condition ";
    }

    /**
     * Add a groupby field
     * @param string $field
     * @param string $alias
     */
    public function addGroupBy($field, $alias = '') {
        $groupby = empty($alias) ? $field : "$alias.$field";
        $this->groupBy .= empty($this->groupBy) ? " GROUP BY $groupby" : ", $groupby";
    }

    /**
     * Set a groupby field
     * @param string $field
     * @param string $alias
     */
    public function setGroupBy($field, $alias) {
        $groupby = empty($alias) ? $field : "$alias.$field";
        $this->groupBy = " GROUP BY $groupby ";
    }

    /**
     * Add a order by field
     * @param string $field
     * @param $order
     */
    public function addOrderBy($field, $order = QueryBuilderTool::ORDER_ASC) {
        if (!empty($field) && !empty($order)) {
            $this->orderBy .= empty($this->orderBy) ? " ORDER BY $field $order" : ", $field $order";
        }
    }

    /**
     * Set the limit of the query
     * @param integer $limit
     */
    public function setLimit($limit){
        $this->limit = " LIMIT $limit";
    }

    public function addWhereConditionsToQuery($query){
        $last_condition = null;
        if(!empty($this->conditions) ){
            $cpt = 0;
            while($cpt < count($this->conditions)) {
                if($this->conditions[$cpt]->getFilterType() == Condition::FILTER_WHERE) {
                    if(isset($last_condition) ) {
                        $query .= $last_condition->getConnectType();
                    } else {
                        $query .= " WHERE ";
                    }

                    $last_condition = $this->conditions[$cpt];
                    $query .= $this->conditions[$cpt]->getCondition();
                }
                $cpt ++;
            }
        }
        return $query;
    }

    private function addHavingConditionsToQuery($query){
        // Add having conditions
        if(!empty($this->conditions) ){
            $cpt = 0;
            $last_condition = null;
            while($cpt < count($this->conditions)) {
                if($this->conditions[$cpt]->getFilterType() == Condition::FILTER_HAVING) {
                    if(isset($last_condition) ) {
                        $query .= $last_condition->getConnectType();
                    } else {
                        $query .= " HAVING ";
                    }
                    $last_condition = $this->conditions[$cpt];
                    $query .= $this->conditions[$cpt]->getCondition();
                }
                $cpt ++;
            }
        }
        return $query;
    }
    /**
     * Get the query
     * @return string
     */
    public function getQuery() {
        $query = $this->select . $this->from;
        foreach($this->joins as $join) {
            $query .= $join;
        }

        $query = $this->addWhereConditionsToQuery($query);

        $query .= $this->groupBy;

        $query = $this->addHavingConditionsToQuery($query);

        $query .= $this->orderBy;
        $query .= $this->limit;

        return $query;
    }

    /**
     * @return mixed|string
     */
    public function getQueryWithoutCalcRows() {

        $query = $this->select . $this->from;

        $query = str_ireplace('SQL_CALC_FOUND_ROWS','',$query);

        // Remove possible duplicates
        $this->joins = array_unique($this->joins);
        foreach($this->joins as $join) {
            $query .= $join;
        }

        $query = $this->addWhereConditionsToQuery($query);

        $query .= $this->groupBy;

        $query = $this->addHavingConditionsToQuery($query);

        $query .= $this->orderBy;
        $query .= $this->limit;

        return $query;
    }

    public function getCountQuery() {
        $query = "";
        if(!empty($this->select) ) {
            $query = "SELECT COUNT(*) AS recordCount FROM (". $this->select . $this->from;
            // Remove possible duplicates
            $this->joins = array_unique($this->joins);
            foreach($this->joins as $join) {
                $query .= $join;
            }

            // add where conditions
            $query = $this->addWhereConditionsToQuery($query);

            $query .= $this->groupBy;

            // Add having conditions
            $query = $this->addHavingConditionsToQuery($query);

            $query .= ") as count GROUP BY NULL";
        }
        return $query;
    }

    public function getLightweightCountQuery() {
        $query = "";
        if(!empty($this->select) ) {
            $query = "SELECT COUNT(count.id) AS recordCount FROM (SELECT {$this->from_alias}.id";
            if(!empty($this->count_select) ) {
                $query .= ", ".$this->count_select;
            }
            $query .= " {$this->from}";
            // Remove possible duplicates
            $this->joins = array_unique($this->joins);
            foreach($this->joins as $join) {
                $query .= $join;
            }

            // add where conditions
            $query = $this->addWhereConditionsToQuery($query);

            $query .= $this->groupBy;

            // Add having conditions
            $query = $this->addHavingConditionsToQuery($query);

            $query .= ") as count GROUP BY NULL";
        }
        return $query;
    }

    public function getParameters() {
        return $this->bind_params;
    }

    public function setParameters($params) {
        $this->bind_params = $params;
    }

    public function bindParameters(Statement $sth) {
        foreach($this->bind_params as $param){
            $sth->bindParam($param->label, $param->value);
        }
    }

    public function getSqlQueryWithParams(){
        $sql = $this->getQueryWithoutCalcRows();
        foreach ($this->getParameters() as $param) {
            $value = $param->value;
            if(is_array($param->value) ) {
                $value = implode(',', $param->value);
            }
            $sql = str_replace(array(
                "{$param->label},",
                "{$param->label} ",
                "{$param->label})"
            ), array(
                "{$value},",
                "{$value} ",
                "{$value})"
            ), $sql);
        }
        return $sql;
    }

    public function setUseBlockNestedLoop(bool $useBNL) {
        $this->useBlockNestedLoop = $useBNL;

        return $this;
    }

    public function isUseBlockNestedLoop() {
        return $this->useBlockNestedLoop;
    }
}