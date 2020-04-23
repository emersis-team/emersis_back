<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Novedad;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $novedad->titulo = $user->id;
        $novedad->descripcion = $campos['name'];
        $novedad->activo = "NEW";

        if ($novedad->save()) {
          //var_dump($novedad->id);
          return response()->json([
            'status' => 200,
            'message' => 'Creación del presupuesto realizada con éxito'
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
        $user = Auth::user();

        try {

            if (!is_numeric($novedadId)) {
                throw new \Error('No ingresó un número válido de Presupuesto.');
            }

            $budgets = Budget::where('id', $novedadId)
                                ->where('user_id', $user->id)
                                ->first();

            if (!$budgets)
            {
                throw new \Error('No existe el presupuesto para el usuario.');
            }

            //Chequea los campos de entrada
            $campos = request()->validate([
                'name' => 'string',
                'status' => Rule::in(['NEW', 'CLOSED']),
            ]);

            //UPDATE en la DB
            $updateBudget = Budget::where('id',$budgetId)
                        -> update(['name' => $campos['name'],
                                   'status' => $campos['status'] ]);

            if (!$updateBudget)
            {
                throw new \Error('No se pudo actualizar el presupuesto.');
            }

            return response()->json([
            'status' => 200,
            'message' => 'La actualización del presupuesto fue realizada con éxito'
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => $e->getCode() ? $e->getCode() : 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function deleteNovedad($budgetId)
    {
        $user = Auth::user();

        try {

            if (!is_numeric($budgetId)) {
                throw new \Error('No ingresó un número válido de Presupuesto.');
            }

            DB::beginTransaction();

            $budgets = Budget::where('id', $budgetId)
                                ->where('user_id', $user->id)
                                ->first();

            if (!$budgets)
            {
                throw new \Error('No existe el presupuesto para el usuario.');
            }

            //DELETE Budget en la DB
            if (!$budgets->delete())
            {
                throw new \Error('No se pudo borrar el presupuesto.');
            }

            //DELETE BudgetProducts en la DB
            $budgetProducts = BudgetProduct::where('budget_id', $budgetId)->get();
            //$budgetProducts->delete();

            foreach ($budgetProducts as $budgetProduct) {
                $budgetProduct->delete();
            }

            if (!$budgetProducts) {

                DB::rollBack();
                throw new \Error('No se pudo borrar los ítems del presupuesto. No se borró el presupuesto.');
            }

            DB::commit();

            return response()->json([
            'status' => 200,
            'message' => 'El presupuesto y sus ítems han sido borrados con éxito'
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => $e->getCode() ? $e->getCode() : 500,
                'message' => $e->getMessage()
            ]);
        }
    }

}
