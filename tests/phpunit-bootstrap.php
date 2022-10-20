<?php
declare(strict_types=1);

/**
 * This file is part of the Phinx package.
 *
 * (c) Rob Morgan <robbym@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Phinx\Util\Util;

if (is_file('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
} else {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

if (getenv('MYSQL_DSN')) {
    define('MYSQL_DB_CONFIG', Util::parseDsn(getenv('MYSQL_DSN')));
}
