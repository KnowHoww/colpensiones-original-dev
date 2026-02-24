<?php

namespace App\Http\Controllers;

use App\Models\CentroCostos;
use App\Models\InvestigacionAuxilioFunerario;
use App\Models\InvestigacionesReportes;
use App\Models\InvestigacionesBeneficiariosReportes;
use App\Models\Servicios;
use App\Models\States;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class InformesController extends Controller
{
    public function informeInvestigacionExcel()
    {
        $fecha_actual = now()->format('Y-m-d');
        $nombre_archivo = 'informeInvestigaciones_' . $fecha_actual . '.xlsx';
        return Excel::download(new \App\Exports\InformeInvestigaciones, $nombre_archivo);
    }

    public function validar(Request $request)
    {
        $data = InvestigacionesReportes::select(
            'investigaciones.*',
            'investigador.name as name_investigador',
            'investigador.lastname as lastname_investigador',
            'coordinador.name as name_coordinador',
            'coordinador.lastname as lastname_coordinador',
            'analista.name as name_analista',
            'analista.lastname as lastname_analista',
            'analistaColpensiones.name as name_analistaColpensiones',
            'analistaColpensiones.lastname as lastname_analistaColpensiones',
            'aprobadorColpensiones.name as name_aprobadorColpensiones',
            'aprobadorColpensiones.lastname as lastname_aprobadorColpensiones'
        )
            ->leftJoin('investigaciones_observaciones_estados', 'investigaciones_observaciones_estados.idInvestigacion', '=', 'investigaciones.id')
            ->leftJoin('investigacion_asignacion', 'investigacion_asignacion.idInvestigacion', '=', 'investigaciones.id')
            ->leftJoin('users as analista', 'analista.id', '=', 'investigacion_asignacion.Analista')
            ->leftJoin('users as coordinador', 'coordinador.id', '=', 'investigacion_asignacion.CoordinadorRegional')
            ->leftJoin('users as investigador', 'investigador.id', '=', 'investigacion_asignacion.Investigador')
            ->leftJoin('users as analistaColpensiones', 'analistaColpensiones.id', '=', 'investigaciones.analista')
            ->leftJoin('users as aprobadorColpensiones', 'aprobadorColpensiones.id', '=', 'investigaciones.aprobador');

        if ((request()->has('fecha_inicio') && request('fecha_inicio') != null) && (request()->has('fecha_fin')  && request('fecha_fin') != null)) {
            $data->whereBetween('MarcaTemporal', [request('fecha_inicio'), request('fecha_fin')]);
        }

        if (request()->has('estado') && request('estado') != 0) {
            $data->where('investigaciones.estado', request('estado'));
        }

        if (request()->has('centroCosto') && request('centroCosto') != 0) {
            $data->where('investigaciones.CentroCosto', request('centroCosto'));
        }

        if (request()->has('tipo_investigacion') && request('tipo_investigacion') != 0) {
            $data->where('investigaciones.TipoInvestigacion', request('tipo_investigacion'));
        }

        return $data = $data->groupBy('investigaciones.id')->get();
    }

public function informeInvestigacionFiltroExcel(Request $request)
{
    // Parámetros de emergencia (se mantienen igual)
    ini_set('memory_limit', '3072M');
    set_time_limit(2400);

    $fecha = now()->format('Y-m-d_His');
    $nombre_archivo = 'informe_investigaciones_' . $fecha . '.xlsx';
    
    // 1. Guardar el archivo en el disco 'public' (Se mantiene igual)
    Excel::store(new \App\Exports\InformeInvestigaciones($request), $nombre_archivo, 'public');

    // 2. MODIFICACIÓN: En lugar de retornar un JSON, retornamos la descarga directa
    // storage_path apunta a la ubicación física en el servidor
    $ruta_fisica = storage_path('app/public/' . $nombre_archivo);

    return response()->download($ruta_fisica)->deleteFileAfterSend(true);
}

    public function informeInvestigacionFiltroExcelOperaciones(Request $request)
    {
        $fecha_actual = now()->format('Y-m-d');
        $nombre_archivo = 'informeInvestigaciones_filtros_' . $fecha_actual . '.xlsx';
        return Excel::download(new \App\Exports\InformeInvestigacionesOperaciones($request), $nombre_archivo);
    }

    public function generarInformeInvestigacionesFiltrosCreador(Request $request)
    {
        $fecha_actual = now()->format('Y-m-d');
        $nombre_archivo = 'informeInvestigaciones_filtros_' . $fecha_actual . '.xlsx';
        return Excel::download(new \App\Exports\InformeMisInvestigaciones($request), $nombre_archivo);
    }

    public function informeInvestigacionFiltroAprobador(Request $request)
    {
        $fecha_actual = now()->format('Y-m-d');
        $nombre_archivo = 'informeInvestigaciones_filtros_' . $fecha_actual . '.xlsx';
        return Excel::download(new \App\Exports\InformeInvestigaciones($request), $nombre_archivo);
    }

    public function informeInvestigacionCompletoExcel($estado = null)
    {
        $campo = null;
        if ($estado != null) {
            $campo = States::find($estado);
        }

        $nombre_estado = $campo ? $campo->name : 'sin_estado';

        $fecha_actual = now()->format('Y-m-d');
        $nombre_archivo = 'informeInvestigaciones_' . $fecha_actual . '_' . $nombre_estado . '.xlsx';
        return Excel::download(new \App\Exports\InformeInvestigacionesCompleto($estado), $nombre_archivo);
    }

    public function generarTrazabilidadInvestigacion($id)
    {
        return Excel::download(new \App\Exports\InformeTrazabilidadInvestigacion($id), 'informeTrazabilidadId#' . $id . '.xlsx');
    }

    public function informeInvestigacionEstadoExcel($id)
    {
        $fecha_actual = now()->format('Y-m-d');
        $nombre_archivo = 'informeInvestigaciones_' . $id . '_' . $fecha_actual . '.xlsx';
        return Excel::download(new \App\Exports\InformeInvestigacionesFinalizadas($id), $nombre_archivo);
    }
}
