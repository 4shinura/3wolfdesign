<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CatalogController extends AbstractController
{
    #[Route('/catalogue', name: 'app_catalog')]
    public function index(ProduitRepository $productRepository): Response
    {
        $produits = $productRepository->findBy(['categorie' => 2]);

        return $this->render('catalog/catalog.html.twig', [
            'produits' => $produits,
        ]);
    }

    #[Route('/catalogue/{id}', name: 'app_catalog_show')]
    public function show(?Produit $produit): Response
    {
        if (!$produit) {
            $this->addFlash('warning', 'Désolé, ce produit n\'existe pas ou a été supprimée.');
            return $this->redirectToRoute('app_catalog');
        }
        return $this->render('catalog/show.html.twig', [
            'produit' => $produit,
        ]);
    }
}
