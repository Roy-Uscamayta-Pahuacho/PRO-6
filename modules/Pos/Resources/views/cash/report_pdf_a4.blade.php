<?php

$establishment = $cash->user->establishment;

$final_balance = 0;
$cash_income = 0;
$cash_egress = 0;
$cash_final_balance = 0;
$cash_documents = $cash->cash_documents;

$cash_income_array = [];
$final_balance_array = [];
/** Obtiene un array y suma los valores*/
function setArray(&$array, $type, $val)
{
    $array[$type] = (isset($array[$type]) ? $array[$type] : 0) + $val;
}

foreach ($cash_documents as $cash_document) {

    if ($cash_document->sale_note) {
        $type = $cash_document->sale_note->state_type_id;

        if (in_array($type, $type_documents)) {
            $total = $cash_document->sale_note->total;
            if ($cash_document->sale_note->currency_type_id == 'PEN') {
                $cash_income += $total;
                $final_balance += $total;
                /** Array Temporal */
                setArray($cash_income_array, $type, $total);
                setArray($final_balance_array, $type, $total);
            } else {
                $t = $total * $cash_document->sale_note->exchange_rate_sale;
                $cash_income += $t;
                $final_balance += $t;
                /** Array Temporal */
                setArray($cash_income_array, $type, $t);
                setArray($final_balance_array, $type, $t);
            }

            if (count($cash_document->sale_note->payments) > 0) {
                $pays = $cash_document->sale_note->payments;
                foreach ($methods_payment as $record) {
                    $record->sum = ($record->sum + $pays->where('payment_method_type_id', $record->id)->sum('payment'));
                }
            }
        }
    } elseif ($cash_document->document) {
        $type = $cash_document->document->state_type_id;
        if (in_array($type, $type_documents)) {
            $total = $cash_document->document->total;
            if ($cash_document->document->currency_type_id == 'PEN') {
                $cash_income += $total;
                $final_balance += $total;
                /** Array Temporal */
                setArray($cash_income_array, $type, $total);
                setArray($final_balance_array, $type, $total);
            } else {
                $t = $total * $cash_document->document->exchange_rate_sale;
                $cash_income += $t;
                $final_balance += $t;
                /** Array Temporal */
                setArray($cash_income_array, $type, $t);
                setArray($final_balance_array, $type, $t);
            }
        }
        $payment_condition_id = $cash_document->document->payment_condition_id;
        if (in_array($type, $type_documents) && $payment_condition_id === '01') {
            if (count($cash_document->document->payments) > 0) {
                $pays = $cash_document->document->payments;
                foreach ($methods_payment as $record) {
                    $record->sum = ($record->sum + $pays->where('payment_method_type_id', $record->id)->whereIn('document.state_type_id', $type_documents)->sum('payment'));
                }
            }
        } else {
            foreach ($methods_payment as $record) {
                if ($record->id === '09') {
                    $record->sum += $cash_document->document->total;
                }
            }
        }
    } elseif ($cash_document->technical_service) {

        $cash_income += $cash_document->technical_service->cost;
        $final_balance += $cash_document->technical_service->cost;
        /** Array Temporal */
        $cash_income_array['service'] += $cash_document->technical_service->cost;
        $final_balance['service'] += $cash_document->technical_service->cost;

        if (count($cash_document->technical_service->payments) > 0) {
            $pays = $cash_document->technical_service->payments;
            foreach ($methods_payment as $record) {
                $record->sum = ($record->sum + $pays->where('payment_method_type_id', $record->id)->sum('payment'));
            }
        }
    } elseif ($cash_document->expense_payment) {

        if ($cash_document->expense_payment->expense->state_type_id == '05') {
            if ($cash_document->expense_payment->expense->currency_type_id == 'PEN') {
                $cash_egress += $cash_document->expense_payment->payment;
                $final_balance -= $cash_document->expense_payment->payment;
            } else {
                $cash_egress += $cash_document->expense_payment->payment * $cash_document->expense_payment->expense->exchange_rate_sale;
                $final_balance -= $cash_document->expense_payment->payment * $cash_document->expense_payment->expense->exchange_rate_sale;
            }
        }
    }
}
$cash_final_balance = $final_balance + $cash->beginning_balance;
//$cash_income = ($final_balance > 0) ? ($cash_final_balance - $cash->beginning_balance) : 0;
?>
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="application/pdf; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Reporte POS - {{$cash->user->name}} - {{$cash->date_opening}} {{$cash->time_opening}}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-spacing: 0;
            border: 1px solid black;
        }

        .celda {
            text-align: center;
            padding: 5px;
            border: 0.1px solid black;
        }

        th {
            padding: 5px;
            text-align: center;
            border: 0.1px solid #0088cc;
        }

        .title {
            font-weight: bold;
            padding: 5px;
            font-size: 20px !important;
            text-decoration: underline;
        }

        p>strong {
            margin-left: 5px;
            font-size: 12px;
        }

        thead tr th {
            font-weight: bold;
            background: #0088cc;
            color: white;
            text-align: center;
        }
        .width-custom { width: 50% }
    </style>
</head>
<body>
<div>
    <p align="center" class="title"><strong>Reporte Punto de Venta</strong></p>
</div>
<div style="margin-top:20px; margin-bottom:20px;">
    <table>
        <tr>
            <td class="td-custom width-custom">
                <p><strong>Empresa: </strong>{{$company->name}}</p>
            </td>
            <td class="td-custom">
                <p><strong>Fecha reporte: </strong>{{date('Y-m-d')}}</p>
            </td>
        </tr>
        <tr>
            <td class="td-custom">
                <p><strong>Ruc: </strong>{{$company->number}}</p>
            </td>
            <td class="width-custom">
                <p><strong>Establecimiento: </strong>{{$establishment->address}} - {{$establishment->department->description}} - {{$establishment->district->description}}</p>
            </td>
        </tr>
        <tr>
            <td class="td-custom">
                <p><strong>Vendedor: </strong>{{$cash->user->name}}</p>
            </td>
            <td class="td-custom">
                <p><strong>Fecha y hora apertura: </strong>{{$cash->date_opening}} {{$cash->time_opening}}</p>
            </td>
        </tr>
        <tr>
            <td class="td-custom">
                <p><strong>Estado de caja: </strong>{{($cash->state) ? 'Aperturada':'Cerrada'}}</p>
            </td>
            @if(!$cash->state)
                <td class="td-custom">
                    <p><strong>Fecha y hora cierre: </strong>{{$cash->date_closed}} {{$cash->time_closed}}</p>
                </td>
            @endif
        </tr>
        <tr>
            <td colspan="2" class="td-custom">
                <p><strong>Montos de operación: </strong></p>
            </td>
        </tr>
        <tr>
            <td class="td-custom">
                <p><strong>Saldo inicial: </strong>S/. {{number_format($cash->beginning_balance, 2, ".", "")}}</p>
            </td>
            <td  class="td-custom">
                <p><strong>Ingreso: </strong>S/. {{number_format($cash_income, 2, ".", "")}} </p>
            </td>
        </tr>
        <tr>
            <td  class="td-custom">
                <p><strong>Saldo final: </strong>S/. {{number_format($cash_final_balance, 2, ".", "")}} </p>
            </td>
            <td  class="td-custom">
                <p><strong>Egreso: </strong>S/. {{number_format($cash_egress, 2, ".", "")}} </p>
            </td>
        </tr>
    </table>
</div>
@if($cash_documents->count())
    <div class="">
        <div class=" ">

            <table>
                <thead>
                <tr>
                    <th>#</th>
                    <th>Descripcion</th>
                    <th>Suma</th>
                </tr>
                </thead>
                <tbody>
                @foreach($methods_payment as $item)
                    <tr>
                        <td class="celda">{{ $loop->iteration }}</td>
                        <td class="celda">{{ $item->name }}</td>
                        <td class="celda">{{ number_format(  isset($cash_income_array[$item->id])?$cash_income_array[$item->id]:0, 2, ".", "")  }}</td>
                    </tr>
                @endforeach
                </tbody>

            </table>
            <br>

            <table>
                <thead>
                <tr>
                    <th>#</th>
                    <th>Tipo transacción</th>
                    <th>Tipo documento</th>
                    <th>Documento</th>
                    <th>Fecha emisión</th>
                    <th>Cliente/Proveedor</th>
                    <th>N° Documento</th>
                    <th>Moneda</th>
                    <th>Total</th>
                </tr>
                </thead>
                <tbody>
                @php
                    $all_documents = [];
                    foreach ($cash_documents as $key => $value) {
                        if($value->sale_note){
                            $all_documents[] = $value;
                        }else if($value->document){
                            $all_documents[] = $value;
                        }else if($value->technical_service){
                            $all_documents[] = $value;
                        }else if($value->expense_payment){
                            if($value->expense_payment->expense->state_type_id == '05'){
                                $all_documents[] = $value;
                            }
                        }
                    }
                @endphp
                @foreach($all_documents as $key => $value)
                    <tr>
                        @php

                            $type_transaction =  null;
                            $document_type_description = null;
                            $number = null;
                            $date_of_issue = null;
                            $customer_name = null;
                            $customer_number = null;
                            $currency_type_id = null;
                            $total = null;

                            if($value->sale_note){

                                $type_transaction =  'Venta';
                                $document_type_description =  'NOTA DE VENTA';
                                $number = $value->sale_note->number_full;
                                $date_of_issue = $value->sale_note->date_of_issue->format('Y-m-d');
                                $customer_name = $value->sale_note->customer->name;
                                $customer_number = $value->sale_note->customer->number;

                                $total = $value->sale_note->total;

                                if(!in_array($value->sale_note->state_type_id, $type_documents)){
                                    $total = 0;
                                }

                                $currency_type_id = $value->sale_note->currency_type_id;

                            }
                            else if($value->document){

                                $type_transaction =  'Venta';
                                $document_type_description =  $value->document->document_type->description;
                                $number = $value->document->number_full;
                                $date_of_issue = $value->document->date_of_issue->format('Y-m-d');
                                $customer_name = $value->document->customer->name;
                                $customer_number = $value->document->customer->number;
                                $total = $value->document->total;

                                if(!in_array($value->document->state_type_id, $type_documents)){
                                    $total = 0;
                                }

                                $currency_type_id = $value->document->currency_type_id;

                            }
                            else if($value->technical_service){
                                $type_transaction = 'Venta';
                                $document_type_description =  'Servicio técnico';
                                $number = 'TS-'.$value->technical_service->id;//$value->document->number_full;
                                $date_of_issue = $value->technical_service->date_of_issue->format('Y-m-d');
                                $customer_name = $value->technical_service->customer->name;
                                $customer_number = $value->technical_service->customer->number;
                                $total = $value->technical_service->cost;

                                $currency_type_id = 'PEN';

                            }
                            else if($value->expense_payment){

                                $type_transaction =  'Gasto';
                                $document_type_description =  $value->expense_payment->expense->expense_type->description;
                                $number = $value->expense_payment->expense->number;
                                $date_of_issue = $value->expense_payment->expense->date_of_issue->format('Y-m-d');
                                $customer_name = $value->expense_payment->expense->supplier->name;
                                $customer_number = $value->expense_payment->expense->supplier->number;
                                $total = -$value->expense_payment->payment;
                                $currency_type_id = $value->expense_payment->expense->currency_type_id;

                            }

                        @endphp


                        <td class="celda">{{ $loop->iteration }}</td>
                        <td class="celda">{{ $type_transaction }}</td>
                        <td class="celda">{{ $document_type_description }}</td>
                        <td class="celda">{{ $number }}</td>
                        <td class="celda">{{ $date_of_issue}}</td>
                        <td class="celda">{{ $customer_name }}</td>
                        <td class="celda">{{$customer_number }}</td>
                        <td class="celda">{{ $currency_type_id }}</td>
                        <td class="celda">{{ number_format($total,2, ".", "") }}</td>

                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="callout callout-info">
        <p>No se encontraron registros.</p>
    </div>
@endif
</body>
</html>
