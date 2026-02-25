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
        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');
        }
        // Registramos el driver 'azure-blob'
        Storage::extend('azure-blob', function ($app, $config) {

            // Crear el cliente de Azure Blob Storage (BlobRestProxy)
            $client = BlobRestProxy::createBlobService($config['connection_string']);

            // Crear el adaptador de Azure para Flysystem
            $adapter = new AzureBlobStorageAdapter(
                $client,                      // Pasamos el cliente a AzureBlobStorageAdapter
                $config['container']          // El contenedor que se va a usar
            );

            // Crear la instancia de Filesystem de Flysystem con el adaptador
            $filesystem = new Filesystem($adapter);

            // Devolvemos la instancia de FilesystemAdapter correctamente
            return new \Illuminate\Filesystem\FilesystemAdapter($filesystem, $adapter, $config);
        });
    }
}