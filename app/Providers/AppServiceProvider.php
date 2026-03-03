<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use League\Flysystem\Filesystem;
use Illuminate\Support\Facades\URL;
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No es necesario registrar nada en este caso
    }

    public function boot(): void
    {
        // 1. Forzar HTTPS si NO estamos en local
        // Usamos config() en lugar de env() porque es más seguro (cacheable)
        if (config('app.env') !== 'local') {
            \URL::forceScheme('http');
        }

        // 2. Registro del driver de Azure (Tu lógica existente corregida)
        \Storage::extend('azure', function ($app, $config) {
            $client = \MicrosoftAzure\Storage\Blob\BlobRestProxy::createBlobService($config['connection_string']);
            $adapter = new \League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter(
                $client,
                $config['container']
            );

            $filesystem = new \League\Flysystem\Filesystem($adapter);

            return new \Illuminate\Filesystem\FilesystemAdapter($filesystem, $adapter, $config);
        });
    }
}