<?php

namespace mixartemev\yii2\galleryManager;

class GalleryImage
{
    public $name;
    public $description;
    public $id;
    public $ownerid;
    public $rank;
    public $disable;
    /**
     * @var GalleryBehavior
     */
    public $galleryBehavior;

    /**
     * @param GalleryBehavior $galleryBehavior
     * @param array           $props
     */
    function __construct(GalleryBehavior $galleryBehavior, array $props)
    {

        $this->galleryBehavior = $galleryBehavior;
        
        $this->name = isset($props['name']) ? $props['name'] : '';
        $this->type = isset($props['type']) ? $props['type'] : '';
        $this->description = isset($props['description']) ? $props['description'] : '';
        $this->id = isset($props['id']) ? $props['id'] : '';
        $this->ownerid = isset($props['ownerid']) ? $props['ownerid'] : '';
        $this->rank = isset($props['rank']) ? $props['rank'] : '';
        $this->disable = isset($props['disable']) ? $props['disable'] : '';
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
