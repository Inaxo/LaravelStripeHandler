<?php
namespace Inaxo\LaravelStripeHandler\controllers;
use Exception;
use Faker\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use SimpleXMLElement;
use Stripe\Stripe;
use Illuminate\Support\Facades\Log;
class StripePaymentController extends Controller {
    const SESSION_KEY = 'StripeSession';
    const LINE_ITEMS_KEY = 'LineItems';
    const CURRENCY_CONFIG_KEY = 'laravel_stripe_handler.currency';
    const SECRET_KEY_CONFIG_KEY = 'laravel_stripe_handler.secret_key';
    const HOME_ROUTE_CONFIG_KEY = 'laravel_stripe_handler.home_route';

    public function checkout($productID){

        $validator = Validator::make(['productID' => $productID], [
            'productID' => 'required|integer|min:0',
        ]);
        if ($validator->fails()) {
            abort(403, 'Invalid product ID');
        }
        $xml = $this->getProductFromXMLFile();
        $products = json_decode($xml);
        $lineItems = [];
        foreach ($products->product as $product) {
            if ($product->id == $productID) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => config(self::CURRENCY_CONFIG_KEY),
                        'product_data' => [
                            'name' => $product->name,
                        ],
                        'unit_amount' => $product->unit_amount,
                    ],
                    'quantity' => $product->quantity ?? 1,
                ];
            }else{
                continue;
            }
        }
        Stripe::setApiKey(config(self::SECRET_KEY_CONFIG_KEY));
        $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $lineItems,
        'mode' => 'payment',
        'success_url' => route('payment-success'),
        'cancel_url' => route('payment-cancel'),
      ]);
        Session::put(self::SESSION_KEY, $session);
        Session::put(self::LINE_ITEMS_KEY, $lineItems);
      return redirect()->away($session->url);
    }
    public function success(){ //Here you can add your own logic for example: to save order in database
        $stripeSession = Session::has(self::SESSION_KEY) ? Session::get(self::SESSION_KEY) : abort(403);
        $lineItems = Session::has(self::LINE_ITEMS_KEY) ? Session::get(self::LINE_ITEMS_KEY) : abort(403);



        Session::flush();
        return view('LaravelStripeHandler::payment-success')->with(['home_route' => config(self::HOME_ROUTE_CONFIG_KEY)]);

    }
    public function cancel(){
        if(Session::has('StripeSession')){
            Session::flush();
            return view('LaravelStripeHandler::payment-cancel')->with(['home_route' => config(self::HOME_ROUTE_CONFIG_KEY)]);
        }
        else{
           abort(403);
        }

    }

    /**
     * @throws    Exception
     */
    public function getProductFromXMLFile()
    {
        try {
            $directory = resource_path('LaravelStripeHandler');
            $filePath = $directory . '/products.xml';

            if (!File::exists($directory)) {
                File::makeDirectory($directory);
            }

            if (!File::exists($filePath)) {
                File::put($filePath, '');
            }

            $xmlContent = file_get_contents($filePath) ?? throw new Exception('Unable to read XML file: $filePath');
            $xml = new SimpleXMLElement($xmlContent);
            if (!$xml) {
                throw new Exception('Error parsing XML file: ' . $filePath);
            }
        } catch (Exception $e) {
            Log::error('Error in getProductFromXMLFile: ' . $e->getMessage());
            throw $e;
        }
        return json_encode($xml);
    }


}
