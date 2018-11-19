# Gallery Manager usage instructions

Yii2 port of https://github.com/zxbodya/yii-gallery-manager

(frontend part mostly without changes, but backend was rewritten almost completely)

Gallery manager screenshots (yii 1.x version, new one has bootstrap 3 styles):

![GalleryManager images list](http://zxbodya.cc.ua/scrup/ci/eh1n1th6o0c80.png "Gallery Manager Screenshot")

Few more screenshots:
[drag & drop upload](http://zxbodya.cc.ua/scrup/6w/64q4icig84oo0.png "Drag & Drop image upload"), [editing image information](http://zxbodya.cc.ua/scrup/za/gfc68h5b4gksg.png "Edit image information"), [upload progress](http://zxbodya.cc.ua/scrup/8v/tijrezh7oksk8.png "upload progress"), 


## Features

1. AJAX image upload
2. Optional name and description for each image
3. Possibility to arrange images in gallery
4. Ability to generate few versions for each image with different configurations
5. Drag & Drop

## Decencies

1. Yii2
2. Twitter bootstrap assets (version 3)
3. Imagine library
4. JQuery UI (included with Yii)

## Installation:
The preferred way to install this extension is through [composer](https://getcomposer.org/).

Either run

`php composer.phar require --prefer-dist zxbodya/yii2-gallery-manager "*@dev"`

or add

`"zxbodya/yii2-gallery-manager": "*@dev"`

to the require section of your `composer.json` file.

## Usage

### Prepare

Add migration namespace for your console application config to create table for images
(add the following lines to the file `%PROJECT_DIR%/console/config/main.php`):
```php
return [
    // ...
    'controllerMap' => [
        'migrate' => [
            'class' => \yii\console\controllers\MigrateController::class,
            'migrationNamespaces' => [
                'zxbodya\yii2\galleryManager\migrations',
            ],
            'migrationPath' => [
                '@app/migrations',
            ],
        ],
    ],
    // ...
];
```

Now we should run `yii migrate/up` command for apply all widget migrations.

### Add configurations for upload and store images

Add GalleryBehavior to your model, and configure it, create folder for uploaded files.

```php
use zxbodya\yii2\galleryManager\GalleryBehavior;

class Product extends \yii\db\ActiveRecord 
{
...
public function behaviors()
{
    return [
         'galleryBehavior' => [
             'class' => GalleryBehavior::className(),
             'type' => 'product',
             'extension' => 'jpg',
             'directory' => Yii::getAlias('@webroot') . '/images/product/gallery',
             'url' => Yii::getAlias('@web') . '/images/product/gallery',
             'versions' => [
                 'small' => function ($img) {
                     /** @var \Imagine\Image\ImageInterface $img */
                     return $img
                         ->copy()
                         ->thumbnail(new \Imagine\Image\Box(200, 200));
                 },
                 'medium' => function ($img) {
                     /** @var \Imagine\Image\ImageInterface $img */
                     $dstSize = $img->getSize();
                     $maxWidth = 800;
                     if ($dstSize->getWidth() > $maxWidth) {
                         $dstSize = $dstSize->widen($maxWidth);
                     }
                     return $img
                         ->copy()
                         ->resize($dstSize);
                 },
             ]
         ]
    ];
}
```


Add GalleryManagerAction in controller somewhere in your application. Also on this step you can add some security checks for this action.

```php
use zxbodya\yii2\galleryManager\GalleryManagerAction;

class ProductController extends Controller
{
...
public function actions()
{
    return [
       'galleryApi' => [
           'class' => GalleryManagerAction::className(),
           // mappings between type names and model classes (should be the same as in behaviour)
           'types' => [
               'product' => Product::className()
           ]
       ],
    ];
}
```
        
Add ImageAttachmentWidget somewhere in you application, for example in editing from.

```php
use zxbodya\yii2\galleryManager\GalleryManager;

/* @var $this yii\web\View */
/* @var $model Product */
?>
...
<?php
if ($model->isNewRecord) {
    echo 'Can not upload images for new record';
} else {
    echo GalleryManager::widget(
        [
            'model' => $model,
            'behaviorName' => 'galleryBehavior',
            'apiRoute' => 'product/galleryApi'
        ]
    );
}
?>
```
        
Done!

### Get uploaded images
Now, you can use uploaded images from gallery like following:

```php
foreach($model->getBehavior('galleryBehavior')->getImages() as $image) {
    echo Html::img($image->getUrl('medium'));
}
```


## Options 

### Using non default table name for gallery images(default is `{{%gallery_image}}`):

1. Add migration that will create table you need
2. Change `tableName` property in behavior configuration
