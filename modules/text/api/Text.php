<?php
namespace yii\easyii\modules\text\api;

use Yii;
use yii\easyii\components\API;
use yii\easyii\helpers\Data;
use yii\easyii\modules\text\models\Text as TextModel;
use yii\helpers\Html;
use yii\helpers\Url;

class Text extends API
{
    private $_texts = [];

    public function init()
    {
        parent::init();

        $this->_texts = Data::cache(TextModel::CACHE_KEY, 3600, function(){
            return TextModel::find()->asArray()->all();
        });
    }

    public function api_get($id_slug)
    {
        if(($text = $this->findText($id_slug)) === null){
            return $this->notFound($id_slug);
        }
        return LIVE_EDIT ? API::liveEdit($text['text'], Url::to(['/admin/text/a/edit/', 'id' => $text['text_id']])) : $text['text'];
    }

    private function findText($id_slug)
    {
        foreach ($this->_texts as $item) {
            if($item['slug'] == $id_slug || $item['text_id'] == $id_slug){
                return $item;
            }
        }
        return null;
    }

    private function notFound($id_slug)
    {
        $text = '';

        if(!Yii::$app->user->isGuest && preg_match(TextModel::$SLUG_PATTERN, $id_slug)){
            $text = Html::a(Yii::t('easyii/text/api', 'Create text'), ['/admin/text/a/create', 'slug' => $id_slug], ['target' => '_blank']);
        }

        return $text;
    }
}