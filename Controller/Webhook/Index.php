<?php

namespace IoPay\Core\Controller\Webhook;

use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;

class Index extends AbstractNotification
{

    protected $_helper;
    protected $orderFactory;
    protected $invoiceService;
    protected $transaction;
    protected $invoiceSender;

    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        Transaction $transaction
    )
    {
        $this->orderFactory    = $orderFactory;
        $this->invoiceService   = $invoiceService;
        $this->transaction      = $transaction;
        $this->invoiceSender    = $invoiceSender;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_helper = $objectManager->create('IoPay\Core\Helper\Data');
        parent::__construct($context);
    }

    /**
     * Action to receive webhook from IoPay API
     * Controller /iopay/webhook/
     */
    public function execute()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $this->_helper->logs('--- IoPay Webhook ----');
        $this->_helper->logs($data);

        if (
            $data['status'] &&
            $data['reference_id'])
        {
            $orderIncrementId   = $data['reference_id'];
            $status             = $data['status'];

            try {
                $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
                if ($order->getId()) {
                    $this->changeOrderStatus($order, $status);
                    http_response_code(200);
                } else {
                    $this->_helper->logs("IoPay Webhook: order {$orderIncrementId} not found.");
                    http_response_code(404);
                }
            } catch(Exception $e) {
                $this->_helper->logs($e->getMessage());
                http_response_code(400);
            }
        }
    }

    public function changeOrderStatus($order, $status)
    {
        $this->_helper->logs(
            "IoPay Webhook: order {$order->getIncrementId()} changing status to {$status}"
        );
        try {
            $orderStateProcessing = Order::STATE_PROCESSING;

            /* Check webhook status return */
            switch ($status) {
                case 'approved':
                case 'succeeded':
                    $order->setState($orderStateProcessing)->setStatus($orderStateProcessing);
                    $order->save();
                    if ($this->_helper->getIopayAutoInvoice()) {
                        $this->invoiceOrder($order);
                    }
                    break;
                case 'canceled':
                case 'failed':
                case 'charged_back':
                    $this->cancelOrder($order);
                    break;
                default:
                    break;
            }

            return true;

        } catch(Exception $e) {
            $this->_helper->logs($e->getMessage());
        }
    }

    public function cancelOrder($order)
    {
        try {
            $this->_helper->logs("IoPay Webhook: cancelling order {$order->getIncrementId()} ");
            if ($order->canCancel()) {
                $order->cancel()->save();

                $order->addCommentToStatusHistory(
                    __("IoPay: canceled order {$order->getIncrementId()}")
                )->setIsCustomerNotified(true)->save();

                $this->_helper->logs("IoPay Webhook: order {$order->getIncrementId()} canceled");
            } else {
                $this->_helper->logs("IoPay Webhook: order {$order->getIncrementId()} cannot cancel");
            }

            return true;

        } catch (Exception $e) {
            $this->_helper->logs($e->getMessage());
        }
    }

    public function invoiceOrder($order)
    {
        try {
            $this->_helper->logs("IoPay Webhook: generating invoice from order {$order->getIncrementId()} ");

            if ($order->canInvoice()) {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->register();
                $invoice->save();

                $transactionSave =
                    $this->transaction
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder());
                $transactionSave->save();

                //$this->invoiceSender->send($invoice);

                $order->addCommentToStatusHistory(
                    __("IoPay: Auto invoiced order {$order->getIncrementId()}")
                )->setIsCustomerNotified(true)->save();

                $this->_helper->logs("IoPay Webhook: order {$order->getIncrementId()} invoiced successfully");

            } else {
                $this->_helper->logs("IoPay Webhook: cannot invoice order {$order->getIncrementId()}");
            }

            return true;

        } catch (Exception $e) {
            $this->_helper->logs($e->getMessage());
        }
    }
}
