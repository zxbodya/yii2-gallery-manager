<?php

namespace zxbodya\yii2\galleryManager;

use Yii;
use yii\base\Exception;
use yii\base\Widget;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\JsExpression;
use zxbodya\yii2\galleryManager\models\Gallery;

/**
 * Widget to manage gallery.
 * Requires Twitter Bootstrap styles to work.
 *
 * @author Bogdan Savluk <savluk.bogdan@gmail.com>
 */
class GalleryManager extends Widget
{
    /** @var Gallery Model of gallery to manage */
    public $gallery;
    /** @var string Route to gallery controller */
    public $apiRoute = false;

    public $options = array();


    public function init()
    {
        parent::init();
        $this->registerTranslations();
    }

    public function registerTranslations()
    {
        $i18n = Yii::$app->i18n;
        $i18n->translations['galleryManager/*'] = [
            'class' => 'yii\i18n\PhpMessageSource',
            'sourceLanguage' => 'en-US',
            'basePath' => '@zxbodya/yii2/galleryManager/messages',
            'fileMap' => [],
        ];
    }


    /** Render widget */
    public function run()
    {
        if ($this->apiRoute === null) {
            throw new Exception('$apiRoute must be set.', 500);
        }

        $photos = array();
        foreach ($this->gallery->galleryPhotos as $photo) {
            $photos[] = array(
                'id' => $photo->id,
                'rank' => $photo->rank,
                'name' => (string)$photo->name,
                'description' => (string)$photo->description,
                'preview' => $photo->getPreview(),
            );
        }

        $opts = array(
            'hasName' => $this->gallery->name ? true : false,
            'hasDesc' => $this->gallery->description ? true : false,
            'uploadUrl' => Url::to(
                [
                    $this->apiRoute,
                    'action' => 'ajaxUpload',
                    'gallery_id' => $this->gallery->id
                ]
            ),
            'deleteUrl' => Url::to([$this->apiRoute, 'action' => 'delete']),
            'updateUrl' => Url::to([$this->apiRoute, 'action' => 'changeData']),
            'arrangeUrl' => Url::to([$this->apiRoute, 'action' => 'order']),
            'nameLabel' => Yii::t('galleryManager/main', 'Name'),
            'descriptionLabel' => Yii::t('galleryManager/main', 'Description'),
            'photos' => $photos,
        );

        $opts = Json::encode($opts);
        $view = $this->getView();
        GalleryManagerAsset::register($view);
        $view->registerJs("$('#{$this->id}').galleryManager({$opts});");

        $this->options['id'] = $this->id;
        $this->options['class'] = 'gallery-manager';

        return $this->render('galleryManager');
    }

}
