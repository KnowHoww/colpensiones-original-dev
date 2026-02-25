<div class="accordion-item my-2">
    <h2 class="accordion-header" id="heading4">
        <button class="accordion-button collapsed" type="button"
            data-bs-toggle="collapse" data-bs-target="#collapse4"
            aria-expanded="false" aria-controls="collapse4">
            documentos de investigación
        </button>
    </h2>

    <div id="collapse4" class="accordion-collapse collapse"
        aria-labelledby="heading4"
        data-bs-parent="#accordionExample">

        <div class="accordion-body">
            <table class="table datatable table-striped" style="width: 100%">
                <thead class="bg-primary text-white">
                    <tr>
                        <th>Guia</th>
                        <th>Documento</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>

                    @foreach ($documentos as $documento)
                        @php
                            $nombreArchivo = $documento['nombre'];
                            $ruta = $documento['ruta'];

                            $partes = explode('/', $ruta);

                            // radicado/9997_14/investigacion/archivo.pdf
                            $carpeta = $partes[1];
                            $archivo = end($partes);

                            $puedeEliminar =
                                in_array($investigacion->estado, [3,5,6,11]) &&
                                Auth::user()->centroCosto == 1;

                            $esInvestigacion = str_contains($ruta, 'investigacion');
                            $esSoporte = str_contains($ruta, 'soporteFotografico');
                        @endphp

                        <tr>
                            <td>
                                @if ($esInvestigacion)
                                    Investigación
                                @elseif ($esSoporte)
                                    Soporte fotografico
                                @else
                                    Bizagi
                                @endif
                            </td>

                            <td>{{ $nombreArchivo }}</td>

                            <td>
                                <a class="btn btn-primary"
                                target="_blank"
                                href="{{ route('documento.ver', ['carpeta' => $carpeta, 'archivo' => $archivo]) }}">
                                    Ver anexo
                                </a>

                                @if (($esInvestigacion || $esSoporte) && $puedeEliminar)
                                    @can('formularios.eliminar.documento')
                                        <form action="{{ route('eliminarSoporte') }}"
                                            method="POST"
                                            style="display:inline;">
                                            @csrf
                                            <input type="hidden"
                                                name="ruta_archivo"
                                                value="{{ $nombreArchivo }}">
                                            <button type="submit"
                                                    class="btn btn-danger"
                                                    onclick="return confirm('¿Estás seguro de que deseas eliminar este archivo?')">
                                                Eliminar
                                            </button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>