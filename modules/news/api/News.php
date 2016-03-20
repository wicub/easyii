<?php
namespace yii\easyii\modules\news\api;

use Yii;
use yii\data\ActiveDataProvider;
use yii\easyii\models\Tag;
use yii\easyii\modules\news\NewsModule;
use yii\easyii\widgets\Fancybox;
use yii\web\NotFoundHttpException;
use yii\widgets\LinkPager;

use yii\easyii\modules\news\models\News as NewsModel;

/**
 * News module API
 * @package yii\easyii\modules\news\api
 *
 * @method static NewsObject get(mixed $id_slug) Get news object by id or slug
 * @method static array items(array $options = []) Get list of news as NewsObject objects
 * @method static mixed last(int $limit = 1) Get last news
 * @method static void plugin() Applies FancyBox widget on photos called by box() function
 * @method static string pages() returns pagination html generated by yii\widgets\LinkPager widget.
 * @method static \stdClass pagination() returns yii\data\Pagination object.
 */

class News extends \yii\easyii\components\API
{
    private $_adp;
    private $_item = [];

    public function api_items($options = [])
    {
        $result = [];

        $with = ['seo'];
        if(NewsModule::setting('enableTags')){
            $with[] = 'tags';
        }
        $query = NewsModel::find()->with($with)->status(NewsModel::STATUS_ON);

        if(!empty($options['where'])){
            $query->andFilterWhere($options['where']);
        }
        if(!empty($options['tags'])){
            $query
                ->innerJoinWith('tags', false)
                ->andWhere([Tag::tableName() . '.name' => (new NewsModel)->filterTagValues($options['tags'])])
                ->addGroupBy('news_id');
        }
        if(!empty($options['orderBy'])){
            $query->orderBy($options['orderBy']);
        } else {
            $query->sortDate();
        }

        $this->_adp = new ActiveDataProvider([
            'query' => $query,
            'pagination' => !empty($options['pagination']) ? $options['pagination'] : []
        ]);

        foreach($this->_adp->models as $model){
            $result[] = new NewsObject($model);
        }
        return $result;
    }

    public function api_get($id_slug)
    {
        if(!isset($this->_item[$id_slug])) {
            $this->_item[$id_slug] = $this->findNews($id_slug);
        }
        return $this->_item[$id_slug];
    }

    public function api_last($limit = 1)
    {
        $with = ['seo'];
        if(NewsModule::setting('enableTags')){
            $with[] = 'tags';
        }

        $result = [];
        foreach(NewsModel::find()->with($with)->status(NewsModel::STATUS_ON)->sortDate()->limit($limit)->all() as $item){
            $result[] = new NewsObject($item);
        }
        return $result;
    }

    public function api_plugin($options = [])
    {
        Fancybox::widget([
            'selector' => '.easyii-box',
            'options' => $options
        ]);
    }

    public function api_pagination()
    {
        return $this->_adp ? $this->_adp->pagination : null;
    }

    public function api_pages()
    {
        return $this->_adp ? LinkPager::widget(['pagination' => $this->_adp->pagination]) : '';
    }

    private function findNews($id_slug)
    {
        if(!($news = NewsModel::find()->where(['or', 'news_id=:id_slug', 'slug=:id_slug'], [':id_slug' => $id_slug])->status(NewsModel::STATUS_ON)->one())) {
            throw new NotFoundHttpException(Yii::t('easyii', 'Not found'));
        }
        $news->updateCounters(['views' => 1]);
        return new NewsObject($news);
    }
}