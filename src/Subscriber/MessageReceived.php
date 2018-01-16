<?php

namespace App\Subscriber;

use App\Entity\Messages;
use App\Event\MessagesEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class MessageReceived implements EventSubscriberInterface
{
    /**
     * @var \Swift_Mailer
     */
    private $mailer;
    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * MessageReceived constructor.
     * @param \Swift_Mailer $mailer
     * @param \Twig_Environment $twig
     */
    public function __construct(\Swift_Mailer $mailer, \Twig_Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    /**
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            MessagesEvents::MESSAGE_RECEIVED => 'onMessageReceived'
        ];
    }

    /**
     * @param GenericEvent $event
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function onMessageReceived(GenericEvent $event)
    {
        /** @var Messages $messageReceived */
        $messageReceived = $event->getSubject();

        $mailMessage = new \Swift_Message($messageReceived->getFrom()->getName() . ' sent you a message');
        $mailMessage->setFrom($messageReceived->getFrom()->getEmail())
            ->setTo($messageReceived->getTo()->getEmail())
            ->setBody(
                $this->twig->render('emails/message_received.html.twig', [
                    'message_received' => $messageReceived
                ]),
                'text/html'
            );

        $this->mailer->send($mailMessage);
    }
}