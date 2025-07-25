<?php
/**
 * Created by PhpStorm.
 * User: canngo
 * Date: 8/18/18
 * Time: 2:49 PM
 */

namespace QueryBuilderBundle;


class Filter
{
    private $type;
    private $condition;
    private $name;
    private $default_value;
    private $default_text;
    private $label;
    private $value;
    private $select_values;
    private $query_builder;
    private $replacement_text;
    private $class;
    private $children;
    private $optgroups;
    private $section;
    private $group;
    private $maxval;

    const TYPE_TEXT = 'text';
    const TYPE_DATE = 'date';
    const TYPE_SELECT = 'select';
    const TYPE_CHOICE = 'choice';
    const TYPE_NUMBER = 'number';

    public function __construct($type, $name, $label, $default_value, Condition $condition, $query_builder = null, $section = "") {
        $this->type = $type;
        $this->name = $name;
        $this->label = $label;
        $this->group = $label;
        $this->default_value = $default_value;
        $this->condition = $condition;
        $this->query_builder = $query_builder;
        $this->select_values = array();
        $this->replacement_text = null;
        $this->value = null;
        $this->class = "";
        $this->section = $section;
    }

    public function setClass($class) {
        $this->class = $class;
    }

    public function getClass() {
        return $this->class;
    }

    public function setSection($section) {
        $this->section = $section;
    }

    public function getSection() {
        return $this->section;
    }

    public function setReplacementText(array $texts) {
        $this->replacement_text = $texts;
    }

    public function getReplacementText() {
        return $this->replacement_text;
    }

    public function setSelectValues($values) {
        $this->select_values = $values;
    }

    public function getSelectValues() {
        return $this->select_values;
    }

    public function getSelectedName() {
        if (!isset($this->value)) {
            return "";
        }
        if (isset($this->replacement_text)) {
            return $this->replacement_text[$this->value];
        }
        foreach ($this->select_values as $option) {
            if ($option['value'] == $this->value) {
                return $option['text'];
            }
        }
        return "";
    }

    public function setValue($value) {
        $this->value = $value;
    }

    public function setDefaultText($text) {
        $this->default_text = $text;
    }

    public function getQueryBuilder() {
        return $this->query_builder;
    }

    public function getDefaultText() {
        return $this->default_text;
    }

    public function getDefaultValue() {
        return $this->default_value;
    }

    public function setCondition(Condition $condition) {
        $this->condition = $condition;
    }

    public function getCondition() {
        return $this->condition;
    }

    public function getValue() {
        return $this->value;
    }

    public function getType() {
        return $this->type;
    }

    public function getName() {
        return $this->name;
    }

    public function getLabel() {
        return $this->label;
    }

    public function isFiltering() {
        return (isset($this->value) && $this->value != $this->default_value);
    }

    /**
     *
     * @return array
     */
    public function getChildren() {
        return $this->children;
    }

    /**
     * Set children
     * @param string $children (name of children filters)
     */
    public function setChildren($children) {
        $this->children = $children;
    }

    /**
     *
     * @return string
     */
    public function getOptgroups() {
        return $this->optgroups;
    }

    /**
     * Set optgroups
     * @param string $groups (values of optgroups that should always be displayed)
     */
    public function setOptgroups($groups) {
        $this->optgroups = $groups;
    }

    /**
     * Set value to filter and ajust/modify the conditions if needed
     * @param mixed $value
     * @return Condition
     */
    public function setConditionValue($value) {
        $this->setValue($value);
        if ($this->type == self::TYPE_TEXT && strpos($value, ',') !== false) {
            $value = preg_replace('/\s+/', '', $value);
            $values = explode(',', $value);
            /* @var $subcondition Condition */
            $subcondition = $this->getCondition();
            $condition = new Condition(Condition::GROUP, $subcondition->getField(), '');
            if ($subcondition->getType() == Condition::GROUP) {
                foreach ($values as $val) {
                    $valcondition = new Condition(Condition::GROUP, $subcondition->getField(), '');
                    foreach ($subcondition->getGroup() as $cpt => $groupSubcondition) {
                        $groupCondition = new Condition(
                            $groupSubcondition->getType(),
                            $groupSubcondition->getField(),
                            $groupSubcondition->getAlias()
                        );
                        $groupCondition->setConnectType($groupSubcondition->getConnectType());
                        $groupCondition->setValue($val);
                        $valcondition->addConditionToGroup($groupCondition);
                    }
                    $valcondition->setConnectType(QueryBuilderTool::CONNECT_OR);
                    $condition->addConditionToGroup($valcondition);
                }
            } else {
                foreach ($values as $val) {
                    $groupCondition = new Condition($subcondition->getType(), $subcondition->getField(), $subcondition->getAlias());
                    $groupCondition->setConnectType(QueryBuilderTool::CONNECT_OR);
                    $groupCondition->setValue($val);
                    $condition->addConditionToGroup($groupCondition);
                }
            }
        } else {
            $condition = $this->getCondition();
            $condition->setValue($value);
        }
        return $condition;
    }

    /**
     * @return mixed
     */
    public function getGroup() {
        return $this->group;
    }

    /**
     * @param mixed $group
     * @return Filter
     */
    public function setGroup($group) {
        $this->group = $group;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMaxval() {
        return $this->maxval;
    }

    /**
     * @param mixed $maxval
     * @return Filter
     */
    public function setMaxval($maxval) {
        $this->maxval = $maxval;
        return $this;
    }

}