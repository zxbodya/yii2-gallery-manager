<?php

namespace zxbodya\yii2\galleryManager;

use Yii;
use yii\base\Action;
use yii\helpers\Json;
use yii\web\HttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use zxbodya\yii2\galleryManager\models\GalleryPhoto;

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

    public function run($action)
    {
        switch ($action) {
            case 'delete':
                return $this->actionDelete(Yii::$app->request->post('id'));
                break;
            case 'ajaxUpload':
                return $this->actionAjaxUpload(Yii::$app->request->get('gallery_id'));
                break;
            case 'changeData':
                return $this->actionChangeData(Yii::$app->request->post('photo'));
                break;
            case 'order':
                return $this->actionOrder(Yii::$app->request->post('order'));
                break;
            default:
                throw new HttpException(400, 'Action do not exists');
                break;
        }
    }

    /**
     * Removes image with ids specified in post request.
     * On success returns 'OK'
     *
     * @param $id
     *
     * @throws HttpException
     * @return string
     */
    private function actionDelete($id)
    {
        /** @var $photos GalleryPhoto[] */
        $photos = GalleryPhoto::findAll(['id' => $id]);
        foreach ($photos as $photo) {
            if ($photo !== null) {
                $photo->delete();
            } else {
                throw new HttpException(404, 'Photo, not found');
            }
        }

        return 'OK';
    }

    /**
     * Method to handle file upload thought XHR2
     * On success returns JSON object with image info.
     *
     * @param $gallery_id string Gallery Id to upload images
     *
     * @return string
     * @throws HttpException
     */
    public function actionAjaxUpload($gallery_id)
    {
        $model = new GalleryPhoto();
        $model->gallery_id = $gallery_id;
        $imageFile = UploadedFile::getInstanceByName('image');
        $model->file_name = $imageFile->name;
        $model->save();

        $model->setImage($imageFile->tempName);

        // not "application/json", because  IE8 trying to save response as a file

        Yii::$app->response->headers->set('Content-Type', 'text/html');

        return Json::encode(
            array(
                'id' => $model->id,
                'rank' => $model->rank,
                'name' => (string)$model->name,
                'description' => (string)$model->description,
                'preview' => $model->getPreview(),
            )
        );
    }

    /**
     * Saves images order according to request.
     * Variable $_POST['order'] - new arrange of image ids, to be saved
     * @throws HttpException
     */
    public function actionOrder($order)
    {
        if (count($order) == 0) {
            throw new HttpException(400, 'No data, to save');
        }
        $orders = array();
        $i = 0;
        foreach ($order as $k => $v) {
            if (!$v) {
                $order[$k] = $k;
            }
            $orders[] = $order[$k];
            $i++;
        }
        sort($orders);
        $i = 0;
        $res = array();
        foreach ($order as $k => $v) {
            /** @var $p GalleryPhoto */
            $p = GalleryPhoto::findOne(['id' => $k]);
            $p->rank = $orders[$i];
            $res[$k] = $orders[$i];
            $p->save(false);
            $i++;
        }

        return Json::encode($res);

    }

    /**
     * Method to update images name/description via AJAX.
     * On success returns JSON array od objects with new image info.
     *
     * @param $photo
     *
     * @throws HttpException
     * @return string
     */
    public function actionChangeData($photo)
    {
        if (count($photo) == 0) {
            throw new HttpException(400, 'Nothing to save');
        }
        /** @var $models GalleryPhoto[] */
        $models = GalleryPhoto::find()
            ->where(['in', 'id', array_keys($photo)])
            ->indexBy('id')
            ->all();
        foreach ($photo as $id => $attributes) {
            if (isset($attributes['name'])) {
                $models[$id]->name = $attributes['name'];
            }
            if (isset($attributes['description'])) {
                $models[$id]->description = $attributes['description'];
            }
            $models[$id]->save();
        }
        $resp = array();
        foreach ($models as $model) {
            $resp[] = array(
                'id' => $model->id,
                'rank' => $model->rank,
                'name' => (string)$model->name,
                'description' => (string)$model->description,
                'preview' => $model->getPreview(),
            );
        }

        return Json::encode($resp);
    }
}
