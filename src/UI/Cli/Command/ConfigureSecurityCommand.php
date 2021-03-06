<?php

/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Zentlix to newer
 * versions in the future. If you wish to customize Zentlix for your
 * needs please refer to https://docs.zentlix.io for more information.
 */

declare(strict_types=1);

namespace Zentlix\UserBundle\UI\Cli\Command;

use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Zentlix\UserBundle\Domain\User\Entity\User;
use Zentlix\UserBundle\Domain\User\Service\Authenticator;

class ConfigureSecurityCommand extends ConsoleCommand {

    private Filesystem $filesystem;
    private string $projectDir;

    public function __construct(Filesystem $filesystem, string $projectDir)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
        $this->projectDir = $projectDir;
    }

    protected function configure(): void
    {
        $this
            ->setName('zentlix_user:configure:security')
            ->setDescription('Create default security config file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $this->filesystem->remove($this->projectDir . '/config/packages/security.yaml');
        $this->filesystem->dumpFile($this->projectDir . '/config/packages/security.yaml', Yaml::dump($this->getConfig(), 6));

        $io->success('Security bundle configured successfully!');
    }

    private function getConfig(): array
    {
        return [
            'security' => [
                'encoders' => [
                    User::class => [
                        'algorithm' => 'auto'
                    ]
                ],
                'providers' => [
                    'users' => [
                        'entity' => [
                            'class'    => User::class,
                            'property' => 'email'
                        ]
                    ]
                ],
                'firewalls' => [
                    'dev' => [
                        'pattern'  => '^/(_(profiler|wdt)|css|images|js)/',
                        'security' => false
                    ],
                    'admin_secured_area' => [
                        'pattern'   => '^/%admin_path%',
                        'anonymous' => true,
                        'context'   => 'zentlix_auth',
                        'guard'     => [
                            'authenticators' => [
                                Authenticator::class
                            ],
                            'entry_point' => Authenticator::class
                        ],
                        'provider'   => 'users',
                        'form_login' => [
                            'login_path'          => 'admin.login',
                            'check_path'          => '/%admin_path%/login',
                            'default_target_path' => '/%admin_path%'
                        ],
                        'logout' => [
                            'path' => 'app_logout'
                        ]
                    ],
                    'user_secured_area' => [
                        'pattern'   => '^/',
                        'anonymous' => true,
                        'context'   => 'zentlix_auth',
                        'guard' => [
                            'authenticators' => [
                                Authenticator::class
                            ],
                            'entry_point' => Authenticator::class
                        ],
                        'provider'   => 'users',
                        'form_login' => [
                            'login_path'          => 'app_login',
                            'check_path'          => '/login',
                            'default_target_path' => '/profile'
                        ],
                        'logout' => [
                            'path' => 'app_logout'
                        ]
                    ]
                ],
                'access_control' => [
                    [
                        'path'  => '^/login',
                        'roles' => 'IS_AUTHENTICATED_ANONYMOUSLY'
                    ],
                    [
                        'path'  => '^/%admin_path%/login',
                        'roles' => 'IS_AUTHENTICATED_ANONYMOUSLY'
                    ],
                    [
                        'path'  => '^/%admin_path%',
                        'roles' => 'ROLE_ADMIN'
                    ],
                    [
                        'path'  => '^/profile',
                        'roles' => 'ROLE_USER'
                    ]
                ]
            ]
        ];
    }
}