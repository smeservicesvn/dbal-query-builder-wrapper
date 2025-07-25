<?php
/**
 * Created by PhpStorm.
 * User: canngo
 * Date: 8/18/18
 * Time: 2:48 PM
 */

namespace QueryBuilderBundle;


class BindParams
{
    public $label;
    public $value;

    public function getLabel() {
        return $this->label;
    }

    public function getValue() {
        return $this->value;
    }

    public function setLabel($label) {
        $this->label = $label;
        return $this;
    }

    public function setValue($value) {
        $this->value = $value;
        return $this;
    }

}