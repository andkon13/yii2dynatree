<?php
/**
 * Created by PhpStorm.
 * User: andkon
 * Date: 13.08.14
 * Time: 17:56
 */

namespace andkon\yii2dynatree;

use yii\base\Widget;
use yii\db\ActiveRecord;
use yii\helpers\Html;
use yii\web\View;

class Tree extends Widget
{
    /**
     * @var ActiveRecord[]
     */
    public $model = [];

    public $pkField = 'id';
    public $parentField = 'parent_id';
    public $titleField = 'name';
    public $ajaxUrl = null;

    /**
     * @var string
     */
    public $id = 'tree';

    /**
     * @var array
     */
    private $tree = [];

    /**
     * @return void
     */
    public function init()
    {
        parent::init();
        if (!empty($this->model)) {
            $this->renderTree();
            $this->tree = json_encode($this->tree);
            $this->registryScript();
        }
    }

    /**
     * @return string
     */
    public function run()
    {
        $html = Html::tag('div', '', ['id' => $this->id]);

        return $html;
    }

    /**
     * @return void
     */
    private function renderTree()
    {
        $tree = [];
        foreach ($this->model as $item) {
            $parent_id = intval($item->{$this->parentField});
            if (!array_key_exists($parent_id, $tree)) {
                $tree[$parent_id] = [];
            }

            $tree[$parent_id][] = $item;
        }

        $items      = $this->renderMenuItems($tree[0], $tree, true);
        $this->tree = $items;
    }

    /**
     * @param ActiveRecord[] $items
     * @param array          $tree
     * @param bool           $isFolder
     *
     * @return array
     */
    private function renderMenuItems($items, $tree, $isFolder = false)
    {
        $result = [];
        foreach ($items as $item) {
            $itemClass           = new \stdClass();
            $itemClass->title    = $item->{$this->titleField};
            $itemClass->isFolder = $isFolder;
            $itemClass->expand   = $isFolder;
            $itemClass->key      = $item->{$this->pkField};
            $children            = [];
            if (array_key_exists($item->{$this->pkField}, $tree)) {
                $children = $this->renderMenuItems($tree[$item->{$this->pkField}], $tree);
            }

            $itemClass->children = $children;
            $result[]            = $itemClass;
        }

        return $result;
    }

    /**
     * Регистрируем скритпы
     *
     * @return void
     */
    private function registryScript()
    {
        $path = \Yii::$app->getAssetManager()->publish(__DIR__ . '/assets/dynatree/');
        $this->getView()->registerJsFile(
            $path[1] . '/jquery-ui.custom.js',
            '\yii\web\JqueryAsset'
        );
        $this->getView()->registerJsFile(
            $path[1] . '/jquery.dynatree.js',
            ['\yii\web\JqueryAsset']
        );

        $this->getView()->registerCssFile($path[1] . '/skin-vista/ui.dynatree.css');
        $script = <<<JS
$(function() {
  $("#{$this->id}").dynatree({
    debugLevel: 0,
    dnd: {
      preventVoidMoves: true, // Prevent dropping nodes 'before self', etc.
      onDragStart: function(node) {
        /** This function MUST be defined to enable dragging for the tree.
         *  Return false to cancel dragging of node.
         */
        return true;
      },
      onDragEnter: function(node, sourceNode) {
        return true;
        /** sourceNode may be null for non-dynatree droppables.
         *  Return false to disallow dropping on node. In this case
         *  onDragOver and onDragLeave are not called.
         *  Return 'over', 'before, or 'after' to force a hitMode.
         *  Return ['before', 'after'] to restrict available hitModes.
         *  Any other return value will calc the hitMode from the cursor position.
         */
        // Prevent dropping a parent below another parent (only sort
        // nodes under the same parent)
        if(node.parent !== sourceNode.parent){
          return false;
        }
        // Don't allow dropping *over* a node (would create a child)
        return ["before", "after"];
      },
      onDrop: function(node, sourceNode, hitMode, ui, draggable) {
        /** This function MUST be defined to enable dropping of items on
         *  the tree.
         */
        sourceNode.move(node, hitMode);
      },
       onDragStop: function(node) {
        var id = node.data.key;
        var parent_id = node.parent.data.key;
        var sort = [];
        for(i in node.parent.childList){
            sort[i] = node.parent.childList[i].data.key;
        }
        console.log(parent_id, node);
        $.post(
            'http://lost2/index.php?r=Pages/menu-item/moveintree&post',
            {id: id, parent_id: parent_id, sort: sort}
        );
      }
    },
    children: {$this->tree}
  });
});
JS;

        $this->getView()->registerJs($script, View::POS_END);
    }
}
