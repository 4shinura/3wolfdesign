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
        return $this->render('legal/information.html.twig', [
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
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            $email = (new Email())
                ->from($data['email'])
                ->to('ash4evergarden@gmail.com') 
                ->subject('Formulaire de Contact - ' . $data['nom'] . ' ' . $data['prenom'])
                ->text($data['message']);

            $mailer->send($email);

            $this->addFlash('success', 'Votre message a bien été envoyé !');
            
            return $this->redirectToRoute('app_legal_contact');
        }

        return $this->render('legal/contact.html.twig', [
            'contactForm' => $form->createView()
        ]);
    }
}