<?php

namespace zxbodya\yii2\galleryManager;


use Yii;
use yii\base\Action;
use yii\db\ActiveRecord;
use yii\helpers\Json;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/**
 * Backend controller for GalleryManager widget.
 * Provides following features:
 *  - Image removal
 *  - Image upload/Multiple upload
 *  - Arrange images in gallery
 *  - Changing name/description associated with image
 *
 * @author Bogdan Savluk <savluk.bogdan@gmail.com>
 */
class GalleryManagerAction extends Action
{
    /**
     * Glue used to implode composite primary keys
     * @var string
     */
    public $pkGlue = '_';

    /**
     * The prefix of string for temporary id for new models
     * @var string
     */
    public $temporaryPrefix = 'temp';

    /**
     * $types to be defined at Controller::actions()
     * @var array Mapping between types and model class names
     * @example 'post' => 'common\models\Post'
     * @example 'post' => ['class' => common\models\Post::class]
     * @see     GalleryManagerAction::run
     */
    public $types = [];


    protected $type;
    protected $behaviorName;
    protected $galleryId;

    /** @var  ActiveRecord */
    protected $owner;
    /** @var  GalleryBehavior */
    protected $behavior;


    public function run($action)
    {
        $this->type = Yii::$app->request->get('type');
        $this->behaviorName = Yii::$app->request->get('behaviorName');
        $this->galleryId = Yii::$app->request->get('galleryId');

        if (!array_key_exists($this->type, $this->types)) {
            throw new HttpException(400, 'Type does not exists');
        }
        $this->owner = Yii::createObject($this->types[$this->type]);
        if ($this->galleryId && substr_compare($this->galleryId, $this->temporaryPrefix, 0, strlen($this->temporaryPrefix)) !== 0) {
            $pkNames = $this->owner->primaryKey();
            $pkValues = explode($this->pkGlue, $this->galleryId);
            $pk = array_combine($pkNames, $pkValues);
            $this->owner = $this->owner::findOne($pk);
            if (!$this->owner) {
                throw new NotFoundHttpException();
            }
        }
        $this->behavior = $this->owner->getBehavior($this->behaviorName);

        switch ($action) {
            case 'delete':
                return $this->actionDelete(Yii::$app->request->post('id'));
            case 'ajaxUpload':
                return $this->actionAjaxUpload();
            case 'changeData':
                return $this->actionChangeData(Yii::$app->request->post('photo'));
            case 'order':
                return $this->actionOrder(Yii::$app->request->post('order'));
            case 'deleteOrphan':
                return $this->actionDeleteOrphan();
            default:
                throw new HttpException(400, 'Action does not exists');
        }
    }

    /**
     * Removes image with ids specified in post request.
     * On success returns 'OK'
     *
     * @param $ids
     *
     * @return string
     */
    protected function actionDelete($ids)
    {

        $this->behavior->deleteImages($ids);

        return 'OK';
    }

    /**
     * Removes orphan images for this gallery
     * On success returns 'OK'
     *
     * @return string
     * @throws \yii\base\ErrorException
     * @throws \yii\db\Exception
     */
    protected function actionDeleteOrphan()
    {

        $this->behavior->deleteOrphanImages();

        return 'OK';
    }

    /**
     * Method to handle file upload thought XHR2
     * On success returns JSON object with image info.
     *
     * @return string
     */
    public function actionAjaxUpload()
    {

        $imageFile = UploadedFile::getInstanceByName('gallery-image');

        $fileName = $imageFile->tempName;
        $image = $this->behavior->addImage($fileName);

        // not "application/json", because  IE8 trying to save response as a file

        Yii::$app->response->headers->set('Content-Type', 'text/html');

        return Json::encode(
            array(
                'id' => $image->id,
                'rank' => $image->rank,
                'name' => (string) $image->name,
                'description' => (string) $image->description,
                'preview' => $image->getUrl('preview'),
            )
        );
    }

    /**
     * Saves images order according to request.
     *
     * @param array $order new arrange of image ids, to be saved
     *
     * @return string
     * @throws HttpException
     */
    public function actionOrder($order)
    {
        if (count($order) == 0) {
            throw new HttpException(400, 'No data, to save');
        }
        $res = $this->behavior->arrange($order);

        return Json::encode($res);
    }

    /**
     * Method to update images name/description via AJAX.
     * On success returns JSON array of objects with new image info.
     *
     * @param $imagesData
     *
     * @return string
     * @throws HttpException
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     */
    public function actionChangeData($imagesData)
    {
        if (count($imagesData) == 0) {
            throw new HttpException(400, 'Nothing to save');
        }
        $images = $this->behavior->updateImagesData($imagesData);
        $resp = array();
        foreach ($images as $model) {
            $resp[] = array(
                'id' => $model->id,
                'rank' => $model->rank,
                'name' => (string) $model->name,
                'description' => (string) $model->description,
                'preview' => $model->getUrl('preview'),
            );
        }

        return Json::encode($resp);
    }
}
