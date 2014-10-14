<?php

namespace zxbodya\yii2\galleryManager\models;

use Yii;

/**
 * This is the model class for table "{{%gallery}}".
 *
 * @property integer        $id
 * @property string         $versions_data
 * @property integer        $name
 * @property integer        $description
 * @property string         $extension
 *
 * @property GalleryPhoto[] $galleryPhotos
 *
 * @property array          $versions Settings for image auto-generation
 * @example
 *  array(
 *       'small' => array(
 *              'resize' => array(200, null),
 *       ),
 *      'medium' => array(
 *              'resize' => array(800, null),
 *      )
 *  );
 *
 *
 * @author Bogdan Savluk <savluk.bogdan@gmail.com>
 */
class Gallery extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%gallery}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['versions_data'], 'required'],
            [['versions_data'], 'string'],
            [['name', 'description'], 'integer'],
            [['extension'], 'string', 'max' => 10]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'versions_data' => 'Versions Data',
            'name' => 'Name',
            'description' => 'Description',
            'extension' => 'Extension',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGalleryPhotos()
    {
        return $this->hasMany(
            GalleryPhoto::className(),
            ['gallery_id' => 'id']
        )->orderBy('`rank` asc');
    }

    public function init()
    {
        parent::init();
        $this->extension = 'jpg';
    }


    /** @var string directory in web root for galleries */
    public $galleryDir = 'gallery';


    private $_versions;

    public function getVersions()
    {
        if (!isset($this->_versions)) {
            $this->_versions = unserialize($this->versions_data);
        }

        return $this->_versions;
    }

    public function setVersions($value)
    {
        $this->_versions = $value;
    }

    public function beforeSave($insert)
    {
        if (isset($this->_versions)) {
            $this->versions_data = serialize($this->_versions);
        }
        if (empty($this->versions_data)) {
            $this->versions_data = serialize(array());
        }

        return parent::beforeSave($insert);
    }

    public function delete()
    {
        foreach ($this->galleryPhotos as $photo) {
            $photo->delete();
        }

        return parent::delete();
    }

}
