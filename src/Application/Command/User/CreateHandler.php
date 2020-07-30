<?php

/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Zentlix to newer
 * versions in the future. If you wish to customize Zentlix for your
 * needs please refer to https://docs.zentlix.io for more information.
 */

declare(strict_types=1);

namespace Zentlix\UserBundle\Application\Command\User;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Zentlix\MainBundle\Application\Command\CommandHandlerInterface;
use Zentlix\UserBundle\Domain\Group\Entity\UserGroup;
use Zentlix\UserBundle\Domain\Group\Specification\ExistGroupByCodeSpecification;
use Zentlix\UserBundle\Domain\User\Specification\UniqueEmailSpecification;
use Zentlix\UserBundle\Domain\User\Event\User\BeforeCreate;
use Zentlix\UserBundle\Domain\User\Event\User\AfterCreate;
use Zentlix\UserBundle\Domain\User\Entity\User;
use Zentlix\UserBundle\Domain\Group\Repository\GroupRepository;
use Zentlix\UserBundle\Infrastructure\Share\Mailer\Service\MailerInterface;

class CreateHandler implements CommandHandlerInterface
{
    private UniqueEmailSpecification $uniqueEmailSpecification;
    private EntityManagerInterface $entityManager;
    private EventDispatcherInterface $eventDispatcher;
    private MailerInterface $mailer;
    private UserPasswordEncoderInterface $passwordEncoder;
    private GroupRepository $groupRepository;
    private ExistGroupByCodeSpecification $existGroupByCodeSpecification;

    public function __construct(UniqueEmailSpecification $uniqueEmailSpecification,
                                ExistGroupByCodeSpecification $existGroupByCodeSpecification,
                                EntityManagerInterface $entityManager,
                                EventDispatcherInterface $eventDispatcher,
                                MailerInterface $mailer,
                                UserPasswordEncoderInterface $passwordEncoder,
                                GroupRepository $groupRepository)
    {
        $this->uniqueEmailSpecification = $uniqueEmailSpecification;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->mailer = $mailer;
        $this->passwordEncoder = $passwordEncoder;
        $this->groupRepository = $groupRepository;
        $this->existGroupByCodeSpecification = $existGroupByCodeSpecification;
    }

    public function __invoke(CreateCommand $command): void
    {
        $this->validate($command);

        $command->groups = $this->groupRepository->findByCode($command->groups);

        $this->eventDispatcher->dispatch(new BeforeCreate($command));

        $user = new User($command);
        $user->setPassword($this->passwordEncoder->encodePassword($user, $command->plain_password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        if($command->sendRegistrationEmail) {
            $this->mailer->send('user-registration', $user->getEmail()->getValue(), ['user' => $user]);
        }

        $this->eventDispatcher->dispatch(new AfterCreate($user, $command));

        $command->user = $user;
    }

    private function validate(CreateCommand $command): void
    {
        $this->uniqueEmailSpecification->isUnique($command->getEmailObject());
        if(\count($command->groups) === 0) {
            $command->groups = [UserGroup::USER_GROUP];
        }

        foreach ($command->groups as $group) {
            $this->existGroupByCodeSpecification->isExist($group);
        }
    }
}