<?php

namespace zxbodya\yii2\galleryManager;

use Yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\AssetBundle;
use yii\web\View;
use yii\widgets\InputWidget;

/**
 * This is just an example.
 */
class GalleryManagerAsset extends AssetBundle
{
    public $sourcePath = '@zxbodya/yii2/galleryManager/assets';
    public $js = [
        'jquery.iframe-transport.js',
        'jquery.galleryManager.js',
        // 'jquery.iframe-transport.min.js',
        // 'jquery.galleryManager.min.js',
    ];
    public $css = [
        'galleryManager.css'
    ];
    public $depends = [
        'yii\web\JqueryAsset',
        'yii\jui\JuiAsset'
    ];

}