<?php

namespace Razoyo\CarProfile\Controller\Customer;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Class MyCar
 *
 * Handles the logic for displaying the customer's car details or redirecting to the form page if no car details are found.
 */
class MyCar extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var RedirectFactory
     */
    protected $redirectFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * MyCar constructor.
     *
     * @param Context $context
     * @param Session $customerSession
     * @param RedirectFactory $redirectFactory
     * @param PageFactory $resultPageFactory
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        RedirectFactory $redirectFactory,
        PageFactory $resultPageFactory,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->customerSession = $customerSession;
        $this->redirectFactory = $redirectFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->customerRepository = $customerRepository;
        parent::__construct($context);
    }

    /**
     * Execute the action.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        // Create a redirect result
        $resultRedirect = $this->redirectFactory->create();
        
        // Check if the customer is logged in
        if (!$this->customerSession->isLoggedIn()) {
            return $resultRedirect->setPath('customer/account/login');
        }

        // Retrieve customer ID and custom attribute 'car_customer'
        $customerId = $this->customerSession->getCustomerId();
        $customer = $this->customerRepository->getById($customerId);
        $carCustomerValue = $customer->getCustomAttribute('car_customer') 
            ? $customer->getCustomAttribute('car_customer')->getValue() 
            : null;

        // If the custom attribute exists, render the car details page
        if ($carCustomerValue) {
            $resultPage = $this->resultPageFactory->create();
            $block = $resultPage->getLayout()->getBlock('customer.car.index');
            if ($block) {
                $block->setData('carDetail', $carCustomerValue);
            }
            return $resultPage;
        } else {
            // Redirect to the form page if no car details are found
            return $resultRedirect->setPath('*/*/form');
        }
    }
}
