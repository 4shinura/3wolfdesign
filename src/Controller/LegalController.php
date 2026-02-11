<?php

namespace App\Controller;

use App\Form\ContactFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\RateLimiter\RateLimiterFactory;

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
    public function contact(Request $request, MailerInterface $mailer, RateLimiterFactory $contactFormLimiter): Response
    {
        $form = $this->createForm(ContactFormType::class);
        $form->handleRequest($request);

        // On crée un limiteur basé sur l'IP du visiteur
        $limiter = $contactFormLimiter->create($request->getClientIp());

        if ($form->isSubmitted() && $form->isValid()) {

            // Vérification HoneyPot (Bot Protection)
            $honeypot = $form->get('telephone_pro')->getData();
            if (null !== $honeypot) {
                $this->addFlash('success', 'Votre message a été envoyé, ne vous en faites pas !');
                return $this->redirectToRoute('app_legal_contact');
            }

            // Vérification RateLimiter
            if (false === $limiter->consume(1)->isAccepted()) {
                $this->addFlash('warning', 'Veuillez patienter quelques instants avant d\'envoyer un nouveau message.');
                return $this->redirectToRoute('app_legal_contact');
            }

            $data = $form->getData();

            $email = (new TemplatedEmail())
                ->from(new Address($this->getParameter('contact_mailer_from'), '3wolfdesign Contact'))
                ->to((string) $this->getParameter('contact_mailer_to'))
                ->replyTo($data['email']) 
                ->subject('Formulaire de contact : ' . $data['nom'] . ' ' . $data['prenom'])
                ->htmlTemplate('legal/email.html.twig')
                ->context([
                    'data' => $data,
                    'date' => new \DateTime(),
                ]);

            // On utilise le transport 'contact' (contact@3wolfdesign)
            $email->getHeaders()->addTextHeader('X-Transport', 'contact');

            try {
                $mailer->send($email);
                $this->addFlash('success', 'Votre message a bien été envoyé !');
                return $this->redirectToRoute('app_legal_contact');
            } catch (\Exception $e) {
                $this->addFlash('danger', "Désolé, une erreur est survenue lors de l'envoi.");
            }
        }

        return $this->render('legal/contact.html.twig', [
            'contactForm' => $form->createView()
        ]);
    }
}