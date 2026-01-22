<?php

namespace App\Controller;

use App\Form\ChangePasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Utilisateur;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AccountController extends AbstractController
{
    #[Route('/mon-compte', name: 'app_account')]
    #[IsGranted('ROLE_USER')] 
    public function index(): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        return $this->render('account/account.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/mon-compte/modifier-mdp', name: 'app_account_password')]
    #[IsGranted('ROLE_USER')] 
    public function changePassword(
        Request $request, 
        UserPasswordHasherInterface $userPasswordHasher, 
        EntityManagerInterface $entityManager
    ): Response {

        /** @var Utilisateur $user */
        $user = $this->getUser();

        // 2. On crée le formulaire (celui que tu avais déjà !)
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        // 3. Traitement du formulaire
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupération du mot de passe saisi (non haché)
            $newPassword = $form->get('plainPassword')->getData();

            // Hachage et mise à jour
            $user->setPassword(
                $userPasswordHasher->hashPassword($user, $newPassword)
            );

            $entityManager->flush();

            // Message de succès
            $this->addFlash('success', 'Votre mot de passe a bien été mis à jour.');

            return $this->redirectToRoute('app_account');
        }

        return $this->render('account/change_password.html.twig', [
            'passwordForm' => $form->createView(),
        ]);
    }
}
