yii2dynatree
============
<pre>
demo:
    http://andkon.bl.ee/index.php?r=store

install:

php composer.phar require "andkon/yii2dynatree": "dev-master"

Как использовать/How to use

Model:
need attributes:
    id - primary key
    parent_id - FK to id
    sort - INT order in tree
    name - STRING as label

Controller:
class UnitController extends Controller
{
    ...
    public function actions()
    {
        $actions               = parent::actions();
        $actions['moveintree'] = 'andkon\yii2dynatree\actions\MoveInTree';

        return $actions;
    }
    ...
}

Widget:
class Tree extends \andkon\yii2dynatree\Tree
{
    public function init()
    {
        parent::init();
        $this->functions['onClick'] = 'function (node, event) {
            unit.showDetal(node.data.key);
        }';
    }
}

View:
echo \app\pathToWidget\Tree::widget(
    [
        'id'      => 'treeId',
        'isAjax'  => true, // true for use ajax load widget (in dialog|popup etc.) or false for standart render
        'ajaxUrl' => Yii::$app->getUrlManager()->createUrl('/pathToController/moveintree'),
        'model'   => $model,
    ]
);
</pre>
