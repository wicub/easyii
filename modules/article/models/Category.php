<?php
namespace yii\easyii\modules\article\models;

use Yii;
use yii\behaviors\SluggableBehavior;
use yii\easyii\behaviors\CacheFlush;
use yii\easyii\behaviors\SeoBehavior;
use yii\easyii\behaviors\SortableModel;
use creocoder\nestedsets\NestedSetsBehavior;
use yii\easyii\helpers\Data;

class Category extends \yii\easyii\components\ActiveRecordNS
{
    const STATUS_OFF = 0;
    const STATUS_ON = 1;
    const TREE_CACHE_KEY = 'easyii_article_tree';
    const FLAT_CACHE_KEY = 'easyii_article_flat';

    public static function tableName()
    {
        return 'easyii_article_categories';
    }

    public function rules()
    {
        return [
            ['title', 'required'],
            ['title', 'trim'],
            ['title', 'string', 'max' => 128],
            ['image', 'image'],
            ['slug', 'match', 'pattern' => self::$SLUG_PATTERN, 'message' => Yii::t('easyii', 'Slug can contain only 0-9, a-z and "-" characters (max: 128).')],
            ['slug', 'default', 'value' => null]
        ];
    }

    public function attributeLabels()
    {
        return [
            'title' => Yii::t('easyii', 'Title'),
            'image' => Yii::t('easyii', 'Image'),
            'slug' => Yii::t('easyii', 'Slug'),
        ];
    }

    public function behaviors()
    {
        return [
            SortableModel::className(),
            'cacheflush' => [
                'class' => CacheFlush::className(),
                'key' => [self::TREE_CACHE_KEY, self::FLAT_CACHE_KEY]
            ],
            'seoBehavior' => SeoBehavior::className(),
            'sluggable' => [
                'class' => SluggableBehavior::className(),
                'attribute' => 'title',
                'ensureUnique' => true
            ],
            'tree' => [
                'class' => NestedSetsBehavior::className(),
                'treeAttribute' => 'tree'
            ]
        ];
    }

    public function getItems()
    {
        return $this->hasMany(Item::className(), ['category_id' => 'category_id'])->sort();
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if(!$this->isNewRecord && $this->image != $this->oldAttributes['image']){
                @unlink(Yii::getAlias('@webroot').$this->oldAttributes['image']);
            }
            return true;
        } else {
            return false;
        }
    }

    public function afterDelete()
    {
        parent::afterDelete();

        foreach ($this->getItems()->all() as $item) {
            $item->delete();
        }
        if($this->image) {
            @unlink(Yii::getAlias('@webroot') . $this->image);
        }
    }

    public static function tree()
    {
        return Data::cache(self::TREE_CACHE_KEY, 3600, function(){
            return self::getTree();
        });
    }

    public static function flat()
    {
        return Data::cache(self::FLAT_CACHE_KEY, 3600, function(){
            return self::getFlat();
        });
    }
}