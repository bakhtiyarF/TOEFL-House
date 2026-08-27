<?php
// Bootstraps the Laravel app for ad-hoc PHP scripts run through tools/artisan.mjs:
//   node tools/artisan.mjs /path/to/script.php
require '/home/user/TOEFL-House/toefl-house-v3/server/vendor/autoload.php';

$app = require '/home/user/TOEFL-House/toefl-house-v3/server/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
