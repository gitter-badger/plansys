<?php

class ActiveRecordTemplate extends CComponent {

    public static function generateFields($model) {
        $fieldList     = $model->modelFieldList;
        $classPart     = explode("-", Helper::camelToSnake(get_class($model)));
        $lastPartIndex = count($classPart) - 1;
        $params        = [
            'array' => $fieldList,
            'tableColumns' => $model->tableSchema->columns,
            'primaryKey' => $model->tableSchema->primaryKey,
            'classPart' => $classPart,
            'fieldList' => $fieldList,
            'length' => count($fieldList),
            'model' => $model,
        ];
        if (!empty($classPart) && (in_array($classPart[$lastPartIndex], ["index", "form", "master", "relform"]))) {
            $params['type'] = array_pop($classPart);
        } else {
            $params['type'] = "";
        }

        $params['module']     = array_shift($classPart);
        $params['basicTitle'] = Helper::camelToSpacedCamel(implode("", array_map('ucfirst', $classPart)));
        $params['basic']      = lcfirst(implode("", array_map('ucfirst', $classPart)));

        $type   = $params['type'];
        $return = [];

        switch ($type) {
            case "index":
                self::generateIndex($return, $params);
                break;
            case "relform":
                if (isset($_SESSION['CrudGenerator'][get_class($model)]['relation']['type'])) {
                    self::generateRelform($return, $params);
                }
                break;
            case "form":
                self::generateForm($return, $params);
                break;
            case "master":
                self::generateMaster($return, $params);
                break;
        }

        return $return;
    }

    private static function generateIndex(&$return, $params) {
        $fieldList     = [];
        $tableColumns  = [];
        $model         = null;
        $basicTitle    = "";
        $module        = '';
        $basic         = '';
        $filterColumns = [];
        $gridColumns   = [];
        $primaryKey    = '';
        extract($params);

        $generatorParams = [];
        if (isset($_SESSION['CrudGenerator']) && isset($_SESSION['CrudGenerator'][get_class($model)])) {
            $generatorParams = $_SESSION['CrudGenerator'][get_class($model)];
            unset($_SESSION['CrudGenerator'][get_class($model)]);
        }

        $prefixUrl = "{$basic}";
        if ($module != '' && $module != 'app' && $module != 'application') {
            $prefixUrl = "{$module}/" . $prefixUrl;
        }
        $linkBar = [
            [
                'label' => 'Tambah ' . $basicTitle,
                'buttonType' => 'success',
                'icon' => 'plus',
                'options' => [
                    'href' => "url:/{$prefixUrl}/new",
                ],
                'type' => 'LinkButton',
            ],
        ];
        if (@$generatorParams['bulkCheckbox'] == 'Yes') {
            array_unshift($linkBar,
                [
                    'label' => 'Delete Item(s)',
                    'buttonType' => 'danger',
                    'icon' => 'trash',
                    'options' => array(
                        'href' => 'url:' . $prefixUrl . '/delete&id={ gridView1.checkboxValues(\'chk\',\'' . $primaryKey . '\') }',
                        'ng-if' => 'gridView1.checkbox.chk.length > 0',
                    ),
                    'type' => 'LinkButton',
                ]);
        }
        $return[] = [
            'linkBar' => $linkBar,
            'title' => 'Daftar ' . $basicTitle,
            'showSectionTab' => 'No',
            'type' => 'ActionBar',
        ];

        ## generate Filters & Columns
        $gv = new GridView;
        foreach ($fieldList as $k => $i) {
            if ($fieldList[$k]['name'] == $primaryKey) {
                continue;
            }
            $i['label'] = implode(" ", array_map('ucfirst', explode("_", $i['label'])));
            $filter     = [
                'filterType' => "string",
                'name' => $i['name'],
                'label' => $i['label']
            ];
            $filter     = self::processFilterColumn([
                'filter' => $filter,
                'model' => $model,
                'field' => $i,
                'fieldIndex' => $k,
                'tableColumns' => $tableColumns
            ]);
            $column     = [
                'columnType' => "string",
                'options' => [],
                'name' => $i['name'],
                'label' => $i['label']
            ];

            $filterColumns[] = $filter;
            $gridColumns[]   = $column;
            $gv->columns[]   = $column;
        }

        $editUrl               = "{$prefixUrl}/update";
        $editButtonCol         = [
            'name' => '',
            'label' => '',
            'columnType' => "string",
            'options' => [
                'mode' => 'edit-button',
                'editUrl' => "{$editUrl}&id={{row.{$primaryKey}}}"
            ]
        ];
        $gv->columns[]         = $editButtonCol;
        array_push($gridColumns, $editButtonCol);

        if (@$generatorParams['bulkCheckbox'] == 'Yes') {
            $checkboxCol   = [
                'name' => 'chk',
                'label' => '',
                'options' => array(
                    'modifyDataSource' => 'false',
                ),
                'mergeSameRow' => '',
                'mergeSameRowWith' => '',
                'html' => '',
                'columnType' => 'checkbox',
                'show' => false,
                'checkedValue' => 'checked',
            ];
            $gv->columns[] = $checkboxCol;
            array_push($gridColumns, $checkboxCol);
        } else {
            $delUrl               = "{$prefixUrl}/delete";
            $delButtonCol         = [
                'name' => '',
                'label' => '',
                'columnType' => "string",
                'options' => [
                    'mode' => 'del-button',
                    'delUrl' => "{$delUrl}&id={{row.{$primaryKey}}}"
                ]
            ];
            $gv->columns[]        = $delButtonCol;
            array_push($gridColumns, $delButtonCol);
        }

        $return[] = [
            'name' => 'dataFilter1',
            'datasource' => 'dataSource1',
            'type' => 'DataFilter',
            'filters' => $filterColumns
        ];

        $return[] = [
            'name' => 'dataSource1',
            'relationTo' => 'currentModel',
            'relationCriteria' => array(
                'select' => '',
                'distinct' => 'false',
                'alias' => 't',
                'condition' => self::criteriaCondition($model),
                'order' => '{[order]}',
                'paging' => '{[paging]}',
                'group' => '',
                'having' => '',
                'join' => '',
            ),
            'type' => 'DataSource',
        ];
        $return[] = [
            'name' => 'gridView1',
            'datasource' => 'dataSource1',
            'type' => 'GridView',
            'columns' => $gridColumns
        ];
        return $return;
    }

    private static function generateRelform(&$return, $params) {
        $model = null;
        extract($params);

        $generatorParams = [];
        if (isset($_SESSION['CrudGenerator']) && isset($_SESSION['CrudGenerator'][get_class($model)])) {
            $generatorParams = $_SESSION['CrudGenerator'][get_class($model)];
        }
        $params['generatorParams'] = $generatorParams;
        switch ($generatorParams['relation']['type']) {
            case "CBelongsToRelation":
                self::generateRelBelongsToForm($return, $params);
                break;
            case "CManyManyRelation":
                self::generateRelManyManyIndex($return, $params);
                break;
        }

        return $return;
    }

    private static function generateRelManyManyIndex(&$return, $params) {
        $fieldList       = [];
        $primaryKey      = '';
        $tableColumns    = [];
        $length          = 0;
        $model           = null;
        $basicTitle      = "";
        $module          = '';
        $basic           = '';
        $generatorParams = '';
        extract($params);

        $relClass      = $generatorParams['relation']['className'];
        $model         = $relClass::model();
        $primaryKey    = $model->tableSchema->primaryKey;
        $filterColumns = [];
        $gridColumns   = [];

        $relClassSpaced = Helper::camelToSpacedCamel($relClass);

        ## generate ActionBar
        $return[] = [
            'linkBar' => [
                [
                    'renderInEditor' => 'Yes',
                    'type' => 'Text',
                    'value' => '<div
ng-click="choose()"
class="btn btn-sm btn-success"
ng-disabled="!gridView1.checkbox.chk
|| gridView1.checkbox.chk.length == 0">
   <i class="fa fa-check"></i>
   <b>
       Pilih
       <span ng-if="gridView1.checkbox.chk.length > 0">
           {{gridView1.checkbox.chk.length}}
       </span>
       ' . $relClassSpaced . '
   </b>
</div>
',
                ],
            ],
            'title' => 'Daftar ' . ucfirst($generatorParams['relation']['name']),
            'showSectionTab' => 'No',
            'type' => 'ActionBar',
        ];

        ## generate Filters & Columns
        $gv = new GridView;
        foreach ($fieldList as $k => $i) {
            if ($fieldList[$k]['name'] == $primaryKey) {
                continue;
            }
            $i['label'] = implode(" ", array_map('ucfirst', explode("_", $i['label'])));
            $filter     = [
                'filterType' => "string",
                'name' => $i['name'],
                'label' => $i['label']
            ];
            $filter     = self::processFilterColumn([
                'filter' => $filter,
                'model' => $model,
                'field' => $i,
                'fieldIndex' => $k,
                'tableColumns' => $tableColumns
            ]);
            $column     = [
                'columnType' => "string",
                'options' => [],
                'name' => $i['name'],
                'label' => $i['label']
            ];

            $filterColumns[] = $filter;
            $gridColumns[]   = $column;
            $gv->columns[]   = $column;
        }

        $checkboxCol   = [
            'name' => 'chk',
            'label' => '',
            'options' => array(
                'modifyDataSource' => 'false',
            ),
            'mergeSameRow' => '',
            'mergeSameRowWith' => '',
            'html' => '',
            'columnType' => 'checkbox',
            'show' => false,
            'checkedValue' => 'checked',
        ];
        $gv->columns[] = $checkboxCol;
        array_push($gridColumns, $checkboxCol);
        $return[] = [
            'name' => 'dataFilter1',
            'datasource' => 'dataSource1',
            'type' => 'DataFilter',
            'filters' => $filterColumns
        ];

        $condition = self::criteriaCondition($model);
        $params    = [];
        if (@$generatorParams['relation']['uniqueEntry'] == 'Yes') {
            $token   = token_get_all("<?php " . str_replace(" ", "", $generatorParams['relation']['foreignKey']));
            $mmTable = $token[1][1];
            $mmFrom  = $token[3][1];
            $mmTo    = $token[5][1];

            if ($condition != "{[where]}") {
                $condition = "{{$primaryKey} not in (select {$mmTo} from {$mmTable} where {$mmFrom} = :id) AND} {{$primaryKey} not in (:jsid)} {AND} " . $condition;
            } else {
                $condition = "{{$primaryKey} not in (select {$mmTo} from {$mmTable} where {$mmFrom} = :id) AND} {{$primaryKey} not in (:jsid)} {AND [where]} ";
            }

            $params[':id']   = 'php: @$_GET["id"]';
            $params[':jsid'] = 'js: excludeID()';
        }

        $return[] = [
            'name' => 'dataSource1',
            'relationTo' => 'currentModel',
            'relationCriteria' => array(
                'select' => '',
                'distinct' => 'false',
                'alias' => 't',
                'condition' => $condition,
                'order' => '{[order]}',
                'paging' => '{[paging]}',
                'group' => '',
                'having' => '',
                'join' => '',
            ),
            'params' => $params,
            'type' => 'DataSource',
        ];
        $return[] = [
            'name' => 'gridView1',
            'datasource' => 'dataSource1',
            'type' => 'GridView',
            'columns' => $gridColumns
        ];
    }

    private static function generateRelBelongsToForm(&$return, $params) {
        $fieldList       = [];
        $primaryKey      = '';
        $tableColumns    = [];
        $length          = 0;
        $model           = null;
        $basicTitle      = "";
        $module          = '';
        $basic           = '';
        $generatorParams = '';
        extract($params);

        ## get basic model name
        $basic     = substr($basic, 0, strlen($generatorParams['relation']['name']) * -1);
        $prefixUrl = "{$basic}";
        if ($module != '' && $module != 'app' && $module != 'application') {
            $prefixUrl = "{$module}/" . $prefixUrl;
        }

        $column1      = [];
        $column2      = [];
        $fieldList_id = null;
        foreach ($fieldList as $k => $i) {
            if (isset($fieldList[$k]['name'])) {
                if ($fieldList[$k]['name'] == $primaryKey) {
                    $fieldList_id = $fieldList[$k];
                    continue;
                }
                if (isset($fieldList[$k]['label'])) {
                    $fieldList[$k]['label'] = ucfirst(implode(" ", array_map('ucfirst', explode("_", $fieldList[$k]['label']))));
                }

                $fieldList[$k] = self::processGridColumn([
                    'model' => $model,
                    'field' => $i,
                    'fieldIndex' => $k,
                    'tableColumns' => $tableColumns,
                    'generatorParams' => $generatorParams,
                    'prefixUrl' => $prefixUrl
                ]);
            }

            if ($k < $length / 2) {
                $column1[] = $fieldList[$k];
            } else {
                $column2[] = $fieldList[$k];
            }
        }

        $column1[] = '<column-placeholder></column-placeholder>';
        $column2[] = '<column-placeholder></column-placeholder>';
        $return[]  = [
            'type' => 'Text',
            'value' => '<div ng-show="!params.posted">',
        ];

        $linkBar = [
            [
                'label' => 'Simpan',
                'buttonType' => 'success',
                'icon' => 'check',
                'options' => [
                    'ng-click' => "form.submit(this)",
                ],
                'type' => 'LinkButton',
            ],
        ];

        if ($generatorParams['relation']['deleteable'] == 'Yes') {
            $linkBar[] = [
                'type' => 'Text',
                'value' => '<div ng-if="!isNewRecord" class="separator"></div>',
                'renderInEditor' => 'Yes',
            ];
            $linkBar[] = [
                'label' => 'Hapus',
                'buttonType' => 'danger',
                'icon' => 'trash',
                'options' => [
                    'ng-if' => '!isNewRecord',
                    'href' => 'url:/' . $prefixUrl . '/delete' . ucfirst($generatorParams['relation']['name']) . '&id={params.id}',
                    'confirm' => 'Apakah Anda Yakin ?'
                ],
                'type' => 'LinkButton',
            ];
        }

        $return[] = [
            'linkBar' => $linkBar,
            'title' => '{{ isNewRecord ? \'Tambah ' . $basicTitle . '\' : \'Update ' . $basicTitle . '\'}}',
            'showSectionTab' => 'No',
            'type' => 'ActionBar',
        ];

        if (!is_null($fieldList_id)) {
            $return[] = $fieldList_id;
        }
        $return[] = [
            'type' => 'ColumnField',
            'column1' => $column1,
            'column2' => $column2
        ];
        $return[] = [
            'type' => 'Text',
            'value' => '</div>
<div ng-if="!!params.posted">
    <br>
    <br>
    <br>
    <div ng-if=\'!!params.inserted\'>
        <div ng-init=\'
        parentWindow.' . $generatorParams['relation']['foreignKey'] . '.updateInternal(model.' . $primaryKey . ');
        \'></div>
    </div>
    <div ng-if=\'!!params.deleted\'>
        <div ng-init=\'
        parentWindow.' . $generatorParams['relation']['foreignKey'] . '.unselect();
        \'></div>
    </div>
    <div style=\'text-align:center;\' ng-init=\'
    closeWindow();
    parentWindow.' . $generatorParams['relation']['foreignKey'] . '.reload();
    \'>Loading ...</div>
</div>'
        ];
    }

    private static function generateMaster(&$return, $params) {
        $fieldList  = [];
        $primaryKey = '';
        $cols       = [];
        $length     = 0;
        $basicTitle = "";
        $model      = null;
        $module     = '';
        $basic      = '';
        extract($params);

        $return[] = [
            'linkBar' => [
                [
                    'label' => 'Simpan ' . $basicTitle,
                    'buttonType' => 'success',
                    'icon' => 'check',
                    'options' => [
                        'ng-click' => "form.submit(this)",
                    ],
                    'type' => 'LinkButton',
                ],
            ],
            'title' => '{{ form.title }}',
            'showSectionTab' => 'No',
            'type' => 'ActionBar',
        ];

        $gv = new GridView;

        ## generate Filters & Columns
        $pkCol = null;
        foreach ($fieldList as $k => $i) {
            if ($i['name'] == $primaryKey) {
                $i['label'] = $i['name'];
            }

            ## setup label
            $i['label'] = implode(" ", array_map('ucfirst', explode("_", $i['label'])));

            ## setup filters
            $filter          = [
                'filterType' => "string"
            ];
            $filter['name']  = $i['name'];
            $filter['label'] = $i['label'];
            $filters[]       = $filter;

            ## setup columns
            $column          = [
                'columnType' => "string",
                'options' => []
            ];
            $column['name']  = $i['name'];
            $column['label'] = $i['label'];

            if ($i['name'] != $primaryKey) {
                $gv->columns[]             = $column;
                $column['cellMode']        = 'custom';
                $column['options']['mode'] = 'editable';
                $cols[]                    = $column;
            } else {
                $pkCol = $column;
            }
        }
        array_unshift($cols, $pkCol);

        $delButtonCol         = [
            'name' => '',
            'label' => '',
            'columnType' => "string",
            'cellMode' => 'custom',
            'options' => [
                'mode' => 'del-button'
            ]
        ];
        $gv->columns[]        = $delButtonCol;
        array_push($cols, $delButtonCol);

        $return[] = [
            'name' => 'dataFilter1',
            'datasource' => 'dataSource1',
            'type' => 'DataFilter',
            'filters' => $filters
        ];

        if (!!$model->_softDelete) {
            $condition = $model->_softDelete['column'] . ' <> \'' . $model->_softDelete['value'] . '\' {AND [where]}';
        } else {
            $condition = '{[where]}';
        }

        $return[] = [
            'name' => 'dataSource1',
            'params' => [
                'where' => 'dataFilter1',
                'paging' => 'gridView1',
                'order' => 'gridView1',
            ],
            'relationTo' => 'currentModel',
            'relationCriteria' => array(
                'select' => '',
                'distinct' => 'false',
                'alias' => 't',
                'condition' => $condition,
                'order' => '{[order]}',
                'paging' => '{[paging]}',
                'group' => '',
                'having' => '',
                'join' => '',
            ),
            'type' => 'DataSource',
        ];
        $return[] = [
            'name' => 'gridView1',
            'datasource' => 'dataSource1',
            'type' => 'GridView',
            'columns' => $cols
        ];
        $return[] = [
            'renderInEditor' => 'Yes',
            'type' => 'Text',
            'value' => '<div style="margin-top:5px;text-align:center;\">
    <div ng-click="gridView1.addRow()"
    class="btn btn-sm btn-success"><i class="fa fa-plus"></i> New Record</div>
</div>',
        ];
        return $return;
    }

    private static function generateForm(&$return, $params) {
        $fieldList    = [];
        $primaryKey   = '';
        $tableColumns = [];
        $length       = 0;
        $model        = null;
        $basicTitle   = "";
        $module       = '';
        $basic        = '';
        extract($params);
        $modelClassName = get_class($model);

        $generatorParams = [];
        if (isset($_SESSION['CrudGenerator']) && isset($_SESSION['CrudGenerator'][$modelClassName])) {
            $generatorParams = $_SESSION['CrudGenerator'][$modelClassName];
            unset($_SESSION['CrudGenerator'][$modelClassName]);
        }

//        $generatorParams = json_decode('{"name":"AppCountryForm.php","className":"AppCountryForm","extendsName":"Country","type":"form","relations":[{"name":"cities","tableName":"city","type":"CHasManyRelation","foreignKey":"country_id","className":"City","formType":"Table","editable":"No","insertable":"No","chooseable":"No","deleteable":"Yes"}],"status":"processing","path":"app.forms.country"}', true);

//        $generatorParams = json_decode('{"name":"AppCityForm.php","className":"AppCityForm","extendsName":"City","type":"form","relations":[{"name":"addresses","tableName":"address","type":"CHasManyRelation","foreignKey":"city_id","className":"Address","formType":"Table","editable":"Inline","insertable":"Inline","deleteable":"Multi Delete"}],"status":"processing","overwrite":true,"path":"app.forms.city"}', true);

//        $generatorParams = json_decode('{"name":"AppCityForm.php","className":"AppCityForm","extendsName":"City","type":"form","relations":[{"name":"country","tableName":"country","type":"CBelongsToRelation","foreignKey":"country_id","className":"Country","formType":"PopUp","deleteable":"Yes","insertable":"Yes"}],"status":"processing","overwrite":true,"path":"app.forms.city"}', true);

        $prefixUrl = "{$basic}";
        if ($module != '' && $module != 'app' && $module != 'application') {
            $prefixUrl = "{$module}/" . $prefixUrl;
        }

        $column1      = [];
        $column2      = [];
        $fieldList_id = null;
        $genBelongsTo = [];

        foreach ($fieldList as $k => $i) {
            if (isset($fieldList[$k]['name']) && isset($tableColumns[$fieldList[$k]['name']])) {
                if ($fieldList[$k]['name'] == $primaryKey) {
                    $fieldList_id = $fieldList[$k];
                    continue;
                }
                if (isset($fieldList[$k]['label'])) {
                    $fieldList[$k]['label'] = implode(" ", array_map('ucfirst', explode("_", $i['label'])));
                }

                $fieldList[$k] = self::processGridColumn([
                    'model' => $model,
                    'field' => $i,
                    'fieldIndex' => $k,
                    'tableColumns' => $tableColumns,
                    'generatorParams' => $generatorParams,
                    'prefixUrl' => $prefixUrl
                ], $genBelongsTo);
            }

            if ($k < $length / 2) {
                $column1[] = $fieldList[$k];
            } else {
                $column2[] = $fieldList[$k];
            }
        }

        foreach ($genBelongsTo as $i => $gen) {
            $colNum = $gen['index'] >= floor(count($fieldList) / 2) ? 2 : 1;
            $idx    = $gen['index'] >= floor(count($fieldList) / 2) ? $gen['index'] - ceil(count($fieldList) / 2) : $gen['index'];
            $offset = ($i * count($gen['data'])) + 1;
            array_splice(${'column' . $colNum}, $idx + $offset, 0, $gen['data']);
        }

        $column1[] = '<column-placeholder></column-placeholder>';
        $column2[] = '<column-placeholder></column-placeholder>';

        $return[] = [
            'linkBar' => [
                [
                    'label' => 'Kembali',
                    'buttonType' => 'default',
                    'icon' => 'chevron-left',
                    'options' => [
                        'href' => "url:/{$prefixUrl}/index",
                    ],
                    'type' => 'LinkButton',
                ],
                [
                    'label' => 'Simpan',
                    'buttonType' => 'success',
                    'icon' => 'check',
                    'options' => [
                        'ng-click' => "form.submit(this)",
                    ],
                    'type' => 'LinkButton',
                ],
                [
                    'type' => 'Text',
                    'value' => '<div ng-if="!isNewRecord" class="separator"></div>',
                    'renderInEditor' => 'Yes',
                ],
                [
                    'label' => 'Hapus',
                    'buttonType' => 'danger',
                    'icon' => 'trash',
                    'options' => [
                        'ng-if' => '!isNewRecord',
                        'href' => "url:/{$prefixUrl}/delete?id={model.{$primaryKey}}",
                        'confirm' => 'Apakah Anda Yakin ?'
                    ],
                    'type' => 'LinkButton',
                ],
            ],
            'title' => '{{ isNewRecord ? \'Tambah ' . $basicTitle . '\' : \'Update ' . $basicTitle . '\'}}',
            'showSectionTab' => 'Yes',
            'type' => 'ActionBar',
        ];

        if (!is_null($fieldList_id)) {
            $return[] = $fieldList_id;
        }

        $return[] = [
            'type' => 'ColumnField',
            'column1' => $column1,
            'column2' => $column2
        ];

        if (!!@$generatorParams['relations']) {
            foreach ($generatorParams['relations'] as $rel) {
                if (!isset($rel['name'])) continue;
                if (!@is_subclass_of($rel['className'], 'ActiveRecord')) {
                    self::appendGenMsg('<br/>&bull; Failed to create Relation <b>' . $rel['name'] . '</b>,
                                      <br/> &nbsp; Model Class <b>' . $rel['className'] . '</b> is not available', $model);
                    continue;
                }
                $relName = ucfirst($rel['name']);
                if ($rel['type'] == 'CHasManyRelation' || $rel['type'] == 'CManyManyRelation') {
                    $return[] = [
                        'title' => $relName,
                        'type' => 'SectionHeader',
                    ];

                    if ($rel['type'] == "CManyManyRelation") {
                        if ($rel['chooseable'] == "Yes") {
                            self::appendChooseButton($return, [
                                'primaryKey' => $primaryKey,
                                'prefixUrl' => $prefixUrl,
                                'relName' => $relName,
                                'rel' => $rel
                            ]);
                        }
                    } else if ($rel['type'] == "CHasManyRelation") {
                        if ($rel['insertable'] != "No") {
                            self::appendInsertButton($return, [
                                'primaryKey' => $primaryKey,
                                'prefixUrl' => $prefixUrl,
                                'relName' => $relName,
                                'rel' => $rel
                            ]);
                        }
                    }

                    switch ($rel['formType']) {
                        case "Table":
                            self::insertRelTable($rel, $return);
                            break;
                        case "SubForm":
                            self::insertRelSubForm($rel, $return);
                            break;
                    }
                }
            }
        }

        return $return;
    }

    private static function getRelClassName($model, $fieldName) {
        $relClassName = substr($fieldName, 0, strlen($fieldName) - 3);
        $relClassName = implode("", array_map('ucfirst', explode("_", $relClassName)));

        $rels = $model::model()->metaData->relations;
        foreach ($rels as $k => $r) {
            if (get_class($r) == "CBelongsToRelation") {
                if ($fieldName == $r->foreignKey) {
                    $relClassName = $r->className;
                }
            }
        }
        return $relClassName;
    }

    private static function appendInsertButton(&$return, $params) {
        $relName    = '';
        $prefixUrl  = '';
        $primaryKey = '';
        $rel        = [];
        extract($params);

        if ($rel['insertable'] == "PopUp") {
            $return[] = [
                'type' => 'Text',
                'value' => '<div
    ng-click="rel' . $relName . 'ChoosePopup.open();"
    style="float:right;margin-top:-25px;"
    class="btn btn-xs btn-success">
    <i class="fa fa-plus"></i>
    <b>Tambah ' . ucfirst(Helper::camelToSnake($rel['className'])) . '</b>
</div>'];
        } else if ($rel['insertable'] == "Inline") {
            $return[] = [
                'type' => 'Text',
                'value' => '<div
    ng-click="gv' . $relName . '.addRow();"
    style="float:right;margin-top:-25px;"
    class="btn btn-xs btn-success">
    <i class="fa fa-plus"></i>
    <b>Tambah ' . ucfirst(Helper::camelToSnake($rel['className'])) . '</b>
</div>'];
        }

    }

    private static function appendChooseButton(&$return, $params) {
        $relName    = '';
        $prefixUrl  = '';
        $primaryKey = '';
        $rel        = [];
        extract($params);
        $return[] = [
            'type' => 'Text',
            'value' => '<div
    ng-click="rel' . $relName . 'ChoosePopup.open();"
    style="float:right;margin-top:-25px;"
    class="btn btn-xs btn-success">
    <i class="fa fa-plus"></i>
    <b>Pilih ' . ucfirst(Helper::camelToSnake($rel['className'])) . '</b>
</div>'];

        $return[] = [
            'type' => 'PopupWindow',
            'name' => 'rel' . $relName . 'ChoosePopup',
            'options' => array(
                'height' => '500',
                'width' => '700',
            ),
            'mode' => 'url',
            'url' => $prefixUrl . '/choose' . $relName . '{{ "&id=" + model.' . $primaryKey . ' || ""}}',
        ];
    }

    private static function insertRelTable($rel, &$return) {
        $gv            = new GridView;
        $model         = $rel['className']::model();
        $gridColumns   = [];
        $filterColumns = [];
        $relName       = ucfirst($rel['name']);
        $fieldList     = $model->modelFieldList;
        $primaryKey    = $model->tableSchema->primaryKey;
        $tableColumns  = $model->tableSchema->columns;

        foreach ($fieldList as $k => $i) {
            if ($fieldList[$k]['name'] == $rel['foreignKey']) {
                continue;
            }
            if ($fieldList[$k]['name'] == $primaryKey) {
                continue;
            }
            $i['label'] = implode(" ", array_map('ucfirst', explode("_", $i['label'])));
            $filter     = [
                'filterType' => "string",
                'name' => $i['name'],
                'label' => $i['label']
            ];

            $filter = self::processFilterColumn([
                'filter' => $filter,
                'model' => $model,
                'field' => $i,
                'fieldIndex' => $k,
                'tableColumns' => $tableColumns
            ]);

            $options = [];
            if ($rel['insertable'] == "Inline" && $rel['editable'] == "Inline") {
                $options['mode'] = 'editable';
            } else if ($rel['insertable'] == "Inline" && $rel['editable'] != "Inline") {
                $options['mode'] = 'editable-insert';
            } else if ($rel['insertable'] != "Inline" && $rel['editable'] == "Inline") {
                $options['mode'] = 'editable-update';
            }

            $column = [
                'columnType' => "string",
                'options' => $options,
                'name' => 't.' . $i['name'],
                'label' => $i['label']
            ];

            $filterColumns[] = $filter;
            $gridColumns[]   = $column;
            $gv->columns[]   = $column;
        }

        if ($rel['type'] == "CManyManyRelation" && $rel['chooseable'] == "Yes") {
            $delButtonCol  = [
                'name' => '',
                'label' => '',
                'columnType' => "string",
                'options' => [
                    'mode' => 'unchoose-button',
                ]
            ];
            $gv->columns[] = $delButtonCol;
            array_push($gridColumns, $delButtonCol);
        }

        if ($rel['type'] == "CHasManyRelation") {
            if ($rel['deleteable'] == "Yes") {
                $delButtonCol  = [
                    'name' => '',
                    'label' => '',
                    'columnType' => "string",
                    'options' => [
                        'mode' => 'del-button',
                    ]
                ];
                $gv->columns[] = $delButtonCol;
                array_push($gridColumns, $delButtonCol);
            }
        }

        $return[] = [
            'name' => 'df' . $relName,
            'datasource' => 'ds' . $relName,
            'type' => 'DataFilter',
            'filters' => $filterColumns
        ];

        $return[] = [
            'name' => 'ds' . $relName,
            'relationTo' => $rel['name'],
            'relationCriteria' => array(
                'select' => '',
                'distinct' => 'false',
                'alias' => 't',
                'condition' => self::criteriaCondition($model),
                'order' => '{[order]}',
                'paging' => '{[paging]}',
                'group' => '',
                'having' => '',
                'join' => '',
            ),
            'type' => 'DataSource',
        ];
        $return[] = [
            'name' => 'gv' . $relName,
            'datasource' => 'ds' . $relName,
            'type' => 'GridView',
            'columns' => $gridColumns
        ];

        $return[] = [
            'renderInEditor' => 'Yes',
            'type' => 'Text',
            'value' => '<div style="height:175px;"></div>',
        ];


        return $return;
    }

    private static function criteriaCondition($model) {
        $condition = '{[where]}';
        if (!!$model->_softDelete) {
            $condition = 't.' . $model->_softDelete['column'] . ' <> \'' . $model->_softDelete['value'] . '\' {AND [where]}';
        }
        return $condition;
    }

    private static function appendGenMsg($message, $model) {
        if (!isset($_SESSION['CrudGenerator'][get_class($model)])) {
            $_SESSION['CrudGenerator'][get_class($model)] = [];
        }

        if (!isset($_SESSION['CrudGenerator'][get_class($model)]['msg'])) {
            $_SESSION['CrudGenerator'][get_class($model)] = [
                'msg' => '<b>WARNING: </b>'
            ];
        }

        $msg = $_SESSION['CrudGenerator'][get_class($model)]['msg'];
        $msg .= $message;
        $_SESSION['CrudGenerator'][get_class($model)] = ['msg' => $msg];
    }

    private static function processGridColumn($params, &$genBelongsTo = null) {
        $model           = '';
        $field           = [];
        $fieldIndex      = 0;
        $tableColumns    = [];
        $generatorParams = [];
        $prefixUrl       = '';
        extract($params);

        switch (true) {
            case $tableColumns[$field['name']]->dbType == "text":
                $field['type']                      = "TextArea";
                $field['fieldOptions']['auto-grow'] = "true";
                break;
            case $tableColumns[$field['name']]->dbType == "date":
                $field['type']      = "DateTimePicker";
                $field['fieldType'] = "date";
                break;
            case $tableColumns[$field['name']]->dbType == "datetime":
                $field['type']      = "DateTimePicker";
                $field['fieldType'] = "datetime";
                break;
            case $tableColumns[$field['name']]->dbType == "timestamp":
                $field['type']      = "DateTimePicker";
                $field['fieldType'] = "datetime";
                break;
            case $tableColumns[$field['name']]->dbType == "time":
                $field['type']      = "DateTimePicker";
                $field['fieldType'] = "time";
                break;
            case substr($field['name'], -3) == "_id": ## generate relation field
                $relClassName = self::getRelClassName($model, $field['name']);
                if (!!@$generatorParams['relations'] && !is_null($genBelongsTo)) {
                    foreach ($generatorParams['relations'] as $rel) {
                        if (!isset($rel['name'])) continue;

                        if (@$rel['type'] == 'CBelongsToRelation' && $relClassName == $rel['className']) {
                            $relClassName = $rel['className'];
                            $relName      = Helper::snakeToCamel(substr($field['name'], 0, -3));
                            $buttons      = [];

                            if ($rel['insertable'] == "Yes") {
                                $label     = strlen($field['label']) > 10 ? '' : $field['label'];
                                $buttons[] = [
                                    'renderInEditor' => 'Yes',
                                    'type' => 'Text',
                                    'value' => '<div
ng-click="rel' . $relName . 'InsertPopup.open()"
style="margin:0px 0px 10px 5px;" class="btn btn-xs btn-default pull-right"><i class="fa fa-plus"></i> New ' . $label . '</div>',
                                ];
                            }

                            $buttons[] = [
                                'renderInEditor' => 'Yes',
                                'type' => 'Text',
                                'value' => '<div
ng-click="rel' . $relName . 'UpdatePopup.open()" ng-if="!!model.' . $field['name'] . ' && !!' . $field['name'] . '.text"
style="margin-bottom:10px;" class="btn btn-xs btn-default pull-right"><i class="fa fa-pencil"></i> Edit ' . $field['label'] . '</div>
<div class="clearfix"></div>',
                            ];

                            if ($rel['insertable'] == "Yes") {
                                $buttons[] = [
                                    'type' => 'PopupWindow',
                                    'name' => 'rel' . $relName . 'InsertPopup',
                                    'options' => array(
                                        'height' => '500',
                                        'width' => '700',
                                    ),
                                    'mode' => 'url',
                                    'url' => $prefixUrl . '/insert' . $relName,
                                ];
                            }

                            $buttons[] = [
                                'type' => 'PopupWindow',
                                'name' => 'rel' . $relName . 'UpdatePopup',
                                'options' => array(
                                    'height' => '500',
                                    'width' => '700',
                                ),
                                'mode' => 'url',
                                'url' => $prefixUrl . '/update' . $relName . '&id={{model.' . $field['name'] . '}}',
                            ];

                            $genBelongsTo[] = [
                                'index' => $fieldIndex,
                                'data' => $buttons
                            ];
                            break;
                        }
                    }
                }

                if (@is_subclass_of($relClassName, 'ActiveRecord')) {
                    ## get class alias
                    if (is_file(Yii::getPathOfAlias('app.models.' . $relClassName) . ".php")) {
                        $classAlias = "app.models." . $relClassName;
                    } else if (is_file(Yii::getPathOfAlias('application.models.' . $relClassName) . ".php")) {
                        $classAlias = "application.models." . $relClassName;
                    } else {
                        $classAlias = '';
                    }

                    if ($classAlias != '') {
                        $field['type']       = "RelationField";
                        $field['modelClass'] = $classAlias;
                        $field['idField']    = $relClassName::model()->tableSchema->primaryKey;
                        $attr                = $relClassName::model()->attributes;

                        ## fill label field
                        if (array_key_exists('name', $attr)) {
                            $field['labelField'] = 'name';
                        } else if (array_key_exists('nama', $attr)) {
                            $field['labelField'] = 'nama';
                        } else {
                            foreach ($attr as $y => $z) {
                                if ($y == $field['idField'])
                                    continue;
                                if ($y == '_softDelete')
                                    continue;
                                if (substr($y, -3) == "_id")
                                    continue;

                                $field['labelField'] = $y;
                                break;
                            }
                        }
                    }
                } else if (!empty($generatorParams)) {
                    self::appendGenMsg('<br/>&bull; Failed to create RelationField <b>' . $field['name'] . '</b>,
                                      <br/> &nbsp; Model Class <b>' . $relClassName . '</b> is not available', $model);
                }
                break;
        }

        return $field;
    }

    private static function processFilterColumn($params) {
        $tableColumns = [];
        $field        = [];
        $filter       = [];
        $model        = null;
        extract($params);

        switch (true) {
            case in_array($tableColumns[$field['name']]->type, array("double", "integer")) && (substr($filter['name'], -3) != "_id"):
                $filter['filterType'] = "number";
                break;
            case $tableColumns[$field['name']]->dbType == "date":
                $filter['filterType'] = "date";
                break;
            case $tableColumns[$field['name']]->dbType == "datetime":
                $filter['filterType'] = "date";
                break;
            case $tableColumns[$field['name']]->dbType == "timestamp":
                $filter['filterType'] = "date";
                break;
            case (substr($filter['name'], -3) == "_id"):
                $relName         = substr($filter['name'], 0, strlen($filter['name']) - 3);
                $relName         = implode("", array_map('ucfirst', explode("_", $relName)));
                $filter['label'] = $relName;

                $relName = self::getRelClassName($model, $filter['name']);

                if (@is_subclass_of($relName, 'ActiveRecord')) {
                    if (is_file(Yii::getPathOfAlias('app.models.' . $relName) . ".php")) {
                        $classAlias = "app.models." . $relName;
                    } else if (is_file(Yii::getPathOfAlias('application.models.' . $relName) . ".php")) {
                        $classAlias = "application.models." . $relName;
                    } else {
                        $classAlias = '';
                    }

                    if ($classAlias != '') {
                        $filter['filterType']    = "relation";
                        $filter['relModelClass'] = $classAlias;
                        $filter['relIdField']    = $relName::model()->tableSchema->primaryKey;
                        $filter['relParams']     = [];
                        $filter['relCriteria']   = array(
                            'select' => '',
                            'distinct' => 'false',
                            'alias' => 't',
                            'condition' => '{[search]}',
                            'order' => '',
                            'group' => '',
                            'having' => '',
                            'join' => '',
                        );

                        $attr = $relName::model()->attributes;

                        ## fill label field
                        if (array_key_exists('name', $attr)) {
                            $filter['relLabelField'] = 'name';
                        } else if (array_key_exists('nama', $attr)) {
                            $filter['relLabelField'] = 'nama';
                        } else {
                            foreach ($attr as $y => $z) {
                                if ($y == 'id')
                                    continue;
                                if (substr($y, -3) == "_id")
                                    continue;

                                $filter['relLabelField'] = $y;
                                break;
                            }
                        }
                    }
                }
                break;
        }

        return $filter;
    }
}
