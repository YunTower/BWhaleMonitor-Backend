<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use app\controller\ConfigController;
use app\controller\InstallController;
use Webman\Route;

Route::get('/install/env/check', [InstallController::class, 'envCheck']);

Route::group('/config/edit', function () {
    Route::patch('/username', [ConfigController::class, 'editUsername']);
    Route::patch('/password', [ConfigController::class, 'editPassword']);
});