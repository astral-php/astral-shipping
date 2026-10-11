<?php

declare(strict_types=1);

// Exemple : app/Controllers/CheckoutController.php (à adapter à ton astral-shop).

namespace App\Controllers;

use AstralShipping\Exception\InvalidSelectionException;
use AstralShipping\Models\Address;
use AstralShipping\Models\ShippingContext;
use AstralShipping\Service\ShippingService;
use Core\Http\Response;
use Core\Request;
use Core\Session;
use Core\View;
use Controller\AbstractController;

final class CheckoutController extends AbstractController
{
    public function __construct(
        View $view,
        private Request $request,
        private Session $session,
        private ShippingService $shipping,
        // private CartService $cart,   // ton panier astral-shop
    ) {
        parent::__construct($view); // AbstractController::__construct(View $view)
    }

    public function shipping(): Response
    {
        $context = $this->contextFromSession();

        return $this->render('checkout/shipping', [
            'quotes'   => $this->shipping->quotes($context),
            'selected' => $this->session->get('shipping.method'),
        ]);
    }

    public function saveShipping(): Response
    {
        $context = $this->contextFromSession();

        try {
            $selection = $this->shipping->select(
                $context,
                (string) $this->request->input('shipping_method', ''),
                $this->request->input('pickup_point_id'),
                $this->request->input('pickup_point_label'),
            );
        } catch (InvalidSelectionException $e) {
            return $this->render('checkout/shipping', [
                'quotes'   => $this->shipping->quotes($context),
                'selected' => null,
                'errors'   => ['shipping_method' => $e->getMessage()],
            ]);
        }

        $this->session->set('shipping.method', $selection->quote->code);
        $this->session->set('shipping.pickup_id', $selection->pickupPoint?->id);
        $this->session->set('shipping.pickup_label', $selection->pickupPoint?->label);

        // Montant à transmettre à astral-payment : panier + port, calculé côté serveur.
        // $total = $this->shipping->total($context->subtotalCents, $selection);

        return $this->redirect('/checkout/payment');
    }

    private function contextFromSession(): ShippingContext
    {
        // Remplace par les vraies données de ton panier / de l'adresse saisie.
        return new ShippingContext(
            subtotalCents: 4990,
            weightGrams: 1200,
            destination: new Address(
                (string) $this->session->get('checkout.country', 'FR'),
                (string) $this->session->get('checkout.postal_code', ''),
            ),
        );
    }
}
