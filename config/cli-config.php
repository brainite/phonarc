<?php
/*
 * This file is part of the Phonarc package.
 *
 * (c) CPNP
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Phonarc\Context\PhonarcContext;
require_once dirname(__DIR__) . '/autoload.php.dist';
PhonarcContext::loadConf(__DIR__ . '/Resources/dev.yml');
return PhonarcContext::factory(PhonarcContext::CURRENT_CONTEXT)->getHelperSet();