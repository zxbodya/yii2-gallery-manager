<?php

namespace zxbodya\yii2\galleryManager;

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use yii\base\Behavior;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\helpers\FileHelper;
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
     * Glue used to implode composite primary keys
     * @var string
     */
    public $pkGlue = '_';
    /**
     * @var string Type name assigned to model in image attachment action
     * @see     GalleryManagerAction::$types
     * @example $type = 'Post' where 'Post' is the model name
     */
    public $type;
    /**
     * @var ActiveRecord the owner of this behavior
     * @example $owner = Post where Post is the ActiveRecord with GalleryBehavior attached under public function behaviors()
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

    /**
     * Used by GalleryManager
     * @var bool
     * @see GalleryManager::run
     */
    public $hasName = true;
    /**
     * Used by GalleryManager
     * @var bool
     * @see GalleryManager::run
     */
    public $hasDescription = true;

    /**
     * Enable video
     * @var bool
     */
    public $videoSupport = true;

    /**
     * Watermark flag for video preview
     * @var bool
     */
    public $videoWatermark = false;

    /**
     * @var string Table name for saving gallery images meta information
     */
    public $tableName = '{{%gallery_image}}';
    protected $_galleryId;

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

    protected $_images = null;

    /**
     * @return GalleryImage[]
     */
    public function getImages()
    {
        if ($this->_images === null) {
            $query = new \yii\db\Query();

            $imagesData = $query
                ->select(['id', 'name', 'description', 'rank'])
                ->from($this->tableName)
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

    protected function getFileName($imageId, $version = 'original')
    {
        return implode(
            '/',
            [
                $this->getGalleryId(),
                $imageId,
                $version . '.' . $this->extension,
            ]
        );
    }

    protected function getFolderName($imageId)
    {
        return implode(
            '/',
            [
                $this->getGalleryId(),
                $imageId
            ]
        );
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

    public function getVideoUrl($imageId)
    {
        $dir = $this->getFolderPath($imageId);
        $files = scandir($dir);

        $search = 'video.';
        $filename = '';

        foreach ($files as $index => $file){
            if (strpos($file, $search) !== false) $filename = $file;
        }

        $path = $dir . '/' . $filename;

        if (!file_exists($path)) {
            return null;
        }

        if (!empty($this->timeHash)) {

            $time = filemtime($path);
            $suffix = '?' . $this->timeHash . '=' . crc32($time);
        } else {
            $suffix = '';
        }

        return $this->url . '/' .  $this->getFolderName($imageId) . '/' . $filename . $suffix;
    }

    public function getFilePath($imageId, $version = 'original')
    {
        return $this->directory . '/' . $this->getFileName($imageId, $version);
    }

    public function getFolderPath($imageId)
    {
        return $this->directory . '/' . $this->getFolderName($imageId);
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

        $mime_type = FileHelper::getMimeType($path);
        $extension = explode('/', $mime_type )[1];

        if (strpos($mime_type, 'video/') !== false && $this->videoSupport) {
            // Видео

            $thumbnailPath = $this->getFilePath($imageId, 'original');
            $videoPath = $this->getFolderPath($imageId) . '/' . 'video.' . $extension;

            // Перемещаем видео файл
            rename($path, $videoPath);

            // Скриншот
            \Yii::$app->ffmpeg->ffmpeg([
                'type' => 'video',
                'input_file' => $videoPath,
                'thumbnail_image' => $thumbnailPath,
                'thumbnail_generation' => 'yes',
            ]);

            // Watermark
            if ($this->videoWatermark) {
                $watermarkImage = '@zxbodya/yii2/galleryManager/assets/video-play-watermark_128.png';
                $watermarkSize = getimagesize($thumbnailPath); // Определяем размер картинки
                $watermarkWidth = $watermarkSize[0]; // Ширина картинки
                $watermarkHeight = $watermarkSize[1]; // Высота картинки
                $watermarkPositionLeft = $watermarkWidth / 2 - 64; // Новая позиция watermark по оси X (горизонтально)
                $watermarkPositionTop = $watermarkHeight / 2 - 64;  // Новая позиция watermark по оси Y (вертикально)
                if ($watermarkPositionLeft < 0) $watermarkPositionLeft = 0;
                if ($watermarkPositionTop < 0) $watermarkPositionTop = 0;

                Image::watermark($thumbnailPath, $watermarkImage, [$watermarkPositionLeft, $watermarkPositionTop])->save(\Yii::getAlias($thumbnailPath));
            }
            // Остальные размеры
            $thumbnailImage = Image::getImagine()->open($thumbnailPath);

            foreach ($this->versions as $version => $fn) {
                /** @var ImageInterface $image */

                $image = call_user_func($fn, $thumbnailImage);
                if (is_array($image)) {
                    list($image, $options) = $image;
                } else {
                    $options = [];
                }

                $image
                    ->save($this->getFilePath($imageId, $version), $options);
            }

        } else {
            // Изображение

            $originalImage = Image::getImagine()->open($path);
            //save image in original size

            //create image preview for gallery manager
            foreach ($this->versions as $version => $fn) {
                /** @var ImageInterface $image */

                $image = call_user_func($fn, $originalImage);
                if (is_array($image)) {
                    list($image, $options) = $image;
                } else {
                    $options = [];
                }

                $image
                    ->save($this->getFilePath($imageId, $version), $options);
            }
        }


    }

    private function removeFile($fileName)
    {
        return FileHelper::unlink($fileName);
    }

    /**
     * Get Gallery Id
     *
     * @return mixed as string or integer
     * @throws Exception
     */
    public function getGalleryId()
    {
        $pk = $this->owner->getPrimaryKey();
        if (is_array($pk)) {
            return implode($this->pkGlue, $pk);
        } else {
            return $pk;
        }
    }


    private function createFolders($filePath)
    {
        return FileHelper::createDirectory(FileHelper::normalizePath(dirname($filePath)), 0777);
    }

    /////////////////////////////// ========== Public Actions ============ ///////////////////////////
    public function deleteImage($imageId)
    {
        foreach ($this->versions as $version => $fn) {
            $filePath = $this->getFilePath($imageId, $version);
            $this->removeFile($filePath);
        }

        $dir = $this->getFolderPath($imageId);
        $files = scandir($dir);


        // Удаляем видео
        $search = 'video.';
        $filename = false;
        foreach ($files as $index => $file){
            if (strpos($file, $search) !== false) $filename = $file;
        }
        $path = $dir . '/' . $filename;
        if ($filename !== false){
            $this->removeFile($path);
        }

        $filePath = $this->getFilePath($imageId, 'original');
        $parts = explode('/', $filePath);
        $parts = array_slice($parts, 0, count($parts) - 1);
        $dirPath = implode('/', $parts);
        @rmdir($dirPath);

        $db = \Yii::$app->db;
        $db->createCommand()
            ->delete(
                $this->tableName,
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
                $this->tableName,
                [
                    'type' => $this->type,
                    'ownerId' => $this->getGalleryId()
                ]
            )->execute();

        $id = $db->getLastInsertID('gallery_image_id_seq');
        $db->createCommand()
            ->update(
                $this->tableName,
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
        $orders = [];
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
        $res = [];
        foreach ($order as $k => $v) {
            $res[$k] = $orders[$i];

            \Yii::$app->db->createCommand()
                ->update(
                    $this->tableName,
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
                ->from($this->tableName)
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
                    $this->tableName,
                    ['name' => $image->name, 'description' => $image->description],
                    ['id' => $image->id]
                )->execute();
        }

        return $imagesToUpdate;
    }

    /**
     * @param bool $direct 1 - right, 0 - left, 2 - auto
     *
     * @return GalleryImage[]
     */
    public function rotateImages($imagesData, $direct)
    {
        set_time_limit(600);

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
                ->from($this->tableName)
                ->where(['type' => $this->type, 'ownerId' => $this->getGalleryId()])
                ->andWhere(['in', 'id', $imageIds])
                ->orderBy(['rank' => 'asc'])
                ->all();
            foreach ($rawImages as $image) {
                $imagesToUpdate[] = new GalleryImage($this, $image);
            }
        }

        foreach ($imagesToUpdate as $image) {
            /** @var GalleryImage $image */
            $img = Image::getImagine()->open($this->getFilePath($image->id));

            if ($direct == 2 )
                $img = Image::autorotate($img);
            else {
                $angle = ($direct == 1) ? 90 : -90;
                $img->rotate($angle);
            }

            $img->save($this->getFilePath($image->id));
            $this->updateImage($image->id);
        }

        return $imagesToUpdate;
    }

    public function updateImage($imageId,$oldExtension = null)
    {
        $id = $imageId;

        if ($oldExtension !== null) {
            $newExtension = $this->extension;
            $this->extension = $oldExtension;
            $originalImage = Image::getImagine()
                ->open($this->getFilePath($id, 'original'));
            foreach ($this->versions as $version => $fn) {
                $this->removeFile($this->getFilePath($id, $version));
            }
            $this->extension = $newExtension;
            $originalImage->save($this->getFilePath($id, 'original'));
        } else {
            $originalImage = Image::getImagine()
                ->open($this->getFilePath($id, 'original'));
        }

        foreach ($this->versions as $version => $fn) {
            if ($version !== 'original') {
                $this->removeFile($this->getFilePath($id, $version));
                /** @var ImageInterface $image */
                $image = call_user_func($fn, $originalImage);
                if (is_array($image)) {
                    list($image, $options) = $image;
                } else {
                    $options = [];
                }
                $image->save($this->getFilePath($id, $version), $options);
            }
        }
    }

    /**
     * Regenerate image versions
     * Should be called in migration on every model after changes in versions configuration
     *
     * @param string|null $oldExtension
     */
    public function updateImages($oldExtension = null)
    {
        $ids = array_map(function ($image) {
            /** @var GalleryImage $image */
            return $image->id;
        }, $this->getImages());

        foreach ($ids as $id) {
            if ($oldExtension !== null) {
                $newExtension = $this->extension;
                $this->extension = $oldExtension;
                $originalImage = Image::getImagine()
                    ->open($this->getFilePath($id, 'original'));
                foreach ($this->versions as $version => $fn) {
                    $this->removeFile($this->getFilePath($id, $version));
                }
                $this->extension = $newExtension;
                $originalImage->save($this->getFilePath($id, 'original'));
            } else {
                $originalImage = Image::getImagine()
                    ->open($this->getFilePath($id, 'original'));
            }

            foreach ($this->versions as $version => $fn) {
                if ($version !== 'original') {
                    $this->removeFile($this->getFilePath($id, $version));
                    /** @var ImageInterface $image */
                    $image = call_user_func($fn, $originalImage);
                    if (is_array($image)) {
                        list($image, $options) = $image;
                    } else {
                        $options = [];
                    }
                    $image->save($this->getFilePath($id, $version), $options);
                }
            }
        }
    }
}
