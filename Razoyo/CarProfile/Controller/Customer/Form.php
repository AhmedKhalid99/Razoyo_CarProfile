<?php

namespace Razoyo\CarProfile\Controller\Customer;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ClientInterface;
use Razoyo\CarProfile\Service\CarService;

/**
 * Class Form
 *
 * Handles the form rendering and car list retrieval for the customer car profile.
 */
class Form extends \Magento\Framework\App\Action\Action
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
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var ClientInterface
     */
    protected $httpClient;

    /**
     * @var CarService
     */
    protected $carService;

    /**
     * Form constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Session $customerSession
     * @param JsonFactory $resultJsonFactory
     * @param ClientInterface $httpClient
     * @param CarService $carService
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Session $customerSession,
        JsonFactory $resultJsonFactory,
        ClientInterface $httpClient,
        CarService $carService
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->httpClient = $httpClient;
        $this->carService = $carService;
        parent::__construct($context);
    }

    /**
     * Execute the action.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        // Check if the customer is logged in
        if (!$this->customerSession->isLoggedIn()) {
            $this->_redirect('customer/account/login');
            return;
        }

        try {
            $resultJson = $this->resultJsonFactory->create();

            // Fetch car list from the external API
            $carListResponse = $this->carService->getCarList();
            if ($carListResponse['status'] !== 200) {
                throw new LocalizedException(__('Failed to retrieve car list.'));
            }
            
            // Create the result page
            $resultPage = $this->resultPageFactory->create();
            $block = $resultPage->getLayout()->getBlock('customer.car.form');
            if ($block) {
                // Set car list and token data to the block
                $block->setData('carList', $carListResponse['data']['cars']);
                $block->setData('your-token', $carListResponse['your-token']);
            }

            // Set active state for the customer account navigation block
            $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');
            if ($navigationBlock) {
                $navigationBlock->setActive('carprofile/customer/mycar');
            }

            return $resultPage;
        } catch (\Exception $e) {
            // Return a JSON response with the error message in case of an exception
            return $resultJson->setData([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }
}