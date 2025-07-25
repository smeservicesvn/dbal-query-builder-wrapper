<?php

/**
 * Created by PhpStorm.
 * User: canngo
 * Date: 8/18/18
 * Time: 2:54 PM
 */

use QueryBuilderBundle\QueryBuilderTool;
use QueryBuilderBundle\Condition;
use QueryBuilderBundle\SQLiteDB;

use PHPUnit\Framework\TestCase;

class SimpleQueryTest extends TestCase {

    public function testSimpleQuery() {

        $queryBuilder = new QueryBuilderTool();
        $select = "u.username, u.id ";
        $queryBuilder->addToSelect($select);
        $queryBuilder->setFrom('users', 'u');
        $queryBuilder->addJoins(QueryBuilderTool::LEFT_JOIN, "groups", "gr", "u.group_id = gr.id");
        $queryBuilder->addGroupBy('id', 'gr');

        $queryBuilder->addIntCondition('recycled', 'u', 0, Condition::EQUAL);
        $condition = new Condition(Condition::IS_NOT_NULL, 'id', 'gr');
        $queryBuilder->addCondition($condition);
        $query = $queryBuilder->getSqlQueryWithParams();
        echo $query;
        self::assertEquals($query, 'SELECT u.username, u.id  FROM users u  LEFT JOIN groups gr ON u.group_id = gr.id  WHERE  u.recycled = 0  AND  gr.id IS NOT NULL  GROUP BY gr.id');
    }

    public function testAddToSelectAndFrom() {
        $qb = new QueryBuilderTool();
        $qb->addToSelect('id, name');
        $qb->setFrom('users', 'u');
        $sql = $qb->getSqlQueryWithParams();
        self::assertStringContainsString('SELECT id, name', $sql);
        self::assertStringContainsString('FROM users u', $sql);
    }

    public function testAddIntCondition() {
        $qb = new QueryBuilderTool();
        $qb->addToSelect('id');
        $qb->setFrom('users', 'u');
        $qb->addIntCondition('id', 'u', 5, Condition::EQUAL);
        $sql = $qb->getSqlQueryWithParams();
        self::assertStringContainsString('u.id = 5', $sql);
    }

    public function testOrderByAndLimit() {
        $qb = new QueryBuilderTool();
        $qb->addToSelect('id');
        $qb->setFrom('users', 'u');
        $qb->addOrderBy('id', QueryBuilderTool::ORDER_DESC);
        $qb->setLimit('10');
        $sql = $qb->getSqlQueryWithParams();
        self::assertStringContainsString('ORDER BY id DESC', $sql);
        self::assertStringContainsString('LIMIT 10', $sql);
    }

    public function testConditionObject() {
        $cond = new Condition(Condition::EQUAL, 'id', 'u', ':id', 1);
        self::assertEquals(Condition::EQUAL, $cond->getType());
        self::assertEquals('id', $cond->getField());
        self::assertEquals('u', $cond->getAlias());
        self::assertEquals(':id', $cond->getLabel());
        self::assertEquals(1, $cond->getValue());
    }

    public function testFilterObject() {
        $cond = new Condition(Condition::EQUAL, 'id', 'u', ':id', 1);
        $filter = new \QueryBuilderBundle\Filter(
            \QueryBuilderBundle\Filter::TYPE_NUMBER,
            'id',
            'ID',
            0,
            $cond
        );
        self::assertEquals('id', $filter->getName());
        self::assertEquals('ID', $filter->getLabel());
        self::assertEquals(0, $filter->getDefaultValue());
        self::assertEquals($cond, $filter->getCondition());
    }

    public function testGroupedCondition() {
        $cond1 = new Condition(Condition::EQUAL, 'age', 'u', ':age', 30);
        $cond2 = new Condition(Condition::GREATER, 'score', 'u', ':score', 100);
        $group = new \QueryBuilderBundle\GroupedCondition('','');
        $group->addConditionToGroup($cond1);
        $group->addConditionToGroup($cond2);
        $text = $group->getCondition();
        self::assertStringContainsString('age =', $text);
        self::assertStringContainsString('score >', $text);
        self::assertStringContainsString('(', $text);
        self::assertStringContainsString(')', $text);
        self::assertCount(2, $group->getGroupedConditions());
    }

    public function testOrderTool() {
        $order = new \QueryBuilderBundle\OrderTool('name', 'DESC');
        self::assertEquals('name', $order->getField());
        self::assertEquals('DESC', $order->getOrder());
        $order->setField('id');
        $order->setOrder('ASC');
        self::assertEquals('id', $order->getField());
        self::assertEquals('ASC', $order->getOrder());
    }

    public function testManageQuerySetters() {
        $doctrineStub = new class {
            public function getConnection() { return null; }
        };
        $stub = new class($doctrineStub, true) extends \QueryBuilderBundle\ManageQuery {
            public function buildQuery($columns, $filters) {}
        };
        $stub->setActive(0);
        $stub->setPage(2);
        $stub->setTotalRows(100);
        $stub->setRowsPerPage(25);
        $stub->setMaxPages(4);
        $stub->setAllowDump(false);
        self::assertEquals(0, $stub->getActive());
        self::assertEquals(2, $stub->getPage());
        self::assertEquals(100, $stub->getTotalRows());
        self::assertEquals(25, $stub->getRowsPerPage());
        self::assertEquals(4, $stub->getMaxPages());
        self::assertFalse($stub->getAllowDump());
    }

}
