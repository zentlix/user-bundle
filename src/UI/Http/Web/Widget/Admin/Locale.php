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

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Zentlix\MainBundle\Domain\Locale\Repository\LocaleRepository;
use Zentlix\UserBundle\Domain\Admin\Service\AdminSettings;

class Locale extends AbstractExtension
{
    private AdminSettings $adminSettings;
    private LocaleRepository $localeRepository;

    public function __construct(AdminSettings $adminSettings, LocaleRepository $localeRepository)
    {
        $this->adminSettings = $adminSettings;
        $this->localeRepository = $localeRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('admin_locales_widget', [$this, 'getLocales'], ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    public function getLocales(Environment $twig)
    {
        return $twig->render('@UserBundle/admin/widgets/locales.html.twig', [
            'currentLocale' => $this->adminSettings->getLocale(),
            'locales'       => $this->localeRepository->findAll()
        ]);
    }
}