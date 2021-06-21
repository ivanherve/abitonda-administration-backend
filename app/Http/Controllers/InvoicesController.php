<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Views\VInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoicesController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getInvoices()
    {
        $invoices = VInvoice::all();
        if (!$invoices) return $this->errorRes("Il n'y a aucune facture d'enregistré", 404);
        return $this->successRes($invoices);
    }

    public function addInvoices(Request $request)
    {
        $title = $request->input('title');
        if (!$title) return $this->errorRes('Veuillez insérer un titre', 404);

        $amount = $request->input('amount');
        if (!$amount) return $this->errorRes('Veuillez insérer un montant', 404);

        $paymentMethod = $request->input('paymentMethod');
        if (!$paymentMethod) return $this->errorRes('Veuillez insérer une méthode de paiement', 404);

        $paymentMethod = PaymentMethod::all()->where('Name', '=', $paymentMethod)->first();
        if (!$paymentMethod) return $this->errorRes('Cette méthode de paiement est introuvable dans le sytème', 404);
        else $paymentMethod = $paymentMethod->PaymentMethodId;

        $datePayment = $request->input('datePayment');
        if (!$datePayment) return $this->errorRes('Veuillez insérer une date de paiement', 404);

        $datePayment = date_format(date_create($datePayment), "Y-m-d");
        $today = date_format(date_create(), "Y-m-d");

        if ($datePayment > $today) return $this->errorRes('Cette date est supérieur à aujourd\'hui', 401);

        $description = $request->input('description');
        if (!$description) return $this->errorRes('Veuillez insérer une description', 404);
        $copyInvoice = $request->input('copyInvoice');
        if (!$copyInvoice) return $this->errorRes('Veuillez insérer une copie/scan de la facture', 404);
        else $copyInvoice = json_decode($copyInvoice);

        if (!strpos("  " . $copyInvoice->type, "image")) return $this->errorRes('Les fichiers de type ' . $copyInvoice->type . ' ne sont pas permis. Veuillez insérer une image', 401);

        $invoice = [
            'Title' => $title,
            'Amount' => $amount,
            'DatePayment' => $datePayment,
            'Description' => $description,
            'BillPicture' => $copyInvoice->base64,
            'PaymentMethodId' => $paymentMethod
        ];

        $invoice = Invoice::create($invoice);

        return $this->successRes($invoice);
    }
}
