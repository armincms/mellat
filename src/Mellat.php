<?php

namespace Armincms\Mellat;

use Illuminate\Http\Request;
use Laravel\Nova\fields\{Text, Boolean};
use Armincms\Arminpay\Contracts\{Gateway, Billing}; 
use Shetabit\Payment\Facade\Payment;
use Shetabit\Multipay\Invoice;

class Mellat implements Gateway
{ 
    /**
     * The gateway configuration values.
     * 
     * @var array
     */
    public $config = [];    

    /**
     * Construcy the instance.
     * 
     * @param array $config 
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Make payment for the given Billing.
     * 
     * @param  \Illuminate\Http\Request  $request  
     * @param  \Armincms\Arminpay\Contracts\Billing $billing  
     * @return \Symfony\Component\HttpFoundation\Response
     * 
     * @throws \InvalidArgumentException
     */
    public function pay(Request $request, Billing $billing)
    {    
        return Payment::callbackUrl($billing->callback())
                        ->via('behpardakht')
                        ->config($this->getConfigurations())
                        ->purchase($this->newInvoice($billing))
                        ->pay();
    } 

    public function newInvoice(Billing $billing)
    {
        return tap(new Invoice, function($invoice) use ($billing) {
            $invoice->amount(intval($billing->amount()))
                    ->uuid($billing->getIdentifier());
        });
    }

    /**
     * Verify the payment for the given Billing.
     * 
     * @param  \Illuminate\Http\Request  $request  
     * @param  \Armincms\Arminpay\Contracts\Billing $billing  
     * @return \Symfony\Component\HttpFoundation\Response
     * 
     * @throws \InvalidArgumentException
     */
    public function verify(Request $request, Billing $billing)
    {
        return Payment::amount(intval($billing->amount()))
                    ->transactionId($billing->getIdentifier())
                    ->verify()
                    ->getReferenceId();
    } 
 
    /**
     * Returns configuration fields.
     * 
     * @return array 
     */
    public function fields(Request $request): array
    {
        return [ 
            Text::make('Merchant ID', 'terminalId')
                ->help(__('Please enter the given the Mellat bank Merchant Id.'))
                ->required()
                ->rules('required'),

            Text::make('Username')
                ->help(__('Please enter the given the Mellat bank Username.'))
                ->required()
                ->rules('required'),

            Text::make('Password')
                ->help(__('Please enter the given the Mellat bank Password.'))
                ->required()
                ->rules('required'), 

            Boolean::make(__('Sandbox'), 'sandbox'),
        ];
    }  

    public function getConfigurations()
    { 
        if(data_get($this->config, 'sandbox', false)) {
            $this->config['apiPurchaseUrl'] = 'https://banktest.ir/gateway/bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';
            $this->config['apiPaymentUrl'] = 'https://banktest.ir/gateway/pgw.bpm.bankmellat.ir/pgwchannel/startpay.mellat';
            $this->config['apiVerificationUrl'] = 'https://banktest.ir/gateway/bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';
        }

        return $this->config; 
    }
}
