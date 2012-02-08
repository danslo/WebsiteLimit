<?php

$installer = $this;

$installer->startSetup();

$installer->run("ALTER TABLE {$this->getTable('admin_user')} ADD `website_limit` INT NULL DEFAULT NULL");

$installer->endSetup();
