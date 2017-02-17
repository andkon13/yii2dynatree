<?php
/**
 * Created by PhpStorm.
 * User: andkon
 * Date: 28.08.14
 * Time: 12:15
 */

namespace andkon\yii2dynatree\actions;

use andkon\yii2actions\Controller;
use yii\base\Action;

class MoveInTree extends Action
{
    public $pkField = 'id';
    public $parentField = 'parent_id';
    public $sortField = 'sort';

    public function run()
    {
        /** @var Controller $controller */
        $controller = $this->controller;
        $model      = $controller->findModel($controller->getPost($this->pkField));
        $parent_id  = intval($controller->getPost($this->parentField));
        $parent_id  = ($parent_id) ? $parent_id : null;
        $model->setAttribute($this->parentField, $parent_id);
        $model->save();
        $sort = $controller->getPost('sort');
        if (is_array($sort) && !empty($sort)) {
            $query = 'update "' . $model->tableName() . '" set "' . $this->sortField . '" = case ' . $this->pkField . ' ';
            foreach ($sort as $position => $id) {
                $id = intval($id);
                if (!$id) {
                    continue;
                }

                $query .= ' when ' . $id . ' then ' . $position;
            }

            $query .= ' end';
            $query .= ' where "' . $this->pkField . '" in (' . implode(',', $sort) . ')';
            \Yii::$app->getDb()->createCommand($query)->execute();
        }
    }
}
