<?php

/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Zentlix to newer
 * versions in the future. If you wish to customize Zentlix for your
 * needs please refer to https://docs.zentlix.io for more information.
 */

declare(strict_types=1);

namespace Zentlix\UserBundle\Domain\Mailer\Specification;

use Symfony\Contracts\Translation\TranslatorInterface;
use Zentlix\UserBundle\Domain\Mailer\Service\Events;
use function is_null;

final class ExistEventSpecification
{
    private Events $events;
    private TranslatorInterface $translator;

    public function __construct(Events $events, TranslatorInterface $translator)
    {
        $this->events = $events;
        $this->translator = $translator;
    }

    public function isExist(string $event): void
    {
        if(is_null($this->events->find($event))) {
            throw new \DomainException(sprintf($this->translator->trans('zentlix_user.mailer.event_not_found'), $event));
        }
    }

    public function __invoke($event): void
    {
        $this->isExist($event);
    }
}