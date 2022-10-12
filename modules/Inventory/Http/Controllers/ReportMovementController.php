<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade as PDF;
use Modules\Inventory\Exports\ReportMovementExport;
use Illuminate\Http\Request;
use App\Models\Tenant\Company;
use Carbon\Carbon;
use Modules\Inventory\Http\Resources\ReportMovementCollection;
use Modules\Inventory\Http\Resources\ReportStockFitCollection;
use Modules\Inventory\Models\{
    Inventory,
    Warehouse,
};
use Modules\Inventory\Traits\InventoryTrait;
use Modules\Inventory\Http\Requests\ReportMovementRequest;
use Modules\Inventory\Exports\ReportStockExport;
use App\CoreFacturalo\Helpers\Template\ReportHelper;


class ReportMovementController extends Controller
{

	use InventoryTrait;
     
    public function filter()
    {
		return [
			'warehouses'             => $this->optionsWarehouse(),
			'inventory_transactions' => $this->allInventoryTransaction(),
		];
    }


    public function records(ReportMovementRequest $request)
    {
        $records = $this->getRecords($request->all());

        return new ReportMovementCollection($records->paginate(config('tenant.items_per_page')));
    }


    /**
     * @param $request
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|Inventory
     */
    private function getRecords($request)
    {

        $warehouse_id = $request['warehouse_id'];
        $inventory_transaction_id = $request['inventory_transaction_id'];
        $date_start = $request['date_start'];
        $date_end = $request['date_end'];
        $item_id = $request['item_id'];
        $order_inventory_transaction_id = $request['order_inventory_transaction_id'];


        return Inventory::whereFilterReportMovement($warehouse_id, $inventory_transaction_id, $date_start, $date_end, $item_id, $order_inventory_transaction_id);
 
    }
    

    /**
     * PDF
     * @param ReportMovementRequest $request
     * @return \Illuminate\Http\Response
     */
    public function pdf(ReportMovementRequest $request)
    {
        $pdf = PDF::loadView('inventory::reports.movements.report_template', $this->getDataForFormat($request));

        return $pdf->download('Reporte_Movimientos' . date('YmdHis') . '.pdf');
    }


    /**
     * Excel
     * @param ReportMovementRequest $request
     * @return \Illuminate\Http\Response
     */
    public function excel(Request $request)
    {
        $exportData = new ReportMovementExport();
        $exportData->data($this->getDataForFormat($request));

        return $exportData->download('Reporte_Movimientos' . date('YmdHis') . '.xlsx');
    }
 
    
    /**
     * Obtener datos para generar reporte pdf/excel
     *
     * @param  mixed $request
     * @return array
     */
    private function getDataForFormat($request)
    {
        return [
            'company' => Company::first(),
            'warehouse' => Warehouse::select('description')->find($request->warehouse_id),
            'records' => $this->getRecords($request->all())->get()->transform(function($row, $key) { return  $row->getRowResourceReport(); }),
        ];
    }

    public function stockRecords(Request $request)
    {
        $records = $this->getStockRecords($request->all());

        return new ReportStockFitCollection($records->paginate(config('tenant.items_per_page')));
    }

    
    /**
     * 
     * Consulta de reporte ajuste stock
     *
     * @param  array $request
     * @return Inventory
     */
    private function getStockRecords($request)
    {
        $warehouse_id = $request['warehouse_id'];
        $date_start = $request['date_start'];
        $date_end = $request['date_end'];
        $order_by_item = ReportHelper::getBoolValue($request['order_by_item']);
        $order_by_timestamps = ReportHelper::getBoolValue($request['order_by_timestamps']);

        return Inventory::whereFilterReportStock($warehouse_id, $date_start, $date_end, $order_by_item, $order_by_timestamps);
    }

    
    /**
     * 
     * Exportar reportes de ajuste de stock
     *
     * @param  string $type
     * @param  Request $request
     * @return mixed
     */
    public function formatStockFit($type, Request $request)
    {
        $filename = 'Reporte_Ajuste_stock' . date('YmdHis');

        if($type === 'excel')
        {
            $exportData = (new ReportStockExport)->data($this->getDataForFormatStock($request));
            return $exportData->download($filename.'.xlsx');
        }

        
        return (PDF::loadView('inventory::reports.movements.report_stock_template', $this->getDataForFormatStock($request)))->download($filename.'.pdf');
    }


    /*
    public function stockExcel(Request $request)
    {
        $exportData = new ReportStockExport();
        $exportData->data($this->getDataForFormatStock($request));

        return $exportData->download('Reporte_Movimientos' . date('YmdHis') . '.xlsx');
    }
    */


    private function getDataForFormatStock($request)
    {
        return [
            'company' => Company::first(),
            'warehouse' => Warehouse::select('description')->find($request->warehouse_id),
            'records' => $this->getStockRecords($request->all())->get()->transform(function($row, $key) { return  $row->getRowResourceReportStock(); }),
        ];
    }

}
