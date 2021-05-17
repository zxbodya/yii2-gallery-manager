<?php
namespace zxbodya\yii2\galleryManager\migrations;

use yii\db\Schema;
use yii\db\Migration;

class m210515_022746_add_image_extension extends Migration
{
    public $tableName = '{{%gallery_image}}';

    public function up()
    {
        $this->addColumn($this->tableName, 'extension', Schema::TYPE_STRING);
    }
    
    public function down()
    {
        $this->dropColumn($this->tableName, 'extension');
    }
}
