<?php

namespace Eckinox\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Install extends Command
{
    const ALLOWED_DATA_EXTENSION = [ 'json' ];

    private $container;
    private $output;

    public function __construct($name = null, ContainerInterface $container) {
        parent::__construct($name);
        $this->container = $container;
    }


    protected function configure()
    {
        $this->setName('eckinox:install')
            ->setDescription('Move the configuration files and create the developer user.')
            ->setHelp('');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $this->output->writeln([
            '=============================',
            ' Installing your application ',
            '=============================',
        ]);

        $this->moveConfigFiles();
        $this->createFolders();
        //$this->clearCache();
        $this->migrateDatabase();
        $this->installAssets();
        $this->createDeveloperUser();
    }

    protected function moveConfigFiles() {
        $symfony_config_path = $this->container->getParameter('app.symfony_config.path');
        $eckinox_config_path = $this->container->getParameter('app.eckinox_config.path');
        $config = [
            'packages/eckinox.yaml',
            'packages/security.yaml',
            'packages/prod/monolog.yaml',
            'routes/eckinox.yaml'
        ];

        foreach($config as $path) {
            copy($eckinox_config_path.$path, $symfony_config_path.$path);

            $this->output->writeln('Moved ' . $eckinox_config_path.$path . ' to ' . $symfony_config_path.$path);
        }
    }

    protected function createFolders() {
        $folders = [
            $this->container->getParameter('app.translations.custom'),
            $this->container->getParameter('app.data.path_custom')
        ];

        foreach($folders as $folder) {
            mkdir($folder, 0755);
        }
    }

    protected function clearCache() {
        $this->output->writeln('Running bin/console cache:clear');

        $command = $this->getApplication()->find('cache:clear');
        $input = new ArrayInput(['command' => 'cache:clear']);
        $returnCode = $command->run($input, $this->output);
    }

    protected function migrateDatabase() {
        $this->output->writeln('Running bin/console doctrine:migrations:diff');

        $runMigrations = true;
        $diffOutput = new BufferedOutput();
        try {
            $command = $this->getApplication()->find('doctrine:migrations:diff');
            $input = new ArrayInput(['command' => 'doctrine:migrations:diff']);
            $returnCode = $command->run($input, $diffOutput);
        } catch (\Doctrine\Migrations\Generator\Exception\NoChangesDetected $e) {
            $runMigrations = false;
        }

        $diffResult = $diffOutput->fetch();
        $this->output->write($diffResult);

        # Run the "migrate" command only if migrations have been generated
        if ($runMigrations) {
            $this->output->writeln('Running bin/console doctrine:migrations:migrate');

            $command = $this->getApplication()->find('doctrine:migrations:migrate');
            $input = new ArrayInput(['command' => 'doctrine:migrations:migrate']);
            $returnCode = $command->run($input, $this->output);
        } else {
            $this->output->writeln('No need to run the migrate command: there are no new migrations to apply.');
        }
    }

    protected function installAssets() {
        $this->output->writeln('Running bin/console assets:install');

        $command = $this->getApplication()->find('assets:install');
        $input = new ArrayInput(['command' => 'assets:install']);
        $returnCode = $command->run($input, $this->output);
    }

    protected function createDeveloperUser() {
        $userClass = $this->container->getParameter('user_class');
        $doctrine = $this->container->get('doctrine');
        $em = $doctrine->getManager();
        $userEmail = 'bundle@eckinox.ca';
        $userPassword = $this->randomRassword();
        $user = $doctrine->getRepository($userClass)->findBy(['username' => $userEmail]);

        if($user) {
            $this->output->writeln('The user '. $userEmail .' already exists.');
        } else {
            $user = new $userClass();

            $user->setFullName('Eckinox User')
                ->setEmail($userEmail)
                ->setUsername($userEmail)
                ->setPrivileges(['USER_LIST', 'USER_CREATE_EDIT', 'USER_EDIT_PRIVILEGES']);

            $encoder = $this->container->get('security.password_encoder');
            $encoded = $encoder->encodePassword($user, $userPassword);

            $user->setPassword($encoded);

            $em->persist($user);
            $em->flush();

            $this->output->writeln(sprintf("The user %s has been created.", $userEmail));
            $this->output->writeln(sprintf("The password is %s", $userPassword));
        }
    }

    protected function randomRassword($length = 15) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $random = mt_rand(0, strlen($chars) - 1);

            $password .= $chars[$random];
        }

        return $password;
    }

}
