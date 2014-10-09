<?php

class ActiveRecord extends CActiveRecord {

    /**
     * @return array of used behaviors
     */
    public function behaviors() {
        return array(
            'LoggableBehavior' => array(
                'class' => 'LoggableBehavior'
            ),
        );
    }

    private $__relations = array();
    private $__relationsObj = array();
    private $__oldRelations = array();
    private $__isRelationLoaded = false;
    private $__defaultPageSize = 25;
    private $__pageSize = array();
    private $__page = array();
    private $__relInsert = array();
    private $__relUpdate = array();
    private $__relDelete = array();

    private function initRelation() {
        $static = !(isset($this) && get_class($this) == get_called_class());

        if (!$static && !$this->__isRelationLoaded) {
            $this->loadRelations();
        }
    }

    public function __call($name, $args) {
        $this->initRelation();

        if (isset($this->__relationsObj[$name])) {
            $this->__page[$name] = $args[0];
            if (count($args) == 2) {
                $this->__pageSize[$name] = $args[1];
            }
            return $this->$name;
        } else {
            return parent::__call($name, $args);
        }
    }

    public function __set($name, $value) {
        switch (true) {
            case Helper::isLastString($name, 'PageSize'):
                $name = substr_replace($name, '', -8);
                $this->__pageSize[$name] = $value;
                break;
            case Helper::isLastString($name, 'Insert'):
                $this->initRelation();

                $name = substr_replace($name, '', -6);
                if (isset($this->__relations[$name])) {
                    $this->__relInsert[$name] = $value;
                }
                break;
            case Helper::isLastString($name, 'Update'):
                $this->initRelation();

                $name = substr_replace($name, '', -6);
                if (isset($this->__relations[$name])) {
                    $this->__relUpdate[$name] = $value;
                }
                break;
            case Helper::isLastString($name, 'Delete'):
                $this->initRelation();

                $name = substr_replace($name, '', -6);
                if (isset($this->__relations[$name])) {
                    $this->__relDelete[$name] = $value;
                }
                break;
            default:
                parent::__set($name, $value);
                break;
        }
    }

    public function __get($name) {
        switch (true) {
            case Helper::isLastString($name, 'Count'):
                $name = substr_replace($name, '', -5);
                $this->initRelation();
                if (isset($this->__relations[$name])) {
                    $rel = $this->__relations[$name];
                    if (count($rel) == 0) {
                        return 0;
                    } else if (Helper::is_assoc($rel)) {
                        return 1;
                    } else {
                        $c = $this->getRelated($name, true, array(
                            'select' => 'count(1) as id',
                        ));
                        return $c[0]->id;
                    }
                }
                break;
            case Helper::isLastString($name, 'PageSize'):
                $name = substr_replace($name, '', -8);
                if (isset($this->__pageSize[$name])) {
                    return $this->__pageSize[$name];
                } else {
                    return $this->__defaultPageSize;
                }
                break;
            case Helper::isLastString($name, 'CurrentPage'):
                $name = substr_replace($name, '', -11);
                return @$this->__page[$name] ? $this->__page[$name] : 1;
                break;
            case Helper::isLastString($name, 'Insert'):
                $name = substr_replace($name, '', -6);
                return @$this->__relInsert[$name];
                break;
            case Helper::isLastString($name, 'Update'):
                $name = substr_replace($name, '', -6);
                return @$this->__relUpdate[$name];
                break;
            case Helper::isLastString($name, 'Delete'):
                $name = substr_replace($name, '', -6);
                return @$this->__relDelete[$name];
                break;
            case isset($this->getMetaData()->relations[$name]):
                $this->loadRelations($name);
                return @$this->__relationsObj[$name];
                break;
            default:
                return parent::__get($name);
                break;
        }
    }

    public static function toArray($models = array()) {
        $result = array();
        foreach ($models as $k => $m) {
            $result[$k] = $m->attributes;
        }
        return $result;
    }

    public function loadRelations($name = null) {
        foreach ($this->getMetaData()->relations as $k => $rel) {
            if (!is_null($name) && $k != $name) {
                continue;
            }

            if (!isset($this->__relations[$k]) || !is_null($name)) {
                if (@class_exists($rel->className)) {
                    switch (get_class($rel)) {
                        case 'CHasOneRelation':
                        case 'CBelongsToRelation':
                            //todo..
                            if (is_string($rel->foreignKey)) {
                                $class = $rel->className;
                                $table = $class::tableName();
                                $foreignKey = $rel->foreignKey;

                                $this->__relationsObj[$k] = $this->getRelated($k, true);
                                if (isset($this->__relationsObj[$k])) {
                                    $this->__relations[$k] = $this->__relationsObj[$k]->attributes;
                                }
                            }
                            break;
                        case 'CManyManyRelation':
                        case 'CHasManyRelation':
                            //without through
                            if (is_string($rel->foreignKey)) {
                                $page = @$this->__page[$k] ? $this->__page[$k] : 1;
                                $pageSize = $this->{$k . 'PageSize'};
                                $start = ($page - 1) * $pageSize;

                                $this->__relationsObj[$k] = $this->getRelated($k, true, array(
                                    'limit' => $pageSize,
                                    'offset' => $start
                                ));

                                if (is_array($this->__relationsObj[$k])) {
                                    foreach ($this->__relationsObj[$k] as $i => $j) {
                                        $this->__relations[$k][$i] = $j->attributes;
                                    }
                                }
                            }

                            //with through
                            //todo..
                            break;
                    }
                }
            }
        }
        $this->__isRelationLoaded = true;
        $this->__oldRelations = $this->__relations;
    }

    public function setAttributes($values, $safeOnly = false, $withRelation = true) {
        parent::setAttributes($values, $safeOnly);
        $this->initRelation();

        foreach ($this->__relations as $k => $r) {
            switch (true) {
                case (isset($values[$k])):
                    $rel = $this->getMetaData()->relations[$k];
                    $this->__relations[$k] = $values[$k];
                    $relArr = $this->$k;

                    if (is_string($values[$k]) || (is_array($values[$k]))) {
                        if (is_string($values[$k])) {
                            $attr = json_decode($values[$k], true);
                            if (!is_array($attr)) {
                                $attr = array();
                            }
                        } else {
                            $attr = $values[$k];
                        }

                        if (Helper::is_assoc($values[$k])) {
                            switch (get_class($rel)) {
                                case 'CHasOneRelation':
                                case 'CBelongsToRelation':
                                    foreach ($attr as $i => $j) {
                                        if (is_array($j)) {
                                            unset($attr[$i]);
                                        }
                                    }

                                    if (is_object($relArr)) {
                                        $relArr->setAttributes($attr, false, false);
                                    }
                                    $this->__relations[$k] = $attr;
                                    break;
                            }
                        }
                    }
                    break;
                case (isset($values[$k . 'Insert'])):
                    $this->{$k . 'Insert'} = $values[$k . 'Insert'];
                    break;
                case (isset($values[$k . 'Update'])):
                    $this->{$k . 'Update'} = $values[$k . 'Update'];
                    break;
                case (isset($values[$k . 'Delete'])):
                    $this->{$k . 'Delete'} = $values[$k . 'Delete'];
                    break;
            }
        }

        foreach ($this->attributeProperties as $k => $r) {
            if (isset($values[$k])) {
                $this->$k = $values[$k];
            }
        }
    }

    public function getAttributes($names = true, $withRelation = true) {
        $attributes = parent::getAttributes($names);
        $attributes = array_merge($this->attributeProperties, $attributes);
        if ($withRelation) {
            foreach ($this->__relations as $k => $r) {
                $attributes[$k] = $this->__relations[$k];
            }
        }

        return $attributes;
    }

    public function getAttributesRelated($names = true) {
        $attributes = parent::getAttributes($names);
        $attributes = array_merge($attributes, $this->__relations);
        $attributes = array_merge($this->attributeProperties, $attributes);

        return $attributes;
    }

    public function getAttributeProperties() {
        $props = array();
        $class = new ReflectionClass($this);
        $properties = Helper::getClassProperties($this);

        foreach ($properties as $p) {
            $props[$p->name] = $this->{$p->name};
        }
        return $props;
    }

    public function getAttributesList($names = true) {
        $fields = array();
        $props = array();
        $relations = array();
        foreach (parent::getAttributes($names) as $k => $i) {
            $fields[$k] = $k;
        }
        foreach ($this->getMetaData()->relations as $k => $r) {
            if (!isset($fields[$k])) {
                if (@class_exists($r->className)) {
                    $relations[$k] = $k;
                }
            }
        }
        foreach ($this->attributeProperties as $k => $r) {
            $props[$k] = $k;
        }

        $attributes = array('DB Fields' => $fields);

        if (count($props) > 0) {
            $attributes = $attributes + array('Properties' => $props);
        }

        if (count($relations) > 0) {
            $attributes = $attributes + array('Relations' => $relations);
        }

        return $attributes;
    }

    public function beforeSave() {
        if ($this->primaryKey == '') {
            $table = $this->getMetaData()->tableSchema;
            $primaryKey = $table->primaryKey;
            $this->$primaryKey = null;
        }

        return true;
    }

    public function afterSave() {
        if ($this->isNewRecord) {
            $this->id = Yii::app()->db->getLastInsertID(); // this is hack
        }
        foreach ($this->__relations as $k => $new) {
            $new = $new == '' ? array() : $new;
            $old = $this->__oldRelations[$k];

            if (is_array($new) && is_array($old) && (count($old) > 0 || count($new) > 0)) {
                $rel = $this->getMetaData()->relations[$k];

                switch (get_class($rel)) {
                    case 'CHasOneRelation':
                    case 'CBelongsToRelation':
                        if (count(array_diff_assoc($new, $old)) > 0) {
                            //todo..
                            $class = $rel->class;
                            $model = $class::model()->findByPk($this->{$rel->foreignKey});
                            if (is_null($model)) {
                                $model = new $class;
                            }
                            $model->attributes = $new;
                            $model->{$rel->foreignKey} = $this->id;
                            $model->save();
                        }
                        break;
                    case 'CManyManyRelation':
                    case 'CHasManyRelation':
                        //without through
                        if (is_string($rel->foreignKey)) {
                            if (isset($this->{$k . 'Insert'})) {
                                ActiveRecord::batchInsert($class, $this->{$k . 'Insert'});
                            }

                            if (isset($this->{$k . 'Update'})) {
                                ActiveRecord::batchDelete($class, $this->{$k . 'Update'});
                            }

                            if (isset($this->{$k . 'Delete'})) {
                                ActiveRecord::batchDelete($class, $this->{$k . 'Delete'});
                            }
                            $this->loadRelations($k);
                        }
                        //with through
                        //todo..
                        break;
                }
            }
            $this->__relations[$k] = $new;
        }

        return true;
    }

    /**
     * Returns the static model of the specified AR class.
     * @return the static model class
     */
    public static function model($className = null) {
        if (is_null($className)) {
            $className = get_called_class();
        }
        return parent::model($className);
    }

    public function getModelFieldList() {
        $fields = array_keys(parent::getAttributes());

        foreach ($fields as $k => $f) {
            if ($this->tableSchema->primaryKey == $f) {
                $type = "HiddenField";
            } else {
                $type = "TextField";
            }

            $array[] = array(
                'name' => $f,
                'type' => $type,
                'label' => $this->getAttributeLabel($f)
            );
        }
        return $array;
    }

    public static function batch($model, $new, $old = array(), $delete = true) {
        $deleteArr = array();
        $updateArr = array();

        foreach ($old as $k => $v) {
            $is_deleted = true;
            $is_updated = false;

            foreach ($new as $i => $j) {
                if (@$j['id'] == @$v['id']) {
                    $is_deleted = false;
                    if (count(array_diff_assoc($j, $v)) > 0) {
                        $is_updated = true;
                        $updateArr[] = $j;
                    }
                }
            }

            if ($is_deleted) {
                $deleteArr[] = $v;
            }
        }

        $insertArr = array();
        $insertIds = array();
        foreach ($new as $i => $j) {
            if (@$j['id'] == '' || is_null(@$j['id'])) {
                $insertArr[] = $j;
                $insertIds[] = $i;
            } else if (count($old) == 0) {
                $updateArr[] = $j;
            }
        }

        if (count($insertArr) > 0) {
            ActiveRecord::batchInsert($model, $insertArr);
        }

        if (count($updateArr) > 0) {
            ActiveRecord::batchUpdate($model, $updateArr);
        }

        if ($delete && count($deleteArr) > 0) {
            ActiveRecord::batchDelete($model, $deleteArr);
        }

        return array_merge($insertArr, $updateArr);
    }

    public static function batchDelete($model, $data) {
        if (!is_array($data) || count($data) == 0)
            return;

        $table = $model::model()->tableSchema->name;

        $ids = array();
        foreach ($data as $i => $j) {
            $ids[] = $j['id'];
        }
        $delete = "DELETE FROM {$table} WHERE id IN (" . implode(",", $ids) . ");";

        $command = Yii::app()->db->createCommand($delete);
        $command->execute();
    }

    public static function batchUpdate($model, $data) {
        if (!is_array($data) || count($data) == 0)
            return;
        $table = $model::model()->tableSchema->name;
        $field = $model::model()->tableSchema->columns;
        unset($field['id']);

        $columnCount = count($field);
        $columnName = array_keys($field);
        $update = "";
        foreach ($data as $d) {
            $cond = $d['id'];
            unset($d['id']);
            $updatearr = array();
            for ($i = 0; $i < $columnCount; $i++) {
                if (isset($columnName[$i]) && isset($d[$columnName[$i]])) {
                    $updatearr[] = $columnName[$i] . " = '{$d[$columnName[$i]]}'";
                }
            }

            $updatesql = implode(",", $updatearr);
            if ($updatesql != '') {
                $update .= "UPDATE {$table} SET {$updatesql} WHERE id='{$cond}';";
            }
        }
        if ($update != '') {
            $command = Yii::app()->db->createCommand($update);
            $command->execute();
        }
    }

    public static function listData($idField, $valueField, $condition = '') {
        $class = get_called_class();
        return CHtml::listData($class::model()->findAll(), $idField, $valueField);
    }

    public static function batchInsert($model, &$data) {
        if (!is_array($data) || count($data) == 0)
            return;

        $table = $model::model()->tableSchema->name;
        $builder = Yii::app()->db->schema->commandBuilder;
        $command = $builder->createMultipleInsertCommand($table, $data);
        $command->execute();

        $id = Yii::app()->db->getLastInsertID();
        foreach ($data as &$d) {
            $d['id'] = $id;
            $id++;
        }
    }

    public function getDefaultFields() {
        $array = $this->modelFieldList;
        $length = count($array);
        $column1 = array();
        $column2 = array();
        $array_id = null;

        foreach ($array as $k => $i) {
            if ($array[$k]['name'] == 'id') {
                $array_id = $array [$k];
                continue;
            }

            if ($k < $length / 2) {
                $column1[] = $array[$k];
            } else {
                $column2[] = $array[$k];
            }
        }

        $column1[] = '<column-placeholder></column-placeholder>';
        $column2[] = '<column-placeholder></column-placeholder>';

        $return = array();
        $return[] = array(
            'type' => 'ActionBar',
        );

        if (!is_null($array_id)) {
            $return[] = $array_id;
        }

        $return[] = array(
            'type' => 'ColumnField',
            'column1' => $column1,
            'column2' => $column2
            )
        ;
        return $return;
    }

}
