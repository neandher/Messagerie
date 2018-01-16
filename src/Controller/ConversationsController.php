<?php

namespace App\Controller;

use App\Entity\Messages;
use App\Entity\User;
use App\Event\MessagesEvents;
use App\Form\MessagesType;
use App\Repository\MessagesRepository;
use App\Repository\UserRepository;
use App\Security\Voter\UserVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ConversationsController
 * @package App\Controller
 *
 * @Route("/conversations", name="conversations_")
 */
class ConversationsController extends AbstractController
{
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var MessagesRepository
     */
    private $messagesRepository;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * ConversationsController constructor.
     * @param UserRepository $userRepository
     * @param MessagesRepository $messagesRepository
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        UserRepository $userRepository,
        MessagesRepository $messagesRepository,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->userRepository = $userRepository;
        $this->messagesRepository = $messagesRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Route("/", name="index")
     * @return Response
     */
    public function index()
    {
        $users = $this->userRepository->findOthers($this->getUser()->getId());
        return $this->render('conversations/index.html.twig', [
            'users' => $users
        ]);
    }

    /**
     * @Route("/show/{user}", name="show")
     * @Method({"GET", "POST"})
     * @param User $user
     * @param Request $request
     * @return Response
     */
    public function show(User $user, Request $request)
    {
        $this->denyAccessUnlessGranted(UserVoter::TALK_TO, $user);

        $authUserId = $this->getUser()->getId();
        $users = $this->userRepository->findOthers($authUserId);
        $messages = $this->messagesRepository->getMessagesFor(
            $this->getUser()->getId(),
            $user->getId(),
            $request->query->get('page', 1)
        );

        /** @var User $listUser */
        foreach ($users as $listUser) {
            if ($listUser->getUnread() != NULL && $listUser->getUnread()['from_id'] == $user->getId()
            ) {
                $this->messagesRepository->readAllFrom($user->getId(), $authUserId);
                $listUser->setUnread(null);
            }
        }

        $message = new Messages();
        $message->setFrom($this->getUser())
            ->setTo($user);
        $form = $this->createForm(MessagesType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($message);
            $em->flush();

            $this->eventDispatcher->dispatch(MessagesEvents::MESSAGE_RECEIVED, new GenericEvent($message));

            return $this->redirectToRoute('conversations_show', [
                'user' => $user->getId()
            ]);
        }

        return $this->render('conversations/show.html.twig', [
            'users' => $users,
            'user' => $user,
            'messages' => $messages,
            'form' => $form->createView()
        ]);
    }
}
