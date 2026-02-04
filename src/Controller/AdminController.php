<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Produit;
use App\Form\ProduitFormType;
use App\Repository\ProduitRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    public function index(UtilisateurRepository $userRepo, ProduitRepository $productRepo): Response
    {
        return $this->render('back-office/admin/dashboard.html.twig', [
            'totalUsers' => $userRepo->count([]),
            'totalProducts' => $productRepo->count([])
        ]);
    }

    #[Route('/utilisateurs', name: 'manage_users')]
    public function usersList(Request $request, UtilisateurRepository $repository): Response
    {
        $search = $request->query->get('q', '');
        $users = [];

        if ($search) {
            if (str_starts_with($search, '#')) {
                $id = substr($search, 1);
                $user = $repository->find($id);
                $users = $user ? [$user] : [];
            } else {
                $users = $repository->findByEmailPart($search);
            }
        } else {
            $users = $repository->findAll();
        }

        return $this->render('back-office/admin/manage_users.html.twig', [
            'users' => $users,
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


    #[Route('/produit/nouveau', name: 'product_new')]
    public function newProduct(Request $request, EntityManagerInterface $em): Response 
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitFormType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $produit);

            if ($produit->getCategorie()->getId() === 2) {
                $produit->setEstAchetable(true);
            } else {
                $produit->setEstAchetable(false);
            }

            $targetRoute = $produit->isEstAchetable() ? 'app_catalog' : 'app_gallery';

            $em->persist($produit);
            $em->flush();

            $this->addFlash('success', 'Ajout réussi !');
            return $this->redirectToRoute($targetRoute);
        }

        return $this->render('back-office/admin/product_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Ajouter une réalisation'
        ]);
    }

    #[Route('/produit/modifier/{id}', name: 'product_edit')]
    public function editProduct(Produit $produit, Request $request, EntityManagerInterface $em): Response 
    {
        $form = $this->createForm(ProduitFormType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $produit);

            $em->flush(); 

            $this->addFlash('success', 'Mise à jour effectuée.');

            if ($produit->isEstAchetable()) {
                return $this->redirectToRoute('app_catalog_show', ['id' => $produit->getId()]);
            }
            return $this->redirectToRoute('app_gallery_show', ['id' => $produit->getId()]);
        }

        return $this->render('back-office/admin/product_form.html.twig', [
            'form' => $form->createView(),
            'produit' => $produit,
            'title' => 'Modifier : ' . $produit->getNom()
        ]);
    }

    /**
     * Méthode privée pour éviter de répéter le code d'upload
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
            }
        }
    }

    #[Route('/produit/supprimer/{id}', name: 'product_delete', methods: ['POST'])]
    public function deleteProduct(Produit $produit, Request $request, EntityManagerInterface $em): Response {
        $targetRoute = $produit->isEstAchetable() ? 'app_catalog' : 'app_gallery';
        $imgDir = $this->getParameter('kernel.project_dir') . '/assets/img/produits/';
        if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->request->get('_token'))) {
            $em->remove($produit);
            if (file_exists($imgDir . $produit->getImg_path())) {
                unlink($imgDir . $produit->getImg_path());
            }
            $em->flush();
            $this->addFlash('success', 'La réalisation a été supprimée.');
        }
        return $this->redirectToRoute($targetRoute);
    }
}