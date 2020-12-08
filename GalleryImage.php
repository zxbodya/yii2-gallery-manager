<?php

namespace zxbodya\yii2\galleryManager;

class GalleryImage
{
    public $name;
    public $description;
    public $id;
    public $rank;
    /**
     * @var GalleryBehavior
     */
    protected $galleryBehavior;

    /**
     * @param GalleryBehavior $galleryBehavior
     * @param array           $props
     */
    function __construct(GalleryBehavior $galleryBehavior, array $props)
    {

        $this->galleryBehavior = $galleryBehavior;

        $this->name = isset($props['name']) ? $props['name'] : '';
        $this->description = isset($props['description']) ? $props['description'] : '';
        $this->id = isset($props['id']) ? $props['id'] : '';
        $this->rank = isset($props['rank']) ? $props['rank'] : '';
    }

    /**
     * @param string $version
     *
     * @return string
     */
    public function getUrl($version)
    {
        if ($version == 'video' && $this->isVideo())
            return $this->galleryBehavior->getVideoUrl($this->id);
        else
            return $this->galleryBehavior->getUrl($this->id, $version);
    }

    public function isVideo(){
            $dir = $this->galleryBehavior->getFolderPath($this->id);
            $files = scandir($dir);

            $search = 'video.';
            $result = false;

            foreach ($files as $index => $file){
                if (strpos($file, $search) !== false) $result = true;
            }

            return $result;
    }
}
