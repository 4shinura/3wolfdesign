<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Form\ChangePasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Utilisateur;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user', name: 'app_user_')]
#[IsGranted('ROLE_USER')]
class AccountController extends AbstractController
{
    #[Route('/mon-compte', name: 'account')] 
    public function index(): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        return $this->render('back-office/user/account.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/mon-compte/mes-commandes', name: 'orders')]
    public function orders(EntityManagerInterface $em): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        // On récupère ses commandes triées par date
        $commandes = $em->getRepository(Commande::class)->findBy(
            ['utilisateur' => $user],
            ['dateCreation' => 'DESC']
        );

        return $this->render('back-office/user/orders.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/mon-compte/modifier-mdp', name: 'password')]
    public function changePassword(
        Request $request, 
        UserPasswordHasherInterface $userPasswordHasher, 
        EntityManagerInterface $entityManager
    ): Response {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $oldPassword = $form->get('oldPassword')->getData();

            if (!$userPasswordHasher->isPasswordValid($user, $oldPassword)) {
                $this->addFlash('danger', 'Le mot de passe actuel est incorrect.');
                return $this->render('back-office/user/change_password.html.twig', [
                    'passwordForm' => $form->createView(),
                ]);
            }

            $newPassword = $form->get('plainPassword')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $newPassword));

            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a bien été mis à jour.');
            return $this->redirectToRoute('app_user_account');
        }

        return $this->render('back-office/user/change_password.html.twig', [
            'passwordForm' => $form->createView(),
        ]);
    }
}
