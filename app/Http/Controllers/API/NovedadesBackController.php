<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Novedad;


class NovedadesBackController extends Controller
{
    public function getNovedades()
    {
      $novedades = Novedad::orderBy('updated_at', 'desc')
                            ->get();

      return response()->json([
        'novedades' => $novedades,
      ]);
    }

    public function createNovedad()
    {
      try {

        //Chequea los campos de entrada
        $campos = request()->validate([
                            'titulo' => 'string',
                            'descripcion' => 'string',
                            'activo' => 'boolean',
                            ]);

        $novedad = new Novedad;
        $novedad->titulo = $campos['titulo'];
        $novedad->descripcion = $campos['descripcion'];
        $novedad->activo = $campos['activo'];

        if ($novedad->save()) {
          //var_dump($novedad->id);
          return response()->json([
            'status' => 200,
            'message' => 'Creación dela novedad realizada con éxito'
          ]);
        } else {
          throw new \Error('No se pudo crear el presupuesto.');
        }
      } catch (\Throwable $e) {

        return response()->json([
          'status' => $e->getCode() ? $e->getCode() : 500,
          'message' => $e->getMessage()
        ]);
      }
    }

    public function updateNovedad($novedadId)
    {
        try {

            if (!is_numeric($novedadId)) {
                throw new \Error('No ingresó un número válido de Novedad.');
            }

            $novedades = Novedad::where('id', $novedadId)
                                ->first();

            if (!$novedades)
            {
                throw new \Error('No existe la novedad.');
            }

            //Chequea los campos de entrada
            $campos = request()->validate([
                'titulo' => 'string',
                'descripcion' => 'string',
                'activo' => 'boolean',
            ]);

            //UPDATE en la DB
            $updateNovedad = Novedad::where('id',$novedadId)
                            -> update(['titulo' => $campos['titulo'],
                                   'descripcion' => $campos['descripcion'],
                                   'activo' => $campos['activo'],]);

            if (!$updateNovedad)
            {
                throw new \Error('No se pudo actualizar la novedad.');
            }

            return response()->json([
            'status' => 200,
            'message' => 'La actualización de la novedad fue realizada con éxito'
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => $e->getCode() ? $e->getCode() : 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function deleteNovedad($novedadId)
    {
        try {

            if (!is_numeric($novedadId)) {
                throw new \Error('No ingresó un número válido de Novedad.');
            }

            $novedades = Novedad::where('id', $novedadId)
                                ->first();

            if (!$novedades)
            {
                throw new \Error('No existe la novedad.');
            }

            //DELETE Novedad en la DB
            if (!$novedades->delete())
            {
                throw new \Error('No se pudo borrar la novedad.');
            }

            return response()->json([
            'status' => 200,
            'message' => 'La novedad fur borrada con éxito'
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => $e->getCode() ? $e->getCode() : 500,
                'message' => $e->getMessage()
            ]);
        }
    }

}
