<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Produit;
use App\Form\ProduitFormType;
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
    private string $galleryDir;
    private string $catalogDir;

    public function __construct(string $gallery_directory, string $catalog_directory)
    {
        $this->galleryDir = $gallery_directory;
        $this->catalogDir = $catalog_directory;
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function index(UtilisateurRepository $userRepo): Response
    {
        return $this->render('back-office/admin/dashboard.html.twig', [
            'totalUsers' => $userRepo->count([]),
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

    #[Route('/utilisateurs/changer-role/{id}', name: 'switch_role')]
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
                
                // Logique de switch de rôle
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


    #[Route('/produit/nouveau', name: 'product_new')]
    public function newProduct(Request $request, EntityManagerInterface $em): Response 
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitFormType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $targetDir = $produit->isEstAchetable() ? $this->catalogDir : $this->galleryDir;
            $this->handleImageUpload($form, $produit, $targetDir);

            if ($produit->getCategorie()->getId() === 2) {
                $produit->setEstAchetable(true);
            } else {
                $produit->setEstAchetable(false);
            }

            $em->persist($produit);
            $em->flush();

            $this->addFlash('success', 'Ajout réussi !');
            return $this->redirectToRoute('app_gallery');
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
            $targetDir = $produit->isEstAchetable() ? $this->catalogDir : $this->galleryDir;
            $this->handleImageUpload($form, $produit, $targetDir);
        
            if ($produit->getCategorie()->getId() === 2) {
                $produit->setEstAchetable(true);
            } else {
                $produit->setEstAchetable(false);
            }

            $em->flush(); 

            $this->addFlash('success', 'Mise à jour effectuée.');
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
    private function handleImageUpload($form, Produit $produit, string $gallery_directory): void
    {
        /** @var UploadedFile $imageFile */
        $imageFile = $form->get('img_path')->getData();

        if ($imageFile) {
            $newFilename = uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move($gallery_directory, $newFilename);
                $produit->setImg_path($newFilename);
            } catch (FileException $e) {
            }
        }
    }

    #[Route('/produit/supprimer/{id}', name: 'product_delete', methods: ['POST'])]
    public function deleteProduct(Produit $produit, Request $request, EntityManagerInterface $em): Response {
        if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->request->get('_token'))) {
            $em->remove($produit);
            $em->flush();
            $this->addFlash('success', 'La réalisation a été supprimée.');
        }
        return $this->redirectToRoute('app_gallery');
    }
}