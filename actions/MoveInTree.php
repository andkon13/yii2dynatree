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
    public function run()
    {
        /** @var Controller $controller */
        $controller       = $this->controller;
        $model            = $controller->findModel($controller->getPost('id'));
        $parent_id        = intval($controller->getPost('parent_id'));
        $parent_id        = ($parent_id) ? $parent_id : null;
        $model->parent_id = $parent_id;
        $model->save();
        $sort = $controller->getPost('sort');
        if (is_array($sort) && !empty($sort)) {
            $query = 'update ' . $model->tableName() . ' set sort = case id ';
            foreach ($sort as $position => $id) {
                $id = intval($id);
                if (!$id) {
                    continue;
                }

                $query .= ' when ' . $id . ' then ' . $position;
            }

            $query .= ' end';
            \Yii::$app->getDb()->createCommand($query)->execute();
        }
    }
}
