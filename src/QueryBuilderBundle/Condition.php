<?php
/**
 * Created by PhpStorm.
 * User: canngo
 * Date: 8/17/18
 * Time: 9:38 PM
 */

namespace QueryBuilderBundle;

class Condition {

    /**
     * Is rendered as [field] = [value]
     */
    const EQUAL = "=";
    /**
     * Is rendered as [field] != [value]
     */
    const NOT_EQUAL = "!=";
    /**
     * Is rendered as [field] >= [value]
     */
    const GOR_EQUAL = ">=";
    /**
     * Is rendered as [field] <= [value]
     */
    const LOR_EQUAL = "<=";
    /**
     * Is rendered as [field] > [value]
     */
    const GREATER = ">";
    /**
     * Is rendered as [field] < [value]
     */
    const LESS = "<";
    /**
     * Is rendered as [field] IN [value]
     */
    const IN = "IN";
    /**
     * Is rendered as [field] NOT IN [value]
     */
    const NOT_IN = "NOT IN";

    const BEGIN_WITH = "BEGINWITH";
    const FINISH_WITH = "FINISHWITH";
    const IN_TEXT = "INTEXT";
    const IS_NULL = "IS NULL";
    const LIKE = "LIKE";
    const IS_NOT_NULL = "IS NOT NULL";
    const GROUP = "GROUP";
    const TEXT = "TEXT";
    const TEXT_IN_TEXT = "TEXTINTEXT";
    const TEXT_IN = "TEXTIN";
    const NO_PARAM = "NOPARAM";
    const FUNC = "FUNCTION";
    const NO_TYPE = "NO_TYPE";

    const FILTER_WHERE = 1;
    const FILTER_HAVING = 2;

    protected $type;
    protected $field;
    protected $alias;
    protected $connect_type;
    protected $value;
    protected $text;
    protected $label;
    protected $group;

    /**
     * Where of Having
     * @var type FILTER_WHERE or FILTER_HAVING
     */
    protected $filter_type;

    protected $parameters = array();

    private static $count = 0;

    protected $condition_uid = ":condition_";

    /**
     * @param string $type One of the Condition::TYPE constants
     * @param string $field The name of the field to apply the condition to
     * @param string $alias The alias of the table containing the field
     * @param string $label The label used for substitution inside the query
     * @param null $value The value to which the field should be compared to
     * @param string $connect_type One of QueryBuilderTool::CONNECTAND or QueryBuilderTool::CONNECTOR
     */
    public function __construct($type, $field, $alias, $label = "", $value = null, $connect_type = QueryBuilderTool::CONNECT_AND) {
        // by default is a where condition
        $this->filter_type = self::FILTER_WHERE;
        $this->type = $type;
        $this->field = $field;
        $this->alias = $alias;
        $this->value = $value; // TODO: QUESTION: Pourquoi on fait pas le meme traitement que dans setValue?
        $this->setLabel($label);
        $this->connect_type = $connect_type;
        if($type == self::GROUP) {
            $this->group = array();
        }
    }

    public function getFilterType() {
        return $this->filter_type;
    }

    /**
     * Set condition as a Having condition
     */
    public function setAsHaving() {
        $this->filter_type = self::FILTER_HAVING;
    }

    /**
     * Set condition as a Where condition
     */
    public function setAsWhere() {
        $this->filter_type = self::FILTER_WHERE;
    }

    /**
     * Add the passed condition to the group
     *
     * @param Condition $condition The condition to add to the group
     */
    public function addConditionToGroup(Condition $condition) {
        $this->group[] = $condition;
    }

    /**
     * Creates a condition and add it to the group
     *
     * @param string $type One of the Condition::TYPE constants
     * @param string $field The name of the field to apply the condition to
     * @param string $alias The alias of the table containing the field
     * @param string $connect_type One of QueryBuilderTool::CONNECTAND or QueryBuilderTool::CONNECTOR
     * @param string $label The label used as parameter name for substitution inside the query (like in bindParam)
     * @param null $value The value to which the field should be compared to
     */
    public function addGroupCondition($type, $field, $alias, $connect_type, $label="", $value=null){
        $condition = new Condition($type, $field, $alias, $label, $value, $connect_type);
        $this->group[] = $condition;
    }

    public function getCondition() {
        $condition = "";
        if($this->type == self::TEXT || $this->type == self::NO_PARAM || $this->type == self::TEXT_IN_TEXT || $this->type == self::TEXT_IN) {
            $condition = " ".$this->getTextCondition()." ";
        } elseif ($this->type == self::IN || $this->type == self::NOT_IN) {
            $condition = !empty($this->alias) ? " $this->alias.$this->field $this->type (" : " $this->field $this->type (";
            $labels = "";
            foreach($this->label as $label) {
                $labels .= empty($labels) ? $label : ",$label";
            }
            $condition .= "$labels)";
        } elseif ($this->type == self::IN_TEXT || $this->type == self::BEGIN_WITH || $this->type == self::FINISH_WITH) {
            $condition = !empty($this->alias) ? " $this->alias.$this->field LIKE $this->label " : " $this->field LIKE $this->label ";
        } elseif ($this->type == self::FUNC) {
            $condition = " $this->value ";
        } elseif($this->type == self::GROUP) {
            $condition = " (";
            /** @var $add Condition */
            foreach($this->group as $cpt => $add){
                if($cpt > 0) {
                    $condition .= " $add->connect_type ";
                }
                $condition .= $add->getCondition();
            }
            $condition .= ") ";
        } elseif($this->type == self::IS_NULL || $this->type == self::IS_NOT_NULL) {
            $condition = !empty($this->alias) ? " $this->alias.$this->field $this->type " : " $this->field $this->type ";
        } else {
            $condition = !empty($this->alias) ? " $this->alias.$this->field $this->type $this->label " : " $this->field $this->type $this->label ";
        }

        return $condition;
    }

    public function getGroup() {
        return $this->group;
    }

    public function getLabel() {
        return $this->label;
    }

    /**
     * Sets the string to use as a parameter placeholder in the query.
     * IF an empty string is passed, it will use a generated unique label.
     * If the given label is not prefixed with a colon, it will automatically add one.
     *
     * @param string $label The label to use
     */
    public function setLabel($label = "") {
        if (gettype($label) == 'array') {
            $this->label = $label;
        } else if ($label == "") {
            $this->label = $this->getUniqueLabel();
        } else {
            $this->label = substr($label,0,1) == ":" ? $label : ":$label";
        }
    }

    public function setConditionAsText($condition) {
        $this->text = $condition;
    }

    public function getTextCondition() {
        return $this->text;
    }

    /**
     * Sets the connect type for this Condition
     *
     * @param $connect One of QueryBuilderTool::CONNECT_AND or QueryBuilderTool::CONNECT_OR
     */
    public function setConnectType($connect) {
        $this->connect_type = $connect;
    }

    /**
     * @return string The value of one of QueryBuilderTool::CONNECT_AND or QueryBuilderTool::CONNECT_OR
     */
    public function getConnectType() {
        return $this->connect_type;
    }

    public function setField($field) {
        $this->field = $field;
    }
    public function getField() {
        return $this->field;
    }

    public function setAlias($alias) {
        $this->alias = $alias;
    }
    public function getAlias() {
        return $this->alias;
    }

    public function setValue($value) {
        if($this->type == self::BEGIN_WITH) {
            $this->value = "$value%";
        } elseif($this->type == self::FINISH_WITH) {
            $this->value = "%$value";
        } elseif($this->type == self::IN_TEXT || $this->type == self::TEXT_IN_TEXT) {
            $this->value = "%$value%";
        } elseif($this->type == self::FUNC) {
            $this->value = "$value$this->field";
        } elseif($this->type == self::EQUAL && $value == "#NULL") {
            $this->type = self::IS_NULL;
        } elseif($this->type == self::GROUP) {
            foreach($this->group as $condition){
                $condition->setValue($value);
            }
        } elseif($this->type == self::TEXT_IN ) {
            $labels = array();
            $newLabel = "";
            foreach($value as $i => $val) {
                $label = ":".$this->field."invalue$i";
                $newLabel .= empty($newLabel) ? $label : ",$label";
                $labels[] = $label;
            }
            $this->text = str_replace(":".$this->field, $newLabel, $this->text);
            $this->label = $labels;
            $this->value = $value;
        } else {
            if(isset($this->value)){
                $this->value = array($this->value);
            }else{
                $this->value = $value;
            }
        }
    }

    public function getValue() {
        return $this->value;
    }

    public function setType($type) {
        $this->type = $type;
    }
    public function getType() {
        return $this->type;
    }

    private function getUniqueLabel() {
        return $this->condition_uid . ++self::$count;
    }
}