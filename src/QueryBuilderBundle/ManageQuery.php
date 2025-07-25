<?php
/**
 * Created by PhpStorm.
 * User: canngo
 * Date: 8/18/18
 * Time: 3:18 PM
 */

namespace QueryBuilderBundle;


use Doctrine\ORM\EntityManager;
use Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Statement;
use PDO;

abstract class ManageQuery {


    /** @var EntityManager */
    protected $doctrine;
    /** @var bool */
    protected $showTotalCount = true;

    /** @var PDO */
    protected $pdo;

    /** @var QueryBuilderTool */
    protected $sqlBuilder;

    protected $active = 1;

    protected $page = 0;

    protected $total_rows = 0;

    protected $rows_per_page = 50;

    protected $max_pages = 0;


    /**
     * @var array
     */
    protected $conditions = array();

    /**
     * @var array
     */
    protected $default_conditions = array();

    /**
     * @var OrderTool $order
     */
    protected $order;

    /**
     *
     * @var boolean
     */
    protected $allowDump = true;


    abstract public function buildQuery($columns, $filters);

    public function __construct($doctrine, $dump) {
        $this->doctrine = $doctrine;
        $this->pdo = $doctrine->getConnection();
        $this->allowDump = $dump;
    }

    public function getActive() {
        return $this->active;
    }

    public function setActive($active) {
        $this->active = $active;
    }

    public function getPage() {
        return $this->page;
    }

    public function setPage($page) {
        $this->page = $page;
    }

    public function getTotalRows() {
        return $this->total_rows;
    }

    public function setTotalRows($total_rows) {
        $this->total_rows = $total_rows;
    }

    public function getRowsPerPage() {
        return $this->rows_per_page;
    }

    public function setRowsPerPage($rows_per_page) {
        $this->rows_per_page = $rows_per_page;
    }

    public function getOrder() {
        return $this->order;
    }

    public function setOrder(OrderTool $order) {
        $this->order = $order;
    }

    public function getConditions() {
        return $this->conditions;
    }

    public function setConditions($conditions) {
        $this->conditions = $conditions;
    }

    public function addCondition($condition) {
        $this->conditions[] = $condition;
    }

    public function getDefaultConditions() {
        return $this->default_conditions;
    }

    public function setDefaultConditions($conditions) {
        $this->default_conditions = $conditions;
    }

    public function getAllowDump() {
        return $this->allowDump;
    }

    public function setAllowDump($allowDump) {
        $this->allowDump = $allowDump;
    }

    public function getMaxPages() {
        return $this->max_pages;
    }

    public function setMaxPages($max_pages) {
        $this->max_pages = $max_pages;
    }


    public function setShowTotalCount($show) {
        $this->showTotalCount = $show;
    }

    /**
     * Execute a count query
     */
    protected function executeCountQuery() {
        $countSql = $this->sqlBuilder->getCountQuery();
        if (!empty($countSql)) {
            $this->executeCountQueryStatement($countSql);
        }
    }

    protected function executeLightweightCountQuery() {
        $countSql = $this->sqlBuilder->getLightweightCountQuery();
        if (!empty($countSql)) {
            $this->executeCountQueryStatement($countSql);
        }
    }

    protected function executeCountQueryStatement($countSql) {
        if (!empty($countSql)) {
            /* @var $sth Statement */
            $sth = $this->pdo->prepare($countSql);
            if ($this->allowDump) {
                dump($this->getSqlQueryWithParams($countSql, $this->sqlBuilder->getParameters()));
            }
            $this->sqlBuilder->bindParameters($sth);
            $sth->execute();

            $result = $sth->fetch(PDO::FETCH_NUM);
            $this->total_rows = $result[0];
            //Max number of pages
            $this->max_pages = ceil($this->total_rows / $this->rows_per_page);

        }
    }

    /**
     * @param QueryBuilderTool $sqlBuilder
     *
     * @return array|mixed
     * @throws \Exception
     */
    protected function executeQueryWithoutRowsCount(QueryBuilderTool $sqlBuilder) {
        $result = array();
        $sql = $sqlBuilder->getQueryWithoutCalcRows();
        if (!empty($sql)) {
            /* @var $sth Statement */
            $sth = $this->pdo->prepare($sql);
            if ($this->allowDump) {
                dump($this->getSqlQueryWithParams($sql, $sqlBuilder->getParameters()));
            }
            $sqlBuilder->bindParameters($sth);
            $sth->execute();
            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    /**
     * Execute sql queries
     * @param QueryBuilderTool $sqlBuilder
     * @return array
     */
    protected function executeQuery($sqlBuilder) {

        $result = array();
        $sql = $sqlBuilder->getQuery();

        if (!empty($sql)) {
            /* @var $sth Statement */
            $sth = $this->pdo->prepare($sql);
            if ($this->allowDump) {
                dump($this->getSqlQueryWithParams($sql, $sqlBuilder->getParameters()));
            }
            $sqlBuilder->bindParameters($sth);
            $sth->execute();
            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    /**
     *
     * @return array|mixed
     */
    public function executeFindTotalRowsQuery () {
        $sql = 'SELECT FOUND_ROWS();';
        $result = array();
        if (!empty($sql)){
            /* @var $sth Statement */
            $sth = $this->pdo->prepare($sql);
            $sth->execute();
            $result = $sth->fetch();
        }
        return $result;
    }

    protected function getSqlQueryWithParams($sql, $params) {
        foreach ($params as $param) {
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
}