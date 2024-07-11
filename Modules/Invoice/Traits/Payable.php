<?php


namespace Modules\Invoice\Traits;


use Bavix\Wallet\Interfaces\Wallet;
use Modules\Invoice\Entities\Invoice;
use Modules\Invoice\Services\InvoiceService;

trait Payable
{
    public function invoices()
    {
        return $this->morphMany(Invoice::class);
    }

    public function onSuccessPayment(Invoice $invoice)
    {
        // Implement model logic

        return response()->success(__('Payment made successfully'));
    }

    public function onFailedPayment(Invoice $invoice)
    {
        // Implement model logic

        return response()->success(__('Payment failed'));
    }

    public function isPayableReason()
    {
        return __(':class_name by id :id cannot be paid',
            ['class_name' => class_basename($this), 'id' => $this->getKey()]);
    }

    public function pay()
    {
        $invoiceService = new InvoiceService($this);

        return $invoiceService->pay();
    }

    public function payWithWallet(Wallet $user)
    {
        $invoiceService = new InvoiceService($this);

        return $invoiceService->payWithWallet($user);
    }

    public function additionalDataOnPay()
    {
        return [];
    }

    public abstract function isPayable();

    public abstract function getPayableAmount();
}
