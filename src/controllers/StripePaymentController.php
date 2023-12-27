<?php
namespace Inaxo\LaravelStripeHandler\controllers;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\Controller;
use SimpleXMLElement;
use Stripe\Stripe;
class StripePaymentController extends Controller {

    public function checkout(){
        Stripe::setApiKey(config('laravel_stripe_handler.secret_key'));
        $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
          'price_data' => [
            'currency' => 'pln',
            'product_data' => [
              'name' => 'Mózg dla Pana Skrzypczyka',
            ],
            'unit_amount' => 15000000,
          ],
          'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => route('payment-success'),
        'cancel_url' => route('payment-cancel'),
      ]);
      return redirect()->away($session->url);
    }
    public function success(){
        return view('LaravelStripeHandler::payment-success');
    }
    public function cancel(){
        return view('LaravelStripeHandler::payment-cancel');
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
