<?php

// src/Controller/PaymentController.php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\DetailsCommande;
use App\Repository\ProduitRepository;
use App\Service\CartService;
use App\Service\PaypalService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/paypal/order', name: 'app_paypal_')]
class PaymentController extends AbstractController
{
    #[Route('/create', name: 'create_order', methods: ['POST'])]
    public function createOrder(CartService $cartService, PaypalService $paypalService): JsonResponse
    {
    $total = $cartService->getTotalPrice();
    $result = $paypalService->createOrder($total);
    
    return new JsonResponse($result);
    }

    #[Route('/capture/{paypalOrderId}', name: 'capture_order', methods: ['POST'])]
    public function captureOrder(
        string $paypalOrderId, 
        CartService $cartService,
        EntityManagerInterface $em,
        ProduitRepository $produitRepository,
        PaypalService $paypalService
        ): JsonResponse
    {
        try {
            // On demande à PayPal de confirmer le prélèvement
            $result = $paypalService->captureOrder($paypalOrderId);

            // Si le paiement est réussi
            if (isset($result['status']) && $result['status'] === 'COMPLETED') {

                // 1. Création de la Commande,

                // Montant exact capturé par PayPal, sinon montant par le panier
                $amountPaid = $result['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? $cartService->getTotalPrice(); 

                $commande = new Commande();
                $commande->setReference($result['id']); 
                $commande->setPrixTotal((float)$amountPaid);
                $commande->setStatus('Payée');
                
                // On récupère l'utilisateur connecté
                $user = $this->getUser();
                if (!$user) {
                    return new JsonResponse(['error' => 'Utilisateur non connecté'], 403);
                }
                $commande->setUtilisateur($user);

                $em->persist($commande);

                // 2. Création des DetailsCommande
                $panier = $cartService->getFullCart(); 
                
                foreach ($panier as $id => $quantite) {
                    $produit = $produitRepository->find($id);
                    if ($produit) {
                        $details = new DetailsCommande();
                        $details->setCommande($commande);
                        $details->setProduit($produit);
                        $details->setQuantite($quantite);
                        $details->setPrixAchat($produit->getPrix()); 
                        
                        $em->persist($details);
                    }
                }

                // 3. Sauvegarde en base de données
                $em->flush();

                // 4. Nettoyage
                $cartService->emptyCart(); 

                return new JsonResponse([
                    'status' => 'success',
                    'message' => 'Paiement validé !',
                    'details' => $result,
                    'orderId' => $commande->getId()
                ]);
            }

            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
            // dd($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }
}