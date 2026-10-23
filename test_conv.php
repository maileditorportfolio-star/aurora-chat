<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
try {
    $service = $app->make(App\Services\ConversationService::class);
    $conv = $service->createDM(3, 4);
    echo "conv_id: ".$conv->id.PHP_EOL;
} catch (Throwable $e) {
    echo "ERR: ".get_class($e).": ".$e->getMessage().PHP_EOL;
    echo $e->getTraceAsString().PHP_EOL;
}
