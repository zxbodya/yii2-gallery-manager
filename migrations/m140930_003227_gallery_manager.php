<?php
namespace zxbodya\yii2\galleryManager\migrations;

use yii\db\Schema;
use yii\db\Migration;

class m140930_003227_gallery_manager extends Migration
{
    public function up()
    {
        $this->createTable(
            '{{%gallery}}',
            array(
                'id' => Schema::TYPE_PK,
                'versions_data' => Schema::TYPE_TEXT . ' NOT NULL',
                'name' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT 1',
                'description' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT 1',
                'extension' => Schema::TYPE_STRING . '(10) NOT NULL DEFAULT \'jpg\'',
            )
        );

        $this->createTable(
            '{{%gallery_photo}}',
            array(
                'id' => Schema::TYPE_PK,
                'gallery_id' => Schema::TYPE_INTEGER . ' NOT NULL',
                'rank' => Schema::TYPE_INTEGER . ' NOT NULL DEFAULT 0',
                'name' => Schema::TYPE_STRING,
                'description' => Schema::TYPE_TEXT,
                'file_name' => Schema::TYPE_STRING,
            )
        );

        $this->addForeignKey(
            '{{%fk_gallery_has_photo}}',
            '{{%gallery_photo}}',
            'gallery_id',
            '{{%gallery}}',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }

    public function down()
    {
        $this->dropForeignKey('{{%fk_gallery_has_photo}}', '{{%gallery_photo}}');
        $this->dropTable('{{%gallery_photo}}');
        $this->dropTable('{{%gallery}}');
    }
}
