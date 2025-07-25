<?php
/**
 * Created by PhpStorm.
 * User: canngo
 * Date: 8/18/18
 * Time: 3:30 PM
 */

namespace QueryBuilderBundle;


use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Statement;
use PDO;

abstract class ManageFilters {

    /** @var EntityManager */
    protected $doctrine;

    protected $sections = array();

    protected $optionalColumns;

    public abstract function setFilters($conditions);

    public function __construct($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    protected function getFiltersValues($filters) {
        /** @var $filter Filter */
        foreach ($filters as $filter) {
            $builder = $filter->getQueryBuilder();
            if (isset($builder)) {
                $values = $this->executeQuery($builder);
                $filter->setSelectValues($values);
            }
        }
    }

    /**
     * Execute sql queries
     * @param QueryBuilderTool $sqlBuilder
     * @return array
     */
    protected function executeQuery($sqlBuilder) {
        $result = array();
        $sql = $sqlBuilder->getQuery();
        $pdo = $this->doctrine->getConnection();
        if (!empty($sql)) {
            /* @var $sth Statement */
            $sth = $pdo->prepare($sql);
            $sqlBuilder->bindParameters($sth);
            $sth->execute();
            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    protected function addSection($section) {
        $this->sections[] = $section;
    }

    protected function setSections($sections) {
        $this->sections = $sections;
    }

    public function getFiltersBySections($filters) {
        $default = /** @Desc("Defaults") */ $this->tr->trans("filter.section.defaults", array(), "quality-client");
        $othersSections = array();
        $filtersBySection = array();
        foreach($filters as $filter) {
            // set a default section if no section
            if($filter->getSection() == "") {
                $filter->setSection($default);
            }
            // if section was forgotten
            if(!in_array($filter->getSection(), $this->sections) && !in_array($filter->getSection(), $othersSections) ) {
                $othersSections[] = $filter->getSection();
            }
        }
        $this->sections = array_merge($this->sections, $othersSections);
        foreach($this->sections as $section) {
            if(!isset($filtersBySection[$section]) ){
                $filtersBySection[$section] = array();
            }
            foreach($filters as $filter) {
                if($filter->getSection() == $section) {
                    $filtersBySection[$section][] = $filter;
                }
            }

            uasort($filtersBySection[$section], function ($filter1, $filter2) {
                return strcasecmp($filter1->getGroup(), $filter2->getGroup());
            });
        }




        return $filtersBySection;
    }

    public function setOptionalColumns($columns)
    {
        $this->optionalColumns = $columns;
    }

    /**
     * This creates a filter WHERE (table_alias.field LIKE '%filter_value%')
     *
     * @param $tableAlias string The table name
     * @param $filterId string The filter ID. This should be the name of the column as defined in
     * 	SettingsManager::getAllAssignmentOptionalFields.
     * @param $text string The label to display, already translated
     * @param $field string Optional. The name of the field in the DB table. Defaults to the filter_id.
     */
    protected function addOptionalTextFilter(&$filters, $optionalFields, $tableAlias, $filterId, $text, $field = '', $section = null) {
        if ($field == '') {
            $field = $filterId;
        }
        if(array_key_exists($filterId, $optionalFields) ) {
            $condition = new Condition(Condition::IN_TEXT, $field, $tableAlias);
            $filter = new Filter(Filter::TYPE_TEXT, $filterId, $text, '', $condition);
            if(isset($section) ) {
                $filter->setSection($section);
            }
            $filters[$filterId] = $filter;
        }
    }

    /**
     * This makes a filter WHERE (table_alias1.field1 LIKE '%filter_value%' OR table_alias2.field2 LIKE '%filter_value%'
     *
     * @param        $tableAlias1 string The first table
     * @param        $tableAlias2 string The second table
     * @param        $filterId    string The filter ID. This should be the name of the column as defined in
     *                            SettingsManager::getAllAssignmentOptionalFields.
     * @param        $trans       string The label to display, already translated
     * @param string $field1      string Optional. Name of field in first table. If left empty, defaults to filter_id
     * @param string $field2      string Optional. Name of field in second table. If left empty, defaults to filter_id
     * @param null   $section
     */
    protected function addOptionalMultiTableTextFilter(&$filters, $optionalFields, $tableAlias1, $tableAlias2, $filterId, $trans,
                                                       $field1 = '',
                                                       $field2 = '',
                                                       $section = null
    ) {
        if ($field1 == '') {
            $field1 = $filterId;
        }
        if ($field2 == '') {
            $field2 = $filterId;
        }
        if (array_key_exists($filterId, $optionalFields)) {
            $condition = new Condition(Condition::GROUP, $filterId, '');
            $condition->addGroupCondition(Condition::INTEXT, $field1, $tableAlias1, QueryBuilderTool::CONNECTOR);
            $condition->addGroupCondition(Condition::INTEXT, $field2, $tableAlias2, QueryBuilderTool::CONNECTOR);
            $filter = new Filter(Filter::TYPE_TEXT, $filterId, $trans, '', $condition);
            if (isset($section)) {
                $filter->setSection($section);
            }
            $filters[$filterId] = $filter;
        }
    }

    /**
     * Creates a from and to conditions like this:
     *
     * (
     * 	(DATE({$tableAlias}.{$fieldName}) >= DATE(:{$conditionFromId}))
     * 	OR
     * 	(DATE({$tableAlias}.{$fieldName}) >= DATE(:{$conditionFromId}))
     * )
     *
     * @param $tableAlias string The table alias defined in the main query
     * @param $filterId string Must match the column key defined in Settings
     * @param $textFrom string The translated text for the from filter
     * @param $textTo string The translated text for the to filter
     * @param $fieldName string The field name in the table. Defaults to $filterId
     */
    protected function addOptionalDateRangeFilter(&$filters, $optionalFields, $tableAlias, $filterId, $textFrom, $textTo, $fieldName = '', $section = null, $group = null) {
        if ($fieldName == '') {
            $fieldName = $filterId;
        }
        if(array_key_exists($filterId, $optionalFields) ) {
            $conditionFromId = "{$filterId}_from";
            $conditionToId = "{$filterId}_to";
            $condition = new Condition(Condition::TEXT, $conditionFromId, '');
            $condition->setConditionAsText(
                "(
					(DATE({$tableAlias}.{$fieldName}) >= DATE(:{$conditionFromId}))
					OR
					(DATE({$tableAlias}.{$fieldName}) >= DATE(:{$conditionFromId}))
				)"
            );
            $filter = new Filter(Filter::TYPE_DATE, $conditionFromId, $textFrom, '', $condition);
            if(isset($section) ) {
                $filter->setSection($section);
            }
            if(isset($group)) {
                $filter->setGroup($group);
            }
            $filters[$conditionFromId] = $filter;

            $condition = new Condition(Condition::TEXT, $conditionToId, '');
            $condition->setConditionAsText(
                "(
					(DATE({$tableAlias}.{$fieldName}) <= DATE(:{$conditionToId}))
					OR
					(DATE({$tableAlias}.{$fieldName}) <= DATE(:{$conditionToId}))
				)"
            );
            $filter = new Filter(Filter::TYPE_DATE, $conditionToId, $textTo, '', $condition);
            if(isset($section) ) {
                $filter->setSection($section);
            }
            if(isset($group)) {
                $filter->setGroup($group);
            }
            $filters[$conditionToId] = $filter;
        }
    }

    /**
     * The filter name and the field name should be the same
     *
     * @param $tableAlias
     * @param $filterId
     * @param $text
     */
    protected function addOptionalNumericFilter(&$filters, $optionalFields, $tableAlias, $filterId, $text, $section = null) {
        if(array_key_exists($filterId, $optionalFields) ) {
            $condition = new Condition(Condition::GOREQUAL, $filterId, $tableAlias);
            $filter = new Filter(Filter::TYPE_NUMBER, $filterId, $text." >=", '', $condition);
            if(isset($section) ) {
                $filter->setSection($section);
            }
            $filters[$filterId] = $filter;
        }
    }
}