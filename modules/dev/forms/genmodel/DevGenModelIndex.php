<?php

class DevGenModelIndex extends Form {
    ## MODEL INFO VARS

    public $name;
    public $alias;
    public $path;
    public $classPath;
    public $rules;
    public $relations;

    ## GENERATOR VARS
    public $generator;
    public $mode = 'Normal';
    public $imports = '';
    public $error = '';
    public $synced = '';

    public function getForm() {
        return array(
            'title'    => 'Generate Model',
            'layout'   => array(
                'name' => '2-cols',
                'data' => array(
                    'col1' => array(
                        'size'     => '200',
                        'sizetype' => 'px',
                        'type'     => 'menu',
                        'name'     => 'col1',
                        'file'     => 'application.modules.dev.menus.GenModel',
                        'title'    => 'Model',
                        'icon'     => 'fa-cube',
                        'inlineJS' => 'GenModel.js',
                    ),
                    'col2' => array(
                        'size'     => '',
                        'sizetype' => '',
                        'type'     => 'mainform',
                    ),
                ),
            ),
            'inlineJS' => 'editModel.js',
        );
    }

    public function getFields() {
        return array (
            array (
                'type' => 'Text',
                'value' => '<!-- EMPTY MODULE -->
<div ng-if=\'!model.name\'>
    <div class=\"empty-box-container\">
        <div class=\"message\">
            Please select item on right sidebar
        </div>
    </div>
</div>',
            ),
            array (
                'type' => 'Text',
                'value' => '
<tabset class=\'tab-set\' ng-if=\'model.name\'>
<tab active=\"true\">
    <tab-heading>
        <i class=\"fa fa-cube\"></i>
        Model {{ model.name | ucfirst }}
    </tab-heading>
    <div style=\'padding:0px 0px;\'>
        ',
            ),
            array (
                'name' => 'mode',
                'labelWidth' => '0',
                'fieldWidth' => '0',
                'onLabel' => 'Normal',
                'offLabel' => 'Custom',
                'options' => array (
                    'style' => 'float:right;
margin:-25px 0px 0px 0px;',
                ),
                'size' => 'small',
                'type' => 'ToggleSwitch',
            ),
            array (
                'showBorder' => 'Yes',
                'column1' => array (
                    array (
                        'label' => 'Model Name',
                        'name' => 'name',
                        'type' => 'LabelField',
                    ),
                    array (
                        'label' => 'Table Name',
                        'js' => 'model.generator.tableName',
                        'type' => 'LabelField',
                    ),
                    array (
                        'label' => 'Rename Table',
                        'icon' => 'pencil',
                        'position' => 'right',
                        'buttonSize' => 'btn-xs',
                        'type' => 'LinkButton',
                    ),
                    array (
                        'label' => 'Alter Table',
                        'icon' => 'sign-in',
                        'position' => 'right',
                        'buttonSize' => 'btn-xs',
                        'options' => array (
                            'href' => 'url:{params.alterurl}',
                            'target' => '_blank',
                        ),
                        'type' => 'LinkButton',
                    ),
                    array (
                        'label' => 'Select Table',
                        'icon' => 'sign-in',
                        'position' => 'right',
                        'buttonSize' => 'btn-xs',
                        'options' => array (
                            'href' => 'url:{params.selecturl}',
                            'target' => '_blank',
                        ),
                        'type' => 'LinkButton',
                    ),
                    array (
                        'type' => 'Text',
                        'value' => '<column-placeholder></column-placeholder>',
                    ),
                ),
                'column2' => array (
                    array (
                        'type' => 'Text',
                        'value' => '<column-placeholder></column-placeholder>',
                    ),
                    array (
                        'label' => 'Model Alias',
                        'name' => 'alias',
                        'type' => 'LabelField',
                    ),
                    array (
                        'label' => 'Extends From',
                        'js' => 'model.generator.extendsFrom',
                        'type' => 'LabelField',
                    ),
                ),
                'w1' => '50%',
                'w2' => '50%',
                'type' => 'ColumnField',
            ),
            array (
                'showBorder' => 'Yes',
                'column1' => array (
                    array (
                        'type' => 'Text',
                        'value' => '<column-placeholder></column-placeholder>',
                    ),
                    array (
                        'title' => '<i class=\\"fa fa-shield\\"></i> Validation Rules',
                        'type' => 'SectionHeader',
                    ),
                    array (
                        'name' => 'rules',
                        'fieldTemplate' => 'form',
                        'templateForm' => 'application.modules.dev.forms.genmodel.DevGenModelRules',
                        'labelWidth' => '0',
                        'fieldWidth' => '12',
                        'options' => array (
                            'style' => 'margin:-10px -45px 0px -35px;',
                        ),
                        'singleViewOption' => array (
                            'name' => 'val',
                            'fieldType' => 'text',
                            'labelWidth' => 0,
                            'fieldWidth' => 12,
                            'fieldOptions' => array (
                                'ng-delay' => 500,
                            ),
                        ),
                        'type' => 'ListView',
                    ),
                ),
                'w1' => '50%',
                'w2' => '50%',
                'type' => 'ColumnField',
            ),
            array (
                'type' => 'Text',
                'value' => '    </div>
</tab>
</tabset>',
            ),
        );
    }

}