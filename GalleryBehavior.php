<?php

namespace zxbodya\yii2\galleryManager;

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use yii\base\Behavior;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\imagine\Image;

/**
 * Behavior for adding gallery to any model.
 *
 * @author Bogdan Savluk <savluk.bogdan@gmail.com>
 *
 * @property string $galleryId
 */
class GalleryBehavior extends Behavior
{
    /**
     * @var string Type name assigned to model in image attachment action
     */
    public $type;
    /**
     * @var ActiveRecord the owner of this behavior
     */
    public $owner;
    /**
     * Widget preview height
     * @var int
     */
    public $previewHeight = 200;
    /**
     * Widget preview width
     * @var int
     */
    public $previewWidth = 200;
    /**
     * Extension for saved images
     * @var string
     */
    public $extension;
    /**
     * Path to directory where to save uploaded images
     * @var string
     */
    public $directory;
    /**
     * Directory Url, without trailing slash
     * @var string
     */
    public $url;
    /**
     * @var array Functions to generate image versions
     * @note Be sure to not modify image passed to your version function,
     *       because it will be reused in all other versions,
     *       Before modification you should copy images as in examples below
     * @note 'preview' & 'original' versions names are reserved for image preview in widget
     *       and original image files, if it is required - you can override them
     * @example
     * [
     *  'small' => function ($img) {
     *      return $img
     *          ->copy()
     *          ->resize($img->getSize()->widen(200));
     *  },
     *  'medium' => function ($img) {
     *      $dstSize = $img->getSize();
     *      $maxWidth = 800;
     * ]
     */
    public $versions;
    /**
     * name of query param for modification time hash
     * to avoid using outdated version from cache - set it to false
     * @var string
     */
    public $timeHash = '_';
    public $hasName = true;
    public $hasDescription = true;
    private $_galleryId;
    private $_tableName = '{{%gallery_image}}';
    
    public function init()
    {
        parent::init();
        if (isset(\Yii::$app->params['zxbodya']['yii2']['galleryManager']['tableName'])) {
            $this->_tableName = \Yii::$app->params['zxbodya']['yii2']['galleryManager']['tableName'];
        }
    }

    /**
     * @param ActiveRecord $owner
     */
    public function attach($owner)
    {
        parent::attach($owner);
        if (!isset($this->versions['original'])) {
            $this->versions['original'] = function ($image) {
                return $image;
            };
        }
        if (!isset($this->versions['preview'])) {
            $this->versions['preview'] = function ($originalImage) {
                /** @var ImageInterface $originalImage */
                return $originalImage
                    ->thumbnail(new Box($this->previewWidth, $this->previewHeight));
            };
        }
    }

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
            ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
        ];
    }

    public function beforeDelete()
    {
        $images = $this->getImages();
        foreach ($images as $image) {
            $this->deleteImage($image->id);
        }
        $dirPath = $this->directory . '/' . $this->getGalleryId();
        @rmdir($dirPath);
    }

    public function afterFind()
    {
        $this->_galleryId = $this->getGalleryId();
    }

    public function afterUpdate()
    {
        $galleryId = $this->getGalleryId();
        if ($this->_galleryId != $galleryId) {
            $dirPath1 = $this->directory . '/' . $this->_galleryId;
            $dirPath2 = $this->directory . '/' . $galleryId;
            rename($dirPath1, $dirPath2);
        }
    }

    private $_images = null;

    /**
     * @return GalleryImage[]
     */
    public function getImages()
    {
        if ($this->_images === null) {
            $query = new \yii\db\Query();

            $imagesData = $query
                ->select(['id', 'name', 'description', 'rank'])
                ->from($this->_tableName)
                ->where(['type' => $this->type, 'ownerId' => $this->getGalleryId()])
                ->orderBy(['rank' => 'asc'])
                ->all();

            $this->_images = [];
            foreach ($imagesData as $imageData) {
                $this->_images[] = new GalleryImage($this, $imageData);
            }
        }

        return $this->_images;
    }

    private function getFileName($imageId, $version = 'original')
    {
        $galleryId = $this->getGalleryId();
        $ext = $this->extension;

        return $galleryId . '/' . $imageId . '/' . $version . '.' . $ext;
    }

    public function getUrl($imageId, $version = 'original')
    {
        $path = $this->getFilePath($imageId, $version);
        if (!file_exists($path)) {
            return null;
        }

        if (!empty($this->timeHash)) {

            $time = filemtime($path);
            $suffix = '?' . $this->timeHash . '=' . crc32($time);
        } else {
            $suffix = '';
        }

        return $this->url . '/' . $this->getFileName($imageId, $version) . $suffix;
    }

    public function getFilePath($imageId, $version = 'original')
    {
        return $this->directory . '/' . $this->getFileName($imageId, $version);
    }

    /**
     * Replace existing image by specified file
     *
     * @param $imageId
     * @param $path
     */
    public function replaceImage($imageId, $path)
    {
        $this->createFolders($this->getFilePath($imageId, 'original'));

        $originalImage = Image::getImagine()->open($path);
        //save image in original size

        //create image preview for gallery manager
        foreach ($this->versions as $version => $fn) {
            /** @var Image $image */

            call_user_func($fn, $originalImage)
                ->save($this->getFilePath($imageId, $version));
        }
    }

    private function removeFile($fileName)
    {
        if (file_exists($fileName)) {
            @unlink($fileName);
        }
    }

    public function getGalleryId()
    {
        $pk = $this->owner->getPrimaryKey();
        if (is_array($pk)) {
            throw new Exception('Composite pk not supported');
        } else {
            return $pk;
        }
    }


    private function createFolders($filePath)
    {
        $parts = explode('/', $filePath);
        // skip file name
        $parts = array_slice($parts, 0, count($parts) - 1);
        $i = 0;

        $path = implode('/', array_slice($parts, 0, count($parts) - $i));
        while (!file_exists($path)) {
            $i++;
            $path = implode('/', array_slice($parts, 0, count($parts) - $i));
        }
        $i--;
        $path = implode('/', array_slice($parts, 0, count($parts) - $i));
        while ($i >= 0) {
            mkdir($path, 0777);
            $i--;
            $path = implode('/', array_slice($parts, 0, count($parts) - $i));
        }
    }

    /////////////////////////////// ========== Public Actions ============ ///////////////////////////
    public function deleteImage($imageId)
    {
        foreach ($this->versions as $version => $fn) {
            $filePath = $this->getFilePath($imageId, $version);
            $this->removeFile($filePath);
        }
        $filePath = $this->getFilePath($imageId, 'original');
        $parts = explode('/', $filePath);
        $parts = array_slice($parts, 0, count($parts) - 1);
        $dirPath = implode('/', $parts);
        @rmdir($dirPath);

        $db = \Yii::$app->db;
        $db->createCommand()
            ->delete(
                $this->_tableName,
                ['id' => $imageId]
            )->execute();
    }

    public function deleteImages($imageIds)
    {
        foreach ($imageIds as $imageId) {
            $this->deleteImage($imageId);
        }
        if ($this->_images !== null) {
            $removed = array_combine($imageIds, $imageIds);
            $this->_images = array_filter(
                $this->_images,
                function ($image) use (&$removed) {
                    return !isset($removed[$image->id]);
                }
            );
        }
    }

    public function addImage($fileName)
    {
        $db = \Yii::$app->db;
        $db->createCommand()
            ->insert(
                $this->_tableName,
                [
                    'type' => $this->type,
                    'ownerId' => $this->getGalleryId()
                ]
            )->execute();

        $id = $db->getLastInsertID();
        $db->createCommand()
            ->update(
                $this->_tableName,
                ['rank' => $id],
                ['id' => $id]
            )->execute();

        $this->replaceImage($id, $fileName);

        $galleryImage = new GalleryImage($this, ['id' => $id]);

        if ($this->_images !== null) {
            $this->_images[] = $galleryImage;
        }

        return $galleryImage;
    }


    public function arrange($order)
    {
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
            $res[$k] = $orders[$i];

            \Yii::$app->db->createCommand()
                ->update(
                    $this->_tableName,
                    ['rank' => $orders[$i]],
                    ['id' => $k]
                )->execute();

            $i++;
        }

        // todo: arrange images if presented
        return $order;
    }

    /**
     * @param array $imagesData
     *
     * @return GalleryImage[]
     */
    public function updateImagesData($imagesData)
    {
        $imageIds = array_keys($imagesData);
        $imagesToUpdate = [];
        if ($this->_images !== null) {
            $selected = array_combine($imageIds, $imageIds);
            foreach ($this->_images as $img) {
                if (isset($selected[$img->id])) {
                    $imagesToUpdate[] = $selected[$img->id];
                }
            }
        } else {
            $rawImages = (new Query())
                ->select(['id', 'name', 'description', 'rank'])
                ->from($this->_tableName)
                ->where(['type' => $this->type, 'ownerId' => $this->getGalleryId()])
                ->andWhere(['in', 'id', $imageIds])
                ->orderBy(['rank' => 'asc'])
                ->all();
            foreach ($rawImages as $image) {
                $imagesToUpdate[] = new GalleryImage($this, $image);
            }
        }


        foreach ($imagesToUpdate as $image) {
            if (isset($imagesData[$image->id]['name'])) {
                $image->name = $imagesData[$image->id]['name'];
            }
            if (isset($imagesData[$image->id]['description'])) {
                $image->description = $imagesData[$image->id]['description'];
            }
            \Yii::$app->db->createCommand()
                ->update(
                    $this->_tableName,
                    ['name' => $image->name, 'description' => $image->description],
                    ['id' => $image->id]
                )->execute();
        }

        return $imagesToUpdate;
    }
}
