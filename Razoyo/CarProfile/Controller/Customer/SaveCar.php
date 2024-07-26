<?php

namespace Razoyo\CarProfile\Controller\Customer;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Razoyo\CarProfile\Service\CarService;

/**
 * Class SaveCar
 *
 * Handles the logic for saving the selected car details for the logged-in customer.
 */
class SaveCar extends Action
{
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var CarService
     */
    protected $carService;

    /**
     * SaveCar constructor.
     *
     * @param Context $context
     * @param Session $customerSession
     * @param RedirectFactory $redirectFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param CarService $carService
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        RedirectFactory $redirectFactory,
        CustomerRepositoryInterface $customerRepository,
        CarService $carService
    ) {
        $this->customerSession = $customerSession;
        $this->redirectFactory = $redirectFactory;
        $this->customerRepository = $customerRepository;
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
        // Retrieve parameters from the request
        $carList = $this->getRequest()->getParam('car_list');
        $make = $this->getRequest()->getParam('make');
        $model = $this->getRequest()->getParam('model');
        $year = $this->getRequest()->getParam('year');
        $token = $this->getRequest()->getParam('token');

        // Get the customer ID from the session
        $customerId = $this->customerSession->getCustomerId();

        // If car list and customer ID are present
        if ($carList && $customerId) {
            try {
                // Retrieve the car ID based on make, model, and year
                $carId = $this->getCarId($carList, $year, $make, $model);

                // Get car details using the CarService
                $details = $this->carService->getCarDetails($carId, $token);
                if (!$details) {
                    // Fallback to concatenating the car attributes if details are not available
                    $details = sprintf('%s|%s|%s|%s', $carId, $make, $model, $year);
                }
                
                // Update the customer attribute with the car details
                $customer = $this->customerRepository->getById($customerId);
                $customer->setCustomAttribute('car_customer', $details);
                $this->customerRepository->save($customer);

                // Add a success message
                $this->messageManager->addSuccessMessage(__('Car has been saved successfully.'));

            } catch (LocalizedException $e) {
                // Add an error message for localized exceptions
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                // Add a generic error message for other exceptions
                $this->messageManager->addErrorMessage(__('Unable to save car.'));
            }
        } else {
            // Add an error message if car list or customer ID is missing
            $this->messageManager->addErrorMessage(__('Invalid car or customer.'));
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->redirectFactory->create();
        return $resultRedirect->setPath('*/*/mycar');
    }

    /**
     * Retrieve the car ID based on make, model, and year.
     *
     * @param string $carList JSON encoded car list
     * @param string $year Selected car year
     * @param string $make Selected car make
     * @param string $model Selected car model
     * @return string|null Car ID or null if not found
     */
    public function getCarId($carList, $year, $make, $model)
    {
        $carList = json_decode($carList, true);

        foreach ($carList as $car) {
            if ($car['year'] == $year && $car['make'] == $make && $car['model'] == $model) {
                return $car['id'];
            }
        }

        return null;
    }
}
