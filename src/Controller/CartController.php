<?php

namespace App\Controller;

use App\Repository\CommandeRepository;
use \App\Repository\ProduitRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/panier', name: 'app_cart_')]
class CartController extends AbstractController
{
    #[Route('/', name: 'view')]
    public function index(CartService $cartService, ProduitRepository $produitRepository): Response
    {
        $panierSession = $cartService->getFullCart();
        $panierDetaille = [];

        foreach ($panierSession as $id => $quantite) {
            $panierDetaille[] = [
                'produit' => $produitRepository->find($id),
                'quantite' => $quantite
            ];
        }

        return $this->render('cart/cart.html.twig', [
            'items' => $panierDetaille,
            'total' => $cartService->getTotalPrice(),
            'paypal_client_id' => $this->getParameter('paypal_client_id')
        ]);
    }

    #[Route('/add/{id}', name: 'add')]
    public function add(int $id, CartService $cartService, Request $request): Response
    {
        $cartService->add($id);
        $this->addFlash('success', 'Produit ajouté au panier !');

        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }
        
        return $this->redirectToRoute('app_cart_view');
    }

    #[Route('/remove/{id}', name: 'remove')]
    public function remove(int $id, CartService $cartService, Request $request): Response
    {
        $cartService->remove($id);
        $this->addFlash('success', 'Produit retiré du panier.');
        
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }
        
        return $this->redirectToRoute('app_cart_view');
    }

    #[Route('/decrease/{id}', name: 'decrease')]
    public function decrease(int $id, CartService $cartService, Request $request): Response
    {
        $cartService->decrease($id);
        
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }
        
        return $this->redirectToRoute('app_cart_view');
    }

    #[Route('/merci', name: 'success')]
    public function success(Request $request, CommandeRepository $commandeRepository): Response
    {
        $orderId = $request->query->get('id');
        $commande = $commandeRepository->find($orderId);

        // Vérification que la commande existe et appartient bien à l'utilisateur connecté
        if (!$commande || $commande->getUtilisateur() !== $this->getUser()) {
            return $this->redirectToRoute('app_catalog');
        }

        return $this->render('cart/success.html.twig', [
            'commande' => $commande
        ]);
    }
}