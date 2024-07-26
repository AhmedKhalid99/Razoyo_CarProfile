<?php

namespace Razoyo\CarProfile\Service;

use Magento\Framework\HTTP\ClientInterface;

/**
 * Class CarService
 *
 * Handles operations related to fetching car information from the external API.
 */
class CarService
{
    /**
     * @var ClientInterface
     */
    protected $httpClient;

    /**
     * CarService constructor.
     *
     * @param ClientInterface $httpClient HTTP client instance for making API requests.
     */
    public function __construct(
        ClientInterface $httpClient
    ) {
        $this->httpClient = $httpClient;
    }

    /**
     * Fetches the list of cars from the external API.
     *
     * @return array An associative array containing the HTTP status code, decoded response data, and the 'your-token' header.
     */
    public function getCarList()
    {
        $url = 'https://exam.razoyo.com/api/cars';
        
        // Set request headers
        $this->httpClient->setHeaders(['accept' => 'application/json']);
        
        // Send GET request
        $this->httpClient->get($url);
        
        // Retrieve HTTP status code and response body
        $statusCode = $this->httpClient->getStatus();
        $responseBody = $this->httpClient->getBody();
        $headers = $this->httpClient->getHeaders();
        
        return [
            'status' => $statusCode,
            'data' => json_decode($responseBody, true),
            'your-token' => $headers['your-token'] ?? null  // Use null check operator to avoid undefined index notice
        ];
    }

    /**
     * Fetches detailed information about a specific car.
     *
     * @param string $carId The ID of the car to fetch details for.
     * @param string $token The authorization token for the API request.
     * @return string|null A formatted string of car details or null if the request was unsuccessful.
     */
    public function getCarDetails($carId, $token)
    {
        $details = null;
        $url = 'https://exam.razoyo.com/api/cars/' . $carId;

        // Set request headers with authorization token
        $this->httpClient->setHeaders([
            'accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ]);
        
        // Send GET request
        $this->httpClient->get($url);
        
        // Retrieve HTTP status code and response body
        $statusCode = $this->httpClient->getStatus();
        $responseBody = $this->httpClient->getBody();
        $data = json_decode($responseBody, true);
        
        // Check if request was successful and format car details
        if ($statusCode === 200 && isset($data['id'], $data['make'], $data['model'], $data['year'], $data['price'], $data['seats'], $data['mpg'], $data['image'])) {
            $details = sprintf(
                '%s|%s|%s|%s|%s|%s|%s|%s',
                $data['id'],
                $data['make'],
                $data['model'],
                $data['year'],
                $data['price'],
                $data['seats'],
                $data['mpg'],
                $data['image']
            );
        }
        
        return $details;
    }
}
