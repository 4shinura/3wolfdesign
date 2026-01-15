<?php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class LegalController extends AbstractController
{
    #[Route('/mentions-legales', name: 'app_legal_informations')]
    public function legal_infos(): Response
    {
        return $this->render('legal/informations.html.twig', [
            'controller_name' => 'LegalController',
        ]);
    }

    #[Route('/cgv', name: 'app_legal_cgv')]
    public function cgv(): Response
    {
        return $this->render('legal/cgv.html.twig', [
            'controller_name' => 'LegalController',
        ]);
    }

    #[Route('/cgu', name: 'app_legal_cgu')]
    public function cgu(): Response
    {
        return $this->render('legal/cgu.html.twig', [
            'controller_name' => 'LegalController',
        ]);
    }

    #[Route('/contact', name: 'app_legal_contact')]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        // 1. On crée l'objet formulaire
        $form = $this->createForm(ContactType::class);

        // 2. On demande au formulaire d'analyser la requête
        $form->handleRequest($request);

        // 3. Si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // On récupère les données nettoyées
            $data = $form->getData();

            // Création de l'email avec les données du formulaire
            $email = (new Email())
                ->from($data['email'])
                ->to('ash4evergarden@gmail.com') 
                ->subject('Formulaire de Contact - ' . $data['nom'] . ' ' . $data['prenom'])
                ->text($data['message']);

            $mailer->send($email);

            $this->addFlash('success', 'Votre message a bien été envoyé !');
            
            return $this->redirectToRoute('app_legal_contact');
        }

        // 4. On envoie la VUE du formulaire au template
        return $this->render('legal/contact.html.twig', [
            'contactForm' => $form->createView(), // C'est ici que la variable est créée pour Twig
        ]);
    }
}