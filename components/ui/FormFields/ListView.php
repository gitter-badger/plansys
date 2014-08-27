<?php

class ListView extends FormField
{
	/**
	 * @return array Fungsi ini akan me-return array property TextField.
	 */
    public function getFieldProperties() {
        return array (
            array (
                'label' => 'Field Name',
                'name' => 'name',
                'options' => array (
                    'ng-model' => 'active.name',
                    'ng-change' => 'changeActiveName()',
                    'ps-list' => 'modelFieldList',
                    'searchable' => 'size(modelFieldList) > 5',
                ),
                'list' => array (),
                'showOther' => 'Yes',
                'type' => 'DropDownList',
            ),
            array (
                'label' => 'Field Template',
                'name' => 'fieldTemplate',
                'options' => array (
                    'ng-model' => 'active.fieldTemplate',
                    'ng-change' => 'save();',
                ),
                'list' => array (
                    'default' => 'Default',
                ),
                'otherLabel' => 'Other...',
                'type' => 'DropDownList',
            ),
            array (
                'label' => 'Template Form',
                'name' => 'templateForm',
                'options' => array (
                    'ng-model' => 'active.templateForm',
                    'ng-show' => 'active.fieldTemplate == \'form\'',
                    'ng-change' => 'save();',
                ),
                'list' => array (
                    '' => '-- Empty --',
                    'application.modules.admin.forms.AdminFormLayoutProperties' => 'FormLayoutProperties',
                    'application.modules.admin.forms.AdminFormProperties' => 'FormProperties',
                    'application.modules.admin.forms.AdminMenuEditor' => 'MenuEditor',
                    'application.modules.admin.forms.AdminSettings' => 'Settings',
                    'z...' => '...',
                ),
                'listExpr' => 'FormBuilder::listForm(\\"admin\\")',
                'type' => 'DropDownList',
            ),
            array (
                'label' => 'Label',
                'name' => 'label',
                'options' => array (
                    'ng-model' => 'active.label',
                    'ng-change' => 'save()',
                    'ng-delay' => '500',
                ),
                'type' => 'TextField',
            ),
            array (
                'label' => 'Layout',
                'name' => 'layout',
                'options' => array (
                    'ng-model' => 'active.layout',
                    'ng-change' => 'save();',
                ),
                'list' => array (
                    'Horizontal' => 'Horizontal',
                    'Vertical' => 'Vertical',
                ),
                'listExpr' => 'array(\\\'Horizontal\\\',\\\'Vertical\\\')',
                'fieldWidth' => '6',
                'type' => 'DropDownList',
            ),
            array (
                'column1' => array (
                    array (
                        'label' => 'Label Width',
                        'name' => 'labelWidth',
                        'layout' => 'Vertical',
                        'labelWidth' => '12',
                        'fieldWidth' => '11',
                        'options' => array (
                            'ng-model' => 'active.labelWidth',
                            'ng-change' => 'save()',
                            'ng-delay' => '500',
                            'ng-disabled' => 'active.layout == \'Vertical\'',
                        ),
                        'type' => 'TextField',
                    ),
                    '<column-placeholder></column-placeholder>',
                ),
                'column2' => array (
                    array (
                        'label' => 'Field Width',
                        'name' => 'fieldWidth',
                        'layout' => 'Vertical',
                        'labelWidth' => 12,
                        'fieldWidth' => '11',
                        'options' => array (
                            'ng-model' => 'active.fieldWidth',
                            'ng-change' => 'save()',
                            'ng-delay' => '500',
                        ),
                        'type' => 'TextField',
                    ),
                    '<column-placeholder></column-placeholder>',
                ),
                'column3' => array (
                    '<column-placeholder></column-placeholder>',
                ),
                'column4' => array (
                    '<column-placeholder></column-placeholder>',
                ),
                'type' => 'ColumnField',
            ),
            '<hr/>',
            array (
                'label' => 'Options',
                'fieldname' => 'options',
                'type' => 'KeyValueGrid',
            ),
            array (
                'label' => 'Label Options',
                'fieldname' => 'labelOptions',
                'type' => 'KeyValueGrid',
            ),
            array (
                'label' => 'Field Options',
                'fieldname' => 'fieldOptions',
                'type' => 'KeyValueGrid',
            ),
        );
    }

	/** @var string variable untuk menampung label */
    public $label = '';
	
	/** @var string variable untuk menampung name */
    public $name = '';
	
	/** @var string variable untuk menampung tipe field dengan default text */
    public $fieldTemplate = 'default';
	
    public $templateForm = '';
    
	/** @var string variable untuk menampung value */
    public $value = '';
	
	/** @var string variable ntuk menampung kondisi layout dengan default Horizontal */
    public $layout = 'Horizontal';
    
    public $layoutVertical = '';
	
	/** @var integer variable untuk menampung nilai width label */
    public $labelWidth = 4;
	
	/** @var integer variable untuk menampung nilai witdth field */
    public $fieldWidth = 8;
	
	/** @var array variable untuk menampung array options */
    public $options = array();
	
	/** @var array variable untuk menampung array options label */
    public $labelOptions = array();
	
	/** @var array variable untuk menampung array options field */
    public $fieldOptions = array();
	
	/** @var string variable untuk menampung toolbarName */
    public static $toolbarName = "List View";
	
	/** @var string variable untuk menampung category */
    public static $category = "Data & Tables";
	
	/** @var string variable untuk menampung toolbarIcon */
    public static $toolbarIcon = "glyphicon glyphicon-align-justify";
    
	/**
	 * @return array Fungsi ini akan me-return array javascript yang di-include. Defaultnya akan meng-include.
	*/
    public function includeJS()
    {
        return array('list-view.js');
    }

	/**
	 * @return string Fungsi ini akan me-return string class layout yang digunakan. Fungsi ini akan mengecek nilai property $layout untuk menentukan nama Class Layout.
	*/
    public function getLayoutClass()
    {
        return ($this->layout == 'Vertical' ? 'form-vertical' : '');
    }

	/**
	 * @return string Fungsi ini akan me-return string class error jika terdapat error pada satu atau banyak attribute.
	*/
    public function getErrorClass()
    {
        return (count($this->errors) > 0 ? 'has-error has-feedback' : '');
    }

	/**
	 * @return string Fungsi ini akan me-return string class label. Fungsi akan mengecek $layout untuk menentukan layout yang digunakan. Fungsi juga me-load option label dari property $labelOptions. 
	 */
    public function getlabelClass()
    {
        if ($this->layout == 'Vertical') {
            $class = "control-label col-sm-12";
        } else {
            $class = "control-label col-sm-{$this->labelWidth}";
        }

        $class .= @$this->labelOptions['class'];
        return $class;
    }

	/**
	 * @return integer Fungsi ini akan me-return string class untuk menentukan width fields.
	 */	
    public function getFieldColClass()
    {
        return "col-sm-" . $this->fieldWidth;
    }

	/**
	 * @return field Fungsi ini untuk me-render field dan atributnya.
	 */	
    public function render()
    {
        $this->addClass('form-group form-group-sm', 'options');
        $this->addClass($this->layoutClass, 'options');
        $this->addClass($this->errorClass, 'options');

        $this->fieldOptions['id'] = $this->name;
        $this->fieldOptions['name'] = $this->name;
        $this->addClass('form-control', 'fieldOptions');

        $this->setDefaultOption('ng-model', "model.{$this->originalName}", $this->options);
        return $this->renderInternal('template_render.php');
    }
}