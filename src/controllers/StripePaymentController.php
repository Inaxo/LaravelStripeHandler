<?php
namespace Inaxo\LaravelStripeHandler\controllers;
use Faker\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use SimpleXMLElement;
use Stripe\Stripe;
class StripePaymentController extends Controller {

    public function checkout($productID){
        $id = $productID;
        $xml = $this->getProductFromXMLFile();
        $products = json_decode($xml);
        $lineItems = [];
        foreach ($products->product as $product) {
            if ($product->id == $id) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'pln',
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
        Stripe::setApiKey(config('laravel_stripe_handler.secret_key'));
        $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $lineItems,
        'mode' => 'payment',
        'success_url' => route('payment-success'),
        'cancel_url' => route('payment-cancel'),
      ]);
        Session::put('StripeSession', $session);
        Session::put('LineItems', $lineItems);
      return redirect()->away($session->url);
    }
    public function success(){ //Here you can add your own logic for example: to save order in database
        $stripeSession = Session::get('StripeSession');
        $lineItems = Session::get('LineItems');





        Session::flush();
        return view('LaravelStripeHandler::payment-success')->with(['home_route' => config('laravel_stripe_handler.home_route')]);

    }
    public function cancel(){
        if(Session::has('StripeSession')){
            Session::flush();
            return view('LaravelStripeHandler::payment-cancel')->with(['home_route' => config('laravel_stripe_handler.home_route')]);
        }
        else{
           abort(403);
        }

    }

    /**
     * @throws \Exception
     */
    public function getProductFromXMLFile()
    {

        $directory = resource_path('LaravelStripeHandler');
        $filePath = $directory . '/products.xml';

        if (!File::exists($directory)) {
            File::makeDirectory($directory);
        }

        if (!File::exists($filePath)) {
            File::put($filePath, '');
        }

        $xmlContent = file_get_contents($filePath);
        $xml = new SimpleXMLElement($xmlContent);
        return json_encode($xml);
    }


}
