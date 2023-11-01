<?php

namespace Modules\Item\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Item\Imports\ItemSetIndividualImport;
use Modules\Item\Imports\ItemSetImport;
use Maatwebsite\Excel\Excel;
use App\Models\Tenant\{
    Item,
    ItemSet
};

class ItemSetController extends Controller
{


    public function importItemSets(Request $request)
    {
        if ($request->hasFile('file')) {
            try {
                $import = new ItemSetImport();
                $import->import($request->file('file'), null, Excel::XLSX);
                $data = $import->getData();
                return [
                    'success' => true,
                    'message' => __('app.actions.upload.success'),
                    'data' => $data
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }
        return [
            'success' => false,
            'message' => __('app.actions.upload.error'),
        ];
    }


    public function importItemSetsIndividual(Request $request)
    {
        if ($request->hasFile('file')) {
            try {
                $import = new ItemSetIndividualImport();
                $import->import($request->file('file'), null, Excel::XLSX);
                $data = $import->getData();
                return [
                    'success' => true,
                    'message' => __('app.actions.upload.success'),
                    'data' => $data
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }
        return [
            'success' => false,
            'message' => __('app.actions.upload.error'),
        ];
    }

        
    /**
     *
     * @param  int $item_id
     * @return array
     */
    public function setsDescription($item_id)
    {
        return ItemSet::filterDataByItem($item_id)
                        ->get()
                        ->transform(function ($set) {
                            return $set->getRowViewDescription();
                        });
    }

}
