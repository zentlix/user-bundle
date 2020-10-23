<?php

/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Zentlix to newer
 * versions in the future. If you wish to customize Zentlix for your
 * needs please refer to https://docs.zentlix.io for more information.
 */

declare(strict_types=1);

namespace Zentlix\UserBundle\UI\Http\Web\Widget\Admin;

use Symfony\Component\Security\Core\Security;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Zentlix\UserBundle\Domain\Admin\Repository\NotificationRepository;

class Notification extends AbstractExtension
{
    private Security $security;
    private NotificationRepository $notificationRepository;

    public function __construct(Security $security, NotificationRepository $notificationRepository)
    {
        $this->security = $security;
        $this->notificationRepository = $notificationRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('admin_notifications_widget', [$this, 'getNotifications'], ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    public function getNotifications(Environment $twig)
    {
        return $twig->render('@UserBundle/admin/widgets/notifications.html.twig', [
            'notifications' => $this->notificationRepository->findLastByUserId($this->security->getUser()->getId())
        ]);
    }
}