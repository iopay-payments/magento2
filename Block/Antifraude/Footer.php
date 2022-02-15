<?php

namespace IoPay\Core\Block\Antifraude;

class Footer
    extends \Magento\Framework\View\Element\Template
{
    protected $_sessionManager;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Session\SessionManager $sessionManager
    )
    {
        $this->_sessionManager = $sessionManager;
        parent::__construct($context);
    }

    public function getCustomerSession()
    {
        return $this->_sessionManager->getSessionId();
    }
}
