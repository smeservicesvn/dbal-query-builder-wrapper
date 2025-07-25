<?php
/**
 * Created by PhpStorm.
 * User: canngo
 * Date: 8/18/18
 * Time: 2:49 PM
 */

namespace QueryBuilderBundle;


class OrderTool
{
    private $order = "";
    private $field = "";

    public function __construct($field, $order) {
        $this->field = $field;
        $this->order = $order;
    }

    public function getOrder() {
        return $this->order;
    }

    public function setOrder($order) {
        $this->order = $order;
    }

    public function getField() {
        return $this->field;
    }

    public function setField($field) {
        $this->field = $field;
    }

}