<?php

class DataFilterListForm extends Form {

    public function getFields() {
        return array(
            array(
                'value' => '<div ng-init=\"value[$index].show = false\" style=\"cursor:pointer;padding-bottom:1px;\" ng-click=\"value[$index].show = !value[$index].show\">
<div class=\"label data-filter-name pull-right\"> {{value[$index].filterType}}</div>

{{value[$index].label}} 
<div class=\"clearfix\"></div>
</div>',
                'type' => 'Text',
            ),
            array(
                'value' => '<hr ng-show=\"value[$index].show\"
style=\"margin:4px -12px 6px -4px;float:left;width:100%;padding:0px 4px;\" />',
                'type' => 'Text',
            ),
            array(
                'value' => '<div ng-if=\\"value[$index].show\\">',
                'type' => 'Text',
            ),
            array(
                'label' => 'Type',
                'name' => 'filterType',
                'options' => array(
                    'ng-model' => 'value[$index].filterType',
                    'ng-change' => 'updateListView()',
                ),
                'labelOptions' => array(
                    'style' => 'text-align:left;',
                ),
                'list' => array(
                    'string' => 'String',
                    'number' => 'Number',
                    'date' => 'Date',
                    'list' => 'DropDownList',
                    'check' => 'Checkbox',
                    'relation' => 'Relation',
                ),
                'labelWidth' => '3',
                'fieldWidth' => '9',
                'type' => 'DropDownList',
            ),
            array(
                'label' => 'Name',
                'name' => 'name',
                'labelWidth' => '3',
                'fieldWidth' => '9',
                'options' => array(
                    'ng-model' => 'value[$index].name',
                    'ng-change' => 'updateListView()',
                    'ng-delay' => '500',
                ),
                'labelOptions' => array(
                    'style' => 'text-align:left;',
                ),
                'fieldOptions' => array(
                    'class' => 'list-view-item-text',
                ),
                'type' => 'TextField',
            ),
            array(
                'label' => 'Label',
                'name' => 'label',
                'labelWidth' => '3',
                'fieldWidth' => '9',
                'options' => array(
                    'ng-model' => 'value[$index].label',
                    'ng-change' => 'updateListView()',
                    'ng-delay' => '500',
                ),
                'labelOptions' => array(
                    'style' => 'text-align:left;',
                ),
                'type' => 'TextField',
            ),
            array(
                'label' => 'Custom?',
                'name' => 'isCustom',
                'options' => array(
                    'ng-model' => 'value[$index].isCustom',
                    'ng-change' => 'updateListView()',
                ),
                'labelOptions' => array(
                    'style' => 'text-align:left;',
                ),
                'listExpr' => 'array(\\\'Yes\\\',\\\'No\\\');',
                'labelWidth' => '6',
                'fieldWidth' => '6',
                'type' => 'DropDownList',
            ),
            array(
                'value' => '<div style=\"margin-top:15px;\">
    <hr/>
    <div style=\"background:white;margin-top:-19px;color:#aaa;padding:5px 5px 5px 3px;width:50px;margin-left:-4px;\">Default</div>
</div>',
                'type' => 'Text',
            ),
            array(
                'label' => 'Operator',
                'name' => 'defaultOperator',
                'options' => array(
                    'ng-model' => 'value[$index].defaultOperator',
                    'ng-change' => 'updateListView()',
                    'ng-delay' => '500',
                    'ng-if' => 'item.filterType == \\\'number\\\'',
                ),
                'labelOptions' => array(
                    'style' => 'text-align:left;',
                ),
                'listExpr' => 'DataFilter::getFilterOperators(\\\'number\\\')',
                'labelWidth' => '3',
                'fieldWidth' => '9',
                'type' => 'DropDownList',
            ),
            array(
                'label' => 'Operator',
                'name' => 'defaultOperator',
                'options' => array(
                    'ng-model' => 'value[$index].defaultOperator',
                    'ng-change' => 'updateListView()',
                    'ng-delay' => '500',
                    'ng-if' => 'item.filterType == \\\'string\\\'',
                ),
                'labelOptions' => array(
                    'style' => 'text-align:left;',
                ),
                'listExpr' => 'DataFilter::getFilterOperators(\\\'string\\\')',
                'labelWidth' => '3',
                'fieldWidth' => '9',
                'type' => 'DropDownList',
            ),
            array(
                'label' => 'Operator',
                'name' => 'defaultOperator',
                'options' => array(
                    'ng-model' => 'value[$index].defaultOperator',
                    'ng-change' => 'updateListView()',
                    'ng-delay' => '500',
                    'ng-if' => 'item.filterType == \\\'date\\\'',
                ),
                'labelOptions' => array(
                    'style' => 'text-align:left;',
                ),
                'listExpr' => 'DataFilter::getFilterOperators(\\\'date\\\')',
                'labelWidth' => '3',
                'fieldWidth' => '9',
                'type' => 'DropDownList',
            ),
            array(
                'label' => 'Value',
                'name' => 'defaultValue',
                'labelWidth' => '3',
                'fieldWidth' => '9',
                'options' => array(
                    'ng-model' => 'value[$index].defaultValue',
                    'ng-change' => 'updateListView()',
                    'ng-delay' => '500',
                    'ng-if' => 'item.defaultOperator != \\\'Between\\\' && item.defaultOperator != \\\'Not Between\\\'',
                ),
                'labelOptions' => array(
                    'style' => 'text-align:left;',
                ),
                'type' => 'TextField',
            ),
            array(
                'label' => 'From',
                'name' => 'defaultValueFrom',
                'labelWidth' => '3',
                'fieldWidth' => '9',
                'options' => array(
                    'ng-model' => 'value[$index].defaultValueFrom',
                    'ng-change' => 'updateListView()',
                    'ng-delay' => '500',
                    'ng-if' => 'item.defaultOperator != \\\'\\\' && (item.filterType == \\\'date\\\' && (item.defaultOperator == \\\'Between\\\' || item.defaultOperator == \\\'Not Between\\\'))',
                ),
                'labelOptions' => array(
                    'style' => 'text-align:left;',
                ),
                'type' => 'TextField',
            ),
            array(
                'label' => 'To',
                'name' => 'defaultValueTo',
                'labelWidth' => '3',
                'fieldWidth' => '9',
                'options' => array(
                    'ng-model' => 'value[$index].defaultValueTo',
                    'ng-change' => 'updateListView()',
                    'ng-delay' => '500',
                    'ng-if' => 'item.defaultOperator != \\\'\\\' && (item.filterType == \\\'date\\\' && (item.defaultOperator == \\\'Between\\\' || item.defaultOperator == \\\'Not Between\\\'))',
                ),
                'labelOptions' => array(
                    'style' => 'text-align:left;',
                ),
                'type' => 'TextField',
            ),
            array(
                'label' => 'List Expression',
                'fieldname' => 'listExpr',
                'options' => array(
                    'ng-model' => 'value[$index].listExpr',
                    'ng-change' => 'updateListView()',
                    'ng-delay' => '500',
                    'ng-if' => 'value[$index].filterType ==\\\'list\\\' || value[$index].filterType == \\\'check\\\'',
                    'style' => 'margin-bottom:8px;',
                ),
                'type' => 'ExpressionField',
            ),
            array(
                'name' => 'TypeRelation',
                'subForm' => 'application.components.ui.FormFields.DataFilterListFormRelation',
                'options' => array(
                    'ng-if' => 'value[$index].filterType == \\\'relation\\\'',
                ),
                'inlineJS' => 'DataFilter/inlinejs/dfr-init.js',
                'type' => 'SubForm',
            ),
            array(
                'value' => '<div style=\\"margin-bottom:-3px;\\"></div>',
                'type' => 'Text',
            ),
            array(
                'value' => '</div>',
                'type' => 'Text',
            ),
        );
    }

    public function getForm() {
        return array(
            'formTitle' => 'DataFilterListForm',
            'layout' => array(
                'name' => 'full-width',
                'data' => array(
                    'col1' => array(
                        'type' => 'mainform',
                    ),
                ),
            ),
        );
    }

    public $name = '';
    public $label = '';
    public $listExpr = '';
    public $filterType = 'string';
    public $isCustom = 'No';
    public $defaultValue = '';
    public $defaultValueFrom = '';
    public $defaultValueTo = '';
    public $defaultOperator = '';
    
    public $typeOptions = array(
        'string' => ['defaultOperator', 'defaultValue'],
        'number' => ['defaultOperator', 'defaultValue'],
        'date' => ['defaultOperator', 'defaultValue', 'defaultValueFrom', 'defaultValueTo'],
        'list' => ['defaultValue', 'listExpr'],
        'check' => [ 'defaultValue', 'listExpr'],
        'relation' => ['defaultValue', 'relParams', 'relCriteria', 'relModelClass', 'relIdField', 'relLabelField'],
    );

    ### Relation ###
    public $relParams = array();
    public $relCriteria = array(
        'select' => '',
        'distinct' => 'false',
        'alias' => 't',
        'condition' => '{[search]}',
        'order' => '',
        'group' => '',
        'having' => '',
        'join' => ''
    );
    public $relModelClass = '';
    public $relIdField = '';
    public $relLabelField = '';

}
