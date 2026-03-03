<?php

namespace App\Http\Controllers;

use App\Models\Documentos;
use App\Models\Investigaciones;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentosController extends Controller
{
    // Función auxiliar para mantener compatibilidad con tus nombres de archivo de firma
    private function getGUID(){
        if (function_exists('com_create_guid')){
            return trim(com_create_guid(), '{}');
        }
        else {
            mt_srand((double)microtime()*10000);
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);
            $uuid = substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12);
            return $uuid;
        }
    }

    /**
     * Subida principal de documentos (Investigación y Firmas)
     */
    public function store(Request $request)
    {
        // CASO A: Subida de Firma de Usuario
        if ($request->has('type') && $request->type == 'firma') {
            $user = User::find($request->id);
            if ($request->hasFile('firma')) {
                $file = $request->file('firma');
                $nombreArchivo = $this->getGUID() . '.jpg';
                
                // Guardamos en carpeta 'firmas' dentro de Azure
                Storage::disk('azure')->putFileAs('firmas', $file, $nombreArchivo);
                
                $user->update(['firma' => $nombreArchivo]);
            }       
            return back()->with('info', 'Firma cargada correctamente.');
        } 
        
        // CASO B: Subida de archivos de Investigación (desde el formulario que vimos)
        else {
            $investigacion = Investigaciones::findOrFail($request->id);
            
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $nombreArchivo = $file->getClientOriginalName();
                    
                    // RUTA UNIFICADA: investigaciones/radicado/CARPETA
                    // Quitamos el '/investigacion' extra para que el visor lo encuentre directo
                    $rutaDestino = 'investigaciones/radicado/' . $investigacion->nombreCarpeta;

                    Storage::disk('azure')->putFileAs($rutaDestino, $file, $nombreArchivo);
                }
            }
        }
        return back()->with('info', 'Documentos cargados correctamente.');
    }

    /**
     * Subida de Soportes Fotográficos (Categorizados)
     */
    public function DocumentosAnexosStore(Request $request)
    {
        $investigacion = Investigaciones::findOrFail($request->id);
        
        // Definimos la carpeta base de soportes para esta investigación
        $rutaBaseSoportes = 'investigaciones/radicado/' . $investigacion->nombreCarpeta . '/soporteFotografico';
        
        // Obtenemos el conteo actual para no sobrescribir nombres
        $num_total = count(Storage::disk('azure')->allFiles($rutaBaseSoportes));

        // Mapeo de inputs del formulario vs prefijo de archivo
        $categorias = [
            'inmuebles'    => 'inmueble_',
            'servicios'    => 'servicios_',
            'pertenencias' => 'pertenencias_',
            'clinica'      => 'clinica_',
            'familiares'   => 'familiares_',
            'investigador' => 'investigador_',
            'basesdedatos' => 'basesdedatos_',
        ];

        foreach ($categorias as $input => $prefijo) {
            if ($request->hasFile($input)) {
                foreach ($request->file($input) as $file) {
                    $extension = $file->getClientOriginalExtension();
                    $nombreArchivo = $prefijo . (++$num_total) . '.' . $extension;
                    
                    Storage::disk('azure')->putFileAs($rutaBaseSoportes, $file, $nombreArchivo);
                }
            }
        }

        return back()->with('info', 'Soportes fotográficos cargados correctamente.');
    }

    /**
     * Eliminación de archivos en Azure
     */
    public function eliminarSoporte(Request $request)
    {
        $rutaArchivo = $request->input('ruta_archivo'); // Debe ser la ruta completa en el contenedor
        
        try {
            if (Storage::disk('azure')->exists($rutaArchivo)) {
                Storage::disk('azure')->delete($rutaArchivo);
                return redirect()->back()->with('success', 'Archivo eliminado correctamente.');
            }
            return redirect()->back()->with('error', 'El archivo no existe en el servidor.');
        } catch (Exception $e) {
            Log::error('Error al eliminar en Azure: ' . $e->getMessage());
            return redirect()->back()->with('error', 'No se pudo eliminar el archivo.');
        }
    }

    /**
     * VISOR DE PDF (Optimizado con cURL para Azure App Service)
     */
    public function ver($carpeta, $archivo)
    {
        $ruta = "investigaciones/radicado/{$carpeta}/{$archivo}";
        $azureService = new \App\Services\AzureBlobService();
        $urlTemporal = $azureService->generarUrlTemporal($ruta);

        // 1. Detectar la extensión y asignar el Content-Type correcto
        $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
        
        $tiposMime = [
            'pdf'  => 'application/pdf',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'txt'  => 'text/plain',
        ];

        // Si la extensión no está en la lista, usamos un tipo genérico de descarga
        $contentType = $tiposMime[$extension] ?? 'application/octet-stream';

        return response()->stream(function () use ($urlTemporal) {
            if (ob_get_level()) ob_end_clean();
            
            $ch = curl_init($urlTemporal);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_BUFFERSIZE, 1024 * 8);
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
                echo $data;
                flush();
                return strlen($data);
            });
            curl_exec($ch);
            curl_close($ch);
        }, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'inline; filename="' . $archivo . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}