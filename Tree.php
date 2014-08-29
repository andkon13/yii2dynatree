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

    public $isAjax = false;

    /**
     * @var string
     */
    public $id = 'tree';

    /**
     * @var array
     */
    private $tree = [];

    private $script;

    protected $functions = [
        'checkbox'       => 'false',
        'onActivate'     => 'null',
        'onDeactivate'   => 'null',
        'onFocus'        => 'null',
        'onBlur'         => 'null',
        'minExpandLevel' => 1,
        'onClick'        => '""',
        'dnd'            => [
            'preventVoidMoves' => 'true',
            'onDragStart'      => 'function(node) {return true;}',
            'onDragEnter'      => 'function(node, sourceNode) {return true;}',
            'onDrop'           => 'function(node, sourceNode, hitMode, ui, draggable) {sourceNode.move(node, hitMode);}',
            'onDragStop'       => '
                function(node) {
                    var id = node.data.key;
                    var parent_id = node.parent.data.key;
                    var sort = [];
                    for(i in node.parent.childList){
                        sort[i] = node.parent.childList[i].data.key;
                    }
                    console.log(parent_id, node);
                    $.post(
                        "{$this->ajaxUrl}",
                        {id: id, parent_id: parent_id, sort: sort}
                    );
                }',
        ]
    ];

    /**
     * @return void
     */
    public function init()
    {
        parent::init();
        if (!empty($this->model)) {
            $this->renderTree();
        }
    }

    /**
     * @return string
     */
    public function run()
    {
        $html       = Html::tag('div', '', ['id' => $this->id]);
        $this->tree = json_encode($this->tree);
        $this->registryScript();

        if ($this->isAjax) {
            $html .= Html::script($this->script);
        }

        return $html;
    }

    /**
     * @return void
     */
    protected function renderTree()
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
    protected function renderMenuItems($items, $tree, $isFolder = false)
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
     * Возвращает JS функцию по пути вида dnd/preventVoidMoves
     * Сделано для упрощения переопределения js функций в классах потомках
     *
     * @param string $path
     *
     * @return bool
     */
    protected function getTreeJsFunction($path)
    {
        $path   = explode('/', $path);
        $result = false;

        $node = $this->functions;
        foreach ($path as $key) {
            if (array_key_exists($key, $node)) {
                $node   = $node[$key];
                $result = $node;
            } else {
                break;
            }
        }

        return $result;
    }

    /**
     * Регистрируем скритпы
     *
     * @return void
     */
    protected function registryScript()
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
        $script       = <<<JS
$(function() {
  $("#{$this->id}").dynatree({
    onActivate: {$this->getTreeJsFunction('onActivate')},
    onDeactivate: {$this->getTreeJsFunction('onDeactivate')},
    minExpandLevel: {$this->getTreeJsFunction('minExpandLevel')},
    checkbox: {$this->getTreeJsFunction('checkbox')},
    onFocus: {$this->getTreeJsFunction('onFocus')},
    onBlur: {$this->getTreeJsFunction('onBlur')},
    debugLevel: 0,
    onClick: {$this->getTreeJsFunction('onClick')},
    dnd: {
      preventVoidMoves: {$this->getTreeJsFunction('dnd/preventVoidMoves')}, // Prevent dropping nodes 'before self', etc.
      onDragStart: {$this->getTreeJsFunction('dnd/onDragStart')},
      onDragEnter: {$this->getTreeJsFunction('dnd/onDragEnter')},
      onDrop: {$this->getTreeJsFunction('dnd/onDrop')},
      onDragStop: {$this->getTreeJsFunction('dnd/onDragStop')}
    },
    children: {$this->tree}
  });
});
JS;
        $this->script = str_replace('{$this->ajaxUrl}', $this->ajaxUrl, $script);
        $this->getView()->registerJs($this->script, View::POS_END);
    }
}
