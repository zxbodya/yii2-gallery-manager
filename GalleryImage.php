<?php

namespace zxbodya\yii2\galleryManager;

/**
 * Class GalleryImage
 * This is the model class for table "{{%gallery_image}}"
 * We can change table name use $tableName property of GalleryBehavior
 * @see \zxbodya\yii2\galleryManager\GalleryBehavior::$tableName.
 *
 * We can use this class in rules:
 *     public function rules()
 *     {
 *         return [
 *             [['mainPhoto'], 'exist', 'skipOnError' => true, 'targetClass' => get_class(Yii::createObject(GalleryImage::class)), 'targetAttribute' => ['mainPhoto' => 'id']],
 *         ];
 *     }
 *
 * @property int $id
 * @property string $type
 * @property string $ownerId
 * @property int $rank
 * @property string $name
 * @property string $description
 *
 * @package zxbodya\yii2\galleryManager
 */
class GalleryImage extends \yii\db\ActiveRecord
{
    /**
     * @var string table name of AR
     */
    protected static $tableName;
    /**
     * @var GalleryBehavior
     */
    protected $galleryBehavior;

    /**
     * @param GalleryBehavior $galleryBehavior
     * @param array $props
     */
    public function __construct(GalleryBehavior $galleryBehavior, array $props)
    {
        $this->galleryBehavior = $galleryBehavior;
        self::$tableName = $galleryBehavior->tableName;

        parent::__construct($props);
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return static::$tableName;
    }

    /**
     * @param string $version
     *
     * @return string
     */
    public function getUrl($version)
    {
        return $this->galleryBehavior->getUrl($this->id, $version);
    }
}
