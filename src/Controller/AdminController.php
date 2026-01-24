<?php

namespace App\Controller;

use App\Entity\Utilisateur;
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
}