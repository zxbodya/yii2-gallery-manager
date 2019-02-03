<?php
namespace zxbodya\yii2\galleryManager\migrations;

use yii\db\Schema;
use yii\db\Migration;

class m190203_003227_add_column_alt_to_gallery_table extends Migration
{
    public $tableName = '{{%gallery_image}}';

    public function up()
    {
        $this->addColumn($this->tableName, 'alt', $this->text());
    }

    public function down()
    {
        $this->dropColumn($this->tableName, 'alt');
    }
}
