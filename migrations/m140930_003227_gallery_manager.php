<?php
namespace zxbodya\yii2\galleryManager\migrations;

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
                'ownerId' => Schema::TYPE_STRING . ' NOT NULL',
                'rank' => Schema::TYPE_INTEGER . ' NOT NULL DEFAULT 0',
                'name' => Schema::TYPE_STRING,
                'description' => Schema::TYPE_TEXT
            )
        );
    }

    public function down()
    {
        $this->dropTable($this->tableName);
    }
}
