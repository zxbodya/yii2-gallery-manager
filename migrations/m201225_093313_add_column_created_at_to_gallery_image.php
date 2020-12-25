<?php

use yii\db\Migration;

/**
 * Class m201225_093313_add_column_created_at_to_gallery_image
 */
class m201225_093313_add_column_created_at_to_gallery_image extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('gallery_image', 'created_at', $this->integer()->unsigned()->comment('Дата создания'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('gallery_image', 'created_at');
    }
}
