<?php
namespace mixartemev\yii2\galleryManager\migrations;

use yii\db\Schema;
use yii\db\Migration;

class m140930_003227_gallery_manager extends Migration
{
    public $tableName = '{{%gallery_image}}';

    public function up()
    {
        $this->createTable(
            $this->tableName,
            array(
                'id' => Schema::TYPE_PK,
                'type' => Schema::TYPE_STRING,
                'ownerId' => Schema::TYPE_INTEGER . ' NOT NULL',
                'rank' => Schema::TYPE_INTEGER . ' NOT NULL DEFAULT 0',
                'name' => Schema::TYPE_STRING,
                'description' => Schema::TYPE_SMALLINT,
                'disable' => Schema::TYPE_BOOLEAN . ' DEFAULT 0',
            )
        );

        // creates index for column `tower_id`
        $this->createIndex(
            'idx-gallery_image-ownerId',
            'gallery_image',
            'ownerId'
        );
    }

    public function down()
    {
        // drops foreign key for table `tower`
        $this->dropForeignKey(
            'idx-gallery_image-ownerId',
            'gallery_image'
        );

        $this->dropTable($this->tableName);
    }
}
