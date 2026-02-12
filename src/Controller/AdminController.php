<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Utilisateur;
use App\Entity\Produit;
use App\Form\ProduitFormType;
use App\Repository\CommandeRepository;
use App\Repository\ProduitRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin', name: 'app_admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(UtilisateurRepository $userRepo, ProduitRepository $productRepo, CommandeRepository $commandeRepo): Response
    {
        return $this->render('back-office/admin/dashboard.html.twig', [
            'totalUsers' => $userRepo->count([]),
            'totalProducts' => $productRepo->count([]),
            'totalCommandes'=> $commandeRepo->countOrders(),
            'pendingOrders' => $commandeRepo->countPendingOrders(),
        ]);
    }

    #[Route('/utilisateurs', name: 'manage_users')]
    public function usersList(Request $request, UtilisateurRepository $repository, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('q', '');

        $pagination = $paginator->paginate(
            $repository->getPaginationQuery($search), 
            $request->query->getInt('page', 1), 
            15
        );

        return $this->render('back-office/admin/manage_users.html.twig', [
            'users' => $pagination,
            'searchTerm' => $search
        ]);
    }

    #[Route('/utilisateurs/changer-role/{id}', name: 'switch_role_user')]
    public function confirmSwitchRole(
        Utilisateur $user, 
        Request $request, 
        UserPasswordHasherInterface $passwordHasher, 
        EntityManagerInterface $em
    ): Response {
        if ($request->isMethod('POST')) {
            $password = $request->request->get('_password');
            
            /** @var Utilisateur $admin */
            $admin = $this->getUser();
            if ($passwordHasher->isPasswordValid($admin, $password)) {
                
                if (in_array('ROLE_ADMIN', $user->getRoles())) {
                    $user->setRoles([]);
                    $this->addFlash('success', "L'utilisateur a été rétrogradé.");
                } else {
                    $user->setRoles(['ROLE_ADMIN']);
                    $this->addFlash('success', "L'utilisateur est désormais Admin !");
                }

                $em->flush();
                return $this->redirectToRoute('app_admin_manage_users');
            }

            $this->addFlash('danger', 'Mot de passe incorrect, action annulée.');
        }

        return $this->render('back-office/admin/confirmation.html.twig', [
            'targetUser' => $user,
            'actionTitle' => "Modification des droits"
        ]);
    }

    #[Route('/utilisateurs/supprimer/{id}', name: 'delete_user')]
    public function deleteUser(
        Utilisateur $user, 
        Request $request, 
        UserPasswordHasherInterface $passwordHasher, 
        EntityManagerInterface $em
    ): Response {
        if ($request->isMethod('POST')) {
            $password = $request->request->get('_password');
            
            /** @var Utilisateur $admin */
            $admin = $this->getUser();

            if ($passwordHasher->isPasswordValid($admin, $password)) {
                
                if ($user === $admin) {
                    $this->addFlash('danger', 'Vous ne pouvez pas supprimer votre propre compte.');
                    return $this->redirectToRoute('app_admin_manage_users');
                }

                $em->remove($user);
                $em->flush();

                $this->addFlash('success', "Le compte de l'utilisateur a été supprimé définitivement.");
                return $this->redirectToRoute('app_admin_manage_users');
            }

            $this->addFlash('danger', 'Mot de passe incorrect, suppression annulée.');
        }

        return $this->render('back-office/admin/confirmation.html.twig', [
            'targetUser' => $user,
            'actionTitle' => "Suppression de l'utilisateur"
        ]);
    }

    #[Route('/images', name: 'manage_images')]
    public function maintenanceImages(ProduitRepository $repo): Response
    {
        $produits = $repo->findAll();
        $imagesEnBdd = [];
        foreach ($produits as $p) {
            if ($p->getImg_path()) {
                $imagesEnBdd[] = $p->getImg_path();
            }
        }

        $directory = $this->getParameter('kernel.project_dir') . '/assets/img/produits';
        $imagesInutilisees = [];
        $totalImagesPhysiques = 0;

        if (is_dir($directory)) {
            $files = array_diff(scandir($directory), ['.', '..']);
            
            foreach ($files as $file) {
                if (is_file($directory . '/' . $file)) {
                    $totalImagesPhysiques++;
                    
                    if (!in_array($file, $imagesEnBdd)) {
                        $imagesInutilisees[] = [
                            'name' => $file,
                            'path' => 'img/produits/' . $file,
                            'full_path' => $directory . '/' . $file
                        ];
                    }
                }
            }
        }

        return $this->render('back-office/admin/manage_images.html.twig', [
            'imagesInutilisees' => $imagesInutilisees,
            'totalImages' => $totalImagesPhysiques
        ]);
    }

    #[Route('/images/supprimer', name: 'delete_images', methods: ['POST'])]
    public function deleteUnusedImages(Request $request): Response
    {
        $imagesToDelete = $request->request->all('images'); 

        foreach ($imagesToDelete as $fullPath) {
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        $this->addFlash('success', count($imagesToDelete) . ' images inutilisées ont été supprimées.');
        return $this->redirectToRoute('app_admin_manage_images');
    }

    #[Route('/produit', name: 'manage_products')]
    public function product(Request $request, ProduitRepository $produitRepository, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('q'); 
        
        $pagination = $paginator->paginate(
            $produitRepository->getPaginationQuery($search),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('back-office/admin/manage_products.html.twig', [
            'produits' => $pagination,
            'searchTerm' => $search
        ]);
    }

    #[Route('/produit/nouveau', name: 'product_new')]
    public function newProduct(Request $request, EntityManagerInterface $em): Response 
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitFormType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $produit);

            $em->persist($produit);
            $em->flush();

            $this->addFlash('success', 'Ajout réussi !');

            // Redirection : priorité au referer, sinon route par défaut selon le type
            $referer = $request->headers->get('referer');
            if ($referer) {
                return $this->redirect($referer);
            }

            $targetRoute = $produit->isEstAchetable() ? 'app_catalog' : 'app_gallery';
            return $this->redirectToRoute($targetRoute);
        }

        return $this->render('back-office/admin/product_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Ajouter une réalisation'
        ]);
    }

    #[Route('/produit/modifier/{id}', name: 'product_edit')]
    public function editProduct(?Produit $produit, Request $request, EntityManagerInterface $em): Response 
    {
        $referer = $request->headers->get('referer');

        if (!$produit) {
            $this->addFlash('warning', 'Désolé, ce produit n\'existe pas ou a été supprimée.');
            if ($referer) {
                return $this->redirect($referer);
            }
            return $this->redirectToRoute('app_catalog');
        }

        $form = $this->createForm(ProduitFormType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $produit);
            $em->flush(); 

            $this->addFlash('success', 'Mise à jour effectuée.');

            // Redirection : priorité au referer, sinon route par défaut selon le type
            if ($referer) {
                return $this->redirect($referer);
            }

            $targetRoute = $produit->isEstAchetable() ? 'app_catalog_show' : 'app_gallery_show';
            return $this->redirectToRoute($targetRoute, ['id' => $produit->getId()]);
        }

        return $this->render('back-office/admin/product_form.html.twig', [
            'form' => $form->createView(),
            'produit' => $produit,
            'title' => 'Modifier : ' . $produit->getNom()
        ]);
    }

    #[Route('/produit/supprimer/{id}', name: 'product_delete', methods: ['POST'])]
    public function deleteProduct(?Produit $produit, Request $request, EntityManagerInterface $em): Response 
    {
        $referer = $request->headers->get('referer');
        $imgDir = $this->getParameter('kernel.project_dir') . '/assets/img/produits/';

        if (!$produit) {
            $this->addFlash('warning', 'Désolé, ce produit n\'existe pas ou a été supprimée.');
            if ($referer) {
                return $this->redirect($referer);
            }
            return $this->redirectToRoute('app_catalog');
        }

        if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->request->get('_token'))) {
            // Suppression physique de l'image
            if ($produit->getImg_path() && file_exists($imgDir . $produit->getImg_path())) {
                unlink($imgDir . $produit->getImg_path());
            }

            $em->remove($produit);
            $em->flush();
            $this->addFlash('success', 'La réalisation a été supprimée.');
        }

        // Redirection : priorité au referer, sinon route par défaut selon le type
        if ($referer) {
            return $this->redirect($referer);
        }

        $targetRoute = $produit->isEstAchetable() ? 'app_catalog' : 'app_gallery';
        return $this->redirectToRoute($targetRoute);
    }

    /**
     * Méthode privée pour la gestion de l'image
     */
    private function handleImageUpload($form, Produit $produit): void
    {
        /** @var UploadedFile $imageFile */
        $imageFile = $form->get('img_path')->getData();
        $imgDir = $this->getParameter('kernel.project_dir') . '/assets/img/produits';

        if ($imageFile) {
            $newFilename = uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move($imgDir, $newFilename);
                $produit->setImg_path($newFilename);
            } catch (FileException $e) {
                dump($e);
            }
        }
    }

    #[Route('/commandes', name: 'manage_orders')]
    public function orders(CommandeRepository $commandeRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $totalVentes = $commandeRepository->getTotalSales();
        $totalVentesMois = $commandeRepository->getTotalSalesThisMonth();

        $search = $request->query->get('search');
        $status = $request->query->get('status'); 
        $sortBy = $request->query->get('sortBy', 'dateCreation'); 
        $order = $request->query->get('order', 'DESC'); // Défaut DESC pour voir les dernières commandes

        $pagination = $paginator->paginate(
            $commandeRepository->getPaginationQuery($search, $status, $sortBy, $order),
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('back-office/admin/manage_orders.html.twig', [
            'commandes' => $pagination,
            'totalVentes' => $totalVentes,
            'totalVentesMois' => $totalVentesMois,
            'currentSort' => $sortBy,
            'currentOrder' => $order,
            'searchTerm' => $search
        ]); 
    }

    #[Route('/commande/{id}', name: 'show_order')]
    public function show(?Commande $commande): Response
    {   
        if (!$commande) {
            $this->addFlash('warning', 'Désolé, cette commande n\'existe pas ou a été supprimée.');
            return $this->redirectToRoute('app_admin_manage_orders');
        }

        return $this->render('back-office/admin/show_order.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/commande/statut/{id}', name: 'update_status_order', methods: ['POST'])]
    public function updateStatus(?Commande $commande, EntityManagerInterface $em): Response
    {
        if (!$commande) {
            $this->addFlash('warning', 'Désolé, cette commande n\'existe pas ou a été supprimée.');
            return $this->redirectToRoute('app_admin_manage_orders');
        }

        $commande->setStatus('Terminé');
        $em->flush();

        $this->addFlash('success', "Commande {$commande->getReference()} marquée comme terminée.");
        return $this->redirectToRoute('app_admin_manage_orders');
    }
}