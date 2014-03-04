<?php
use Witti\Phonarc\Context\PhonarcContext;
require_once dirname(__DIR__) . '/autoload.php.dist';
PhonarcContext::loadConf(__DIR__ . '/Resources/dev.yml');
return PhonarcContext::factory(PhonarcContext::CURRENT_CONTEXT)->getHelperSet();