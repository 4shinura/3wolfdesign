<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GalleryController extends AbstractController
{
    #[Route('/galerie', name: 'app_gallery')]
    public function index(ProduitRepository $productRepository): Response
    {
        $realisations = $productRepository->findBy(['categorie' => 1]);

        return $this->render('gallery/gallery.html.twig', [
            'realisations' => $realisations,
        ]);
    }

    #[Route('/galerie/{id}', name: 'app_gallery_show')]
    public function show(?Produit $produit): Response
    {
        if (!$produit) {
            $this->addFlash('warning', 'Désolé, ce produit n\'existe pas ou a été supprimée.');
            return $this->redirectToRoute('app_gallery');
        }
        return $this->render('gallery/show.html.twig', [
            'produit' => $produit,
        ]);
    }
}
