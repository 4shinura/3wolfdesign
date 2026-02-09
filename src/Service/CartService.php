<?php

namespace App\Service;

use App\Repository\ProduitRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private $requestStack;
    private $produitRepository;

    public function __construct(RequestStack $requestStack, ProduitRepository $produitRepository)
    {
        $this->requestStack = $requestStack;
        $this->produitRepository = $produitRepository;
    }

    public function add(int $id)
    {
        $session = $this->requestStack->getSession();
        
        // On récupère le panier actuel (vide par défaut)
        $cart = $session->get('cart', []);

        if (!empty($cart[$id])) {
            $cart[$id]++; // Si le produit y est déjà, on augmente la quantité
        } else {
            $cart[$id] = 1; // Sinon, on l'ajoute avec une quantité de 1
        }

        $session->set('cart', $cart);
    }

    public function remove(int $id)
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        if(!empty($cart[$id])) {
            unset($cart[$id]);
        }

        $session->set('cart', $cart);
    }

    public function decrease(int $id)
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        if (!empty($cart[$id])) {
            if ($cart[$id] > 1) {
                $cart[$id]--;
            } else {
                unset($cart[$id]);
            }
        }

        $session->set('cart', $cart);
    }

    public function emptyCart()
    {
        $this->requestStack->getSession()->remove('cart');
    }

    public function getFullCart(): array
    {
        return $this->requestStack->getSession()->get('cart', []);
    }

    public function getTotalQuantity(): int
    {
        $cart = $this->getFullCart(); // Récupère [id => quantité]
        $total = 0;

        foreach ($cart as $quantity) {
            $total += $quantity;
        }

        return $total;
    }

    public function getTotalPrice(): float
    {
        $cart = $this->requestStack->getSession()->get('cart', []);
        $total = 0;

        foreach ($cart as $id => $quantity) {
            $produit = $this->produitRepository->find($id);
            if ($produit) {
                $total += $produit->getPrix() * $quantity;
            }
        }

        return $total;
    }
}