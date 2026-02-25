<?php

namespace App\Services;

use MicrosoftAzure\Storage\Blob\BlobSharedAccessSignatureHelper;

class AzureBlobService
{
    protected $accountName;
    protected $accountKey;
    protected $container;

    public function __construct()
    {
        $this->accountName = env('AZURE_STORAGE_ACCOUNT');
        $this->accountKey = env('AZURE_STORAGE_KEY');
        $this->container = env('AZURE_STORAGE_CONTAINER');
    }

    public function generarUrlTemporal($rutaArchivo, $minutos = 20)
    {
        $helper = new BlobSharedAccessSignatureHelper(
            $this->accountName,
            $this->accountKey
        );

        // 👇 Restamos 5 minutos para evitar desfase de reloj
        $start = gmdate('Y-m-d\TH:i:s\Z', strtotime('-5 minutes'));
        $expiry = gmdate('Y-m-d\TH:i:s\Z', strtotime("+{$minutos} minutes"));

        $sasToken = $helper->generateBlobServiceSharedAccessSignatureToken(
            'b',
            "{$this->container}/{$rutaArchivo}",
            'r',
            $expiry,
            $start
        );

        return "https://{$this->accountName}.blob.core.windows.net/{$this->container}/{$rutaArchivo}?{$sasToken}";
    }
}