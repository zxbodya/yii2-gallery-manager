<?php

use yii\db\Schema;
use yii\db\Migration;

class m140930_003227_gallery_manager extends Migration
{
    private $_tableName = '{{%gallery_image}}';
    
    public function init()
    {
        parent::init();
        if (isset(Yii::$app->params['zxbodya']['yii2']['galleryManager']['tableName'])) {
            $this->_tableName = Yii::$app->params['zxbodya']['yii2']['galleryManager']['tableName'];
        }
    }
    
    public function up()
    {
        $this->createTable(
            $this->_tableName,
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
        $this->dropTable($this->_tableName);
    }
}
