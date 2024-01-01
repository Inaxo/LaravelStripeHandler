<?php

namespace Inaxo\LaravelStripeHandler\controllers;

use Illuminate\Filesystem\Filesystem;
use Inaxo\LaravelStripeHandler\Exceptions\InvalidProductException;
use Inaxo\LaravelStripeHandler\Exceptions\InvalidFileException;
use Inaxo\LaravelStripeHandler\Exceptions\SessionDataNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use SimpleXMLElement;
use Stripe\Stripe;
use Exception;

class StripePaymentController extends Controller
{
    protected $validator;
    protected $fileSystem;
    protected $sessionKey;
    protected $lineItemsKey;
    protected $currencyConfigKey;
    protected $secretKeyConfigKey;
    protected $homeRouteConfigKey;
    protected $fileFormatConfigKey;

    /**
     * Constructor for the StripePaymentController.
     *
     * @param Validator $validator
     * @param Filesystem $fileSystem
     * @param string $sessionKey
     * @param string $lineItemsKey
     * @param string $currencyConfigKey
     * @param string $secretKeyConfigKey
     * @param string $homeRouteConfigKey
     * @param string $fileFormatConfigKey
     */
    public function __construct(
        Validator $validator,
        Filesystem $fileSystem,
        string $sessionKey = 'StripeSession',
        string $lineItemsKey = 'LineItems',
        string $currencyConfigKey = 'laravel_stripe_handler.currency',
        string $secretKeyConfigKey = 'laravel_stripe_handler.secret_key',
        string $homeRouteConfigKey = 'laravel_stripe_handler.home_route',
        string $fileFormatConfigKey = 'laravel_stripe_handler.file_format'
    ) {
        $this->validator = $validator;
        $this->fileSystem = $fileSystem;
        $this->sessionKey = $sessionKey;
        $this->lineItemsKey = $lineItemsKey;
        $this->currencyConfigKey = $currencyConfigKey;
        $this->secretKeyConfigKey = $secretKeyConfigKey;
        $this->homeRouteConfigKey = $homeRouteConfigKey;
        $this->fileFormatConfigKey = $fileFormatConfigKey;
    }

    /**
     * Initiates the checkout process for a given product ID.
     *
     * @param int $productID The ID of the product to be checked out.
     *
     * @throws InvalidProductException If the product ID is invalid.
     * @throws InvalidFileException If the file format is invalid.
     * @throws Exception If the checkout process fails for any other reason.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function checkout($productID): \Illuminate\Http\RedirectResponse
    {
        $validator = Validator::make(['productID' => $productID], [
            'productID' => 'required|integer|min:0',
        ]);

        try {
            $this->validateProductID($validator);

            $products = $this->getProductsFromFile();
            $lineItems = $this->buildLineItems($products, $productID);

            $this->initializeStripe();

            $session = $this->createStripeSession($lineItems);

            $this->storeSessionData($session, $lineItems);

            return redirect()->away($session->url);
        } catch (InvalidProductException | InvalidFileException $e) {
            $this->logAndAbort($e, 403);
        } catch (Exception $e) {
            $this->logAndAbort($e, 403, 'Checkout failed');
        }
    }

    /**
     * Handles the success scenario after a successful payment.
     *
     * @throws Exception If the success process fails for any reason.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function success(): \Illuminate\Contracts\View\View
    {
        try {
            $stripeSession = $this->getSessionData($this->sessionKey);
            $lineItems = $this->getSessionData($this->lineItemsKey);

            $this->flushSession();

            return view('LaravelStripeHandler::payment-success')->with(['home_route' => config($this->homeRouteConfigKey)]);
        } catch (Exception $e) {
            $this->logAndAbort($e, 403, 'Invalid success data');
        }
    }

    /**
     * Handles the cancellation scenario during the payment process.
     *
     * @throws SessionDataNotFoundException If session data is not found during cancellation.
     * @throws Exception If the cancellation process fails for any other reason.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function cancel(): \Illuminate\Contracts\View\View
    {
        try {
            $this->validateSessionData();

            $this->flushSession();

            return view('LaravelStripeHandler::payment-cancel')->with(['home_route' => config($this->homeRouteConfigKey)]);
        } catch (SessionDataNotFoundException $e) {
            $this->logAndAbort($e, 403, 'Invalid cancel data');
        }
    }

    /**
     * Retrieves the content of the products file.
     *
     * @throws Exception If there is an issue reading the products file.
     *
     * @return string The content of the products file.
     */
    public function getProductsFileContent(): string
    {
        try {
            $directory = resource_path('LaravelStripeHandler');
            $filePath = $directory . '/products.' . config($this->fileFormatConfigKey);

            $this->ensureDirectoryExists($directory);
            $this->ensureFileExists($filePath);

            $fileContent = $this->fileSystem->get($filePath);

            $this->validateFileContent($fileContent, $filePath);

            return $fileContent;
        } catch (Exception $e) {
            $this->logAndThrow($e, 'Error in getProductsFileContent');
        }
    }

    /**
     * Validate the product ID using the given validator.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     *
     * @throws InvalidProductException
     */
    private function validateProductID($validator): void
    {
        if ($validator->fails()) {
            throw new InvalidProductException('Invalid product ID');
        }
    }
    /**
     * Validate session data to ensure it exists.
     *
     * @throws SessionDataNotFoundException If session data is not found.
     */
    private function validateSessionData(): void
    {
        $sessionData = Session::get($this->sessionKey);

        if (!$sessionData) {
            throw new SessionDataNotFoundException('Session data not found');
        }
    }

    /**
     * Retrieve product data from the file and return it.
     *
     * @throws InvalidFileException
     *
     * @return mixed
     */
    private function getProductsFromFile(): mixed
    {
        $productsFileContent = $this->getProductsFileContent();

        switch (config($this->fileFormatConfigKey)) {
            case 'xml':
                return json_decode(json_encode(new SimpleXMLElement($productsFileContent)), true);
            case 'json':
                return json_decode($productsFileContent);
            default:
                throw new InvalidFileException('Invalid file format');
        }
    }

    /**
     * Build line items for the checkout based on the product ID.
     *
     * @param mixed $products
     * @param int $productID
     *
     * @throws InvalidProductException
     *
     * @return array
     */
    private function buildLineItems($products, $productID): array
    {
        $lineItems = [];
        foreach ($products['product'] as $product) {
            if ($product['id'] == $productID) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => config($this->currencyConfigKey),
                        'product_data' => [
                            'name' => $product['name'],
                        ],
                        'unit_amount' => $product['unit_amount'],
                    ],
                    'quantity' => $product['quantity'] ?? 1,
                ];
            }
        }

        if (empty($lineItems)) {
            throw new InvalidProductException('Product not found or invalid');
        }

        return $lineItems;
    }

    /**
     * Initialize the Stripe API key.
     */
    private function initializeStripe(): void
    {
        Stripe::setApiKey(config($this->secretKeyConfigKey));
    }

    /**
     * Create a Stripe checkout session.
     *
     * @param array $lineItems
     *
     * @return \Stripe\Checkout\Session
     *
     * @throws Exception
     */
    private function createStripeSession($lineItems): \Stripe\Checkout\Session
    {
        return \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('payment-success'),
            'cancel_url' => route('payment-cancel'),
        ]);
    }

    /**
     * Store session data in Laravel session.
     *
     * @param \Stripe\Checkout\Session $session
     * @param array $lineItems
     */
    private function storeSessionData($session, $lineItems): void
    {
        Session::put($this->sessionKey, $session);
        Session::put($this->lineItemsKey, $lineItems);
    }

    /**
     * Get data from Laravel session and return it.
     *
     * @param string $key
     *
     * @throws Exception
     *
     * @return mixed
     */
    private function getSessionData($key): mixed
    {
        $data = Session::get($key);

        if (!$data) {
            throw new Exception('Session data not found');
        }

        return $data;
    }

    /**
     * Flush the Laravel session.
     */
    private function flushSession(): void
    {
        Session::flush();
    }

    /**
     * Ensure that the directory exists; create it if not.
     *
     * @param string $directory
     */
    private function ensureDirectoryExists($directory): void
    {
        if (!$this->fileSystem->exists($directory)) {
            $this->fileSystem->makeDirectory($directory);
        }
    }

    /**
     * Ensure that the file exists; create it if not.
     *
     * @param string $filePath
     */
    private function ensureFileExists($filePath): void
    {
        if (!$this->fileSystem->exists($filePath)) {
            $this->fileSystem->put($filePath, '');
        }
    }

    /**
     * Validate file content.
     *
     * @param mixed $fileContent
     * @param string $filePath
     *
     * @throws Exception
     */
    private function validateFileContent($fileContent, $filePath): void
    {
        if ($fileContent === false) {
            throw new Exception('Unable to read file: ' . $filePath);
        }
    }

    /**
     * Log an error, throw an exception, and abort with the given HTTP status code.
     *
     * @param Exception $exception
     * @param int $statusCode
     * @param string|null $message
     */
    private function logAndAbort(Exception $exception, $statusCode, $message = null): void
    {
        Log::error($message ?: $exception->getMessage());
        abort($statusCode, $message ?: $exception->getMessage());
    }

    /**
     * Log an error, throw an exception, and abort with the given HTTP status code.
     *
     * @param Exception $exception
     * @param string $logMessage
     *
     * @throws Exception
     */
    private function logAndThrow(Exception $exception, $logMessage)
    {
        Log::error($logMessage . ': ' . $exception->getMessage());
        throw $exception;
    }
}
