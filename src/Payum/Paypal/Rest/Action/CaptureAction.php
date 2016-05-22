<?php

namespace Payum\Paypal\Rest\Action;

use PayPal\Api\Payment as PaypalPayment;
use PayPal\Api\PaymentExecution;
use PayPal\Rest\ApiContext;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Payum\Core\Reply\HttpRedirect;

class CaptureAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;

    public function __construct()
    {
        $this->apiClass = ApiContext::class;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request Capture */
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var \PayPal\Api\Payment $model */
        $model = $request->getModel();

        if (
            $model->getState() === null &&
            $model->getPayer() !== null && 
            $model->getPayer()->getPaymentMethod() !== null &&
            'paypal' == $model->getPayer()->getPaymentMethod()
        ) {
            $model->create($this->api);

            /** @var \PayPal\Api\Links $link */
            foreach ($model->getLinks() as $link) {
                if ($link->getRel() == 'approval_url') {
                    throw new HttpRedirect($link->getHref());
                }
            }
        }

        if (
            $model->getState() === null &&
            $model->getPayer() !== null && 
            $model->getPayer()->getPaymentMethod() !== null &&
            'credit_card' == $model->getPayer()->getPaymentMethod()
        ) {
            $model->create($this->api);
        }

        if (
            $model->getState() !== null &&
            $model->getPayer() !== null && 
            $model->getPayer()->getPaymentMethod() !== null &&
            'paypal' == $model->getPayer()->getPaymentMethod()
        ) {
            $execution = new PaymentExecution();
            $execution->setPayerId($_GET['PayerID']);

            //Execute the payment
            $model->execute($execution, $this->api);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof PaypalPayment
        ;
    }
}
