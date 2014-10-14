<?php
/**
 * Behavior for adding gallery to any model.
 *
 * @author Bogdan Savluk <savluk.bogdan@gmail.com>
 */
class GalleryBehavior extends CActiveRecordBehavior
{
    /** @var string Model attribute name to store created gallery id */
    public $idAttribute;
    /**
     * @var array Settings for image auto-generation
     * @example
     *  array(
     *       'small' => array(
     *              'resize' => array(200, null),
     *       ),
     *      'medium' => array(
     *              'resize' => array(800, null),
     *      )
     *  );
     */
    public $versions;
    /** @var boolean does images in gallery need names */
    public $name;
    /** @var boolean does images in gallery need descriptions */
    public $description;
    private $_gallery;

    /** Will create new gallery after save if no associated gallery exists */
    public function beforeSave($event)
    {
        parent::beforeSave($event);
        if ($event->isValid) {
            if (empty($this->getOwner()->{$this->idAttribute})) {
                $gallery = new Gallery();
                $gallery->name = $this->name;
                $gallery->description = $this->description;
                $gallery->versions = $this->versions;
                $gallery->save();

                $this->getOwner()->{$this->idAttribute} = $gallery->id;
            }
        }
    }

    /** Will remove associated Gallery before object removal */
    public function beforeDelete($event)
    {
        $gallery = $this->getGallery();
        if ($gallery !== null) {
            $gallery->delete();
        }
        parent::beforeDelete($event);
    }

    /** Method for changing gallery configuration and regeneration of images versions */
    public function changeConfig($force = false)
    {
        $gallery = $this->getGallery();
        if ($gallery == null) return;

        if ($gallery->versions_data != serialize($this->versions) || $force) {
            foreach ($gallery->galleryPhotos as $photo) {
                $photo->removeImages();
            }

            $gallery->name = $this->name;
            $gallery->description = $this->description;
            $gallery->versions = $this->versions;
            $gallery->save();

            foreach ($gallery->galleryPhotos as $photo) {
                $photo->updateImages();
            }
        }
    }

    /** @return Gallery Returns gallery associated with model */
    public function getGallery()
    {
        if (empty($this->_gallery)) {
            $this->_gallery = Gallery::model()->findByPk($this->getOwner()->{$this->idAttribute});
        }
        return $this->_gallery;
    }

    /** @return GalleryPhoto[] Photos from associated gallery */
    public function getGalleryPhotos()
    {
        $criteria = new CDbCriteria();
        $criteria->condition = 'gallery_id = :gallery_id';
        $criteria->params[':gallery_id'] = $this->getOwner()->{$this->idAttribute};
        $criteria->order = '`rank` asc';
        return GalleryPhoto::model()->findAll($criteria);
    }
}
