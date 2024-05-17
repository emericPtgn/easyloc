<?php

namespace App\Service\Mail;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;

class MailerService {
    
    public function __construct(private MailerInterface $mailer) { }


    public function sendEmail(MailerInterface $mailer): void
    {
        $email = (new Email())
            ->from('easyloc2024@gmail.com')
            ->to('petitgenet.emeric@gmail.com')
            //->cc('cc@example.com')
            //->bcc('bcc@example.com')
            //->replyTo('fabien@example.com')
            //->priority(Email::PRIORITY_HIGH)
            ->subject('Time for Symfony Mailer!')
            ->text('Sending emails is fun again!')
            ->html('<p>See Twig integration for better HTML integration!</p>');

        $this->mailer->send($email);

        // ...
    }


}