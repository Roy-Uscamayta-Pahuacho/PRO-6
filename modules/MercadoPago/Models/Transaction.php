<?php

namespace Modules\MercadoPago\Models;

use App\Models\Tenant\{
    ModelTenant,
    SoapType,
};
use Modules\Payment\Models\{
    PaymentLink
};


class Transaction extends ModelTenant
{

    public const TRANSACTION_STATE_APPROVED = '01';

    protected $fillable = [
        'soap_type_id',
        'date',
        'time',
        'uuid',
        'description',
        'payment_id',
        'amount',
        'transaction_state_id',
        'payment_link_id',
    ];

 
    public function payment_link()
    {
        return $this->belongsTo(PaymentLink::class);
    } 

    public function transaction_state()
    {
        return $this->belongsTo(TransactionState::class, 'transaction_state_id');
    } 

    public function transaction_queries()
    {
        return $this->hasMany(TransactionQuery::class);
    } 
 
    public function soap_type()
    {
        return $this->belongsTo(SoapType::class);
    }
     
    
    /**
     * 
     * Obtener descripción del estado de la transacción
     *
     * @return string
     */
    public function getStateUserMessage()
    {
        return $this->transaction_state->user_message;
    }

}
