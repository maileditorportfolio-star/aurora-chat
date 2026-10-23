<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $u = App\Models\User::where('email','test2@aurora.dev')->first();
    $t = auth('api')->login($u);
    echo 'TOKEN_OK: '.substr((string)$t,0,25)."...\n";
} catch (Throwable $e) {
    echo 'ERR: '.get_class($e).': '.$e->getMessage()."\n";
    echo $e->getTraceAsString()."\n";
}
