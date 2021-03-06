<?php

namespace Eckinox\Command;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class Install extends Command
{
    const ALLOWED_DATA_EXTENSION = [ 'json' ];

    private $doctrineRegistry;
    private $parameterBag;
    private $passwordHasher;
    private $output;

    public function __construct($name = null, ManagerRegistry $doctrineRegistry, ParameterBagInterface $parameterBag, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct($name);
        
        $this->doctrineRegistry = $doctrineRegistry;
        $this->parameterBag = $parameterBag;
        $this->passwordHasher = $passwordHasher;
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
        $this->migrateDatabase();
        $this->installAssets();
        $this->createDeveloperUser();
        // $this->clearCache();
    }

    protected function moveConfigFiles() {
        $symfony_config_path = $this->parameterBag->get('app.symfony_config.path');
        $eckinox_config_path = $this->parameterBag->get('app.eckinox_config.path');
        $config = [
            'packages/eckinox.yaml',
            'packages/security.yaml',
            'packages/prod/monolog.yaml',
            'routes/eckinox.yaml'
        ];

        foreach($config as $path) {
            copy($eckinox_config_path.$path, $symfony_config_path.$path);

            $this->output->writeln(sprintf('The file %s has been created.', $symfony_config_path.$path));
        }

        $translationConfigPath = sprintf('%s%s', $symfony_config_path, 'packages/translation.yaml');
        if(file_exists($translationConfigPath)) {
            $content = file_get_contents($translationConfigPath);
            $content = str_replace('default_locale: en', 'default_locale: fr_CA', $content);

            file_put_contents($translationConfigPath, $content);

            $this->output->writeln($translationConfigPath);
        }

        $this->output->writeln('');
    }

    protected function createFolders() {
        $folders = [
            sprintf('%s/%s', $this->parameterBag->get('kernel.project_dir'), 'var/translations/'),
            sprintf('%s/%s', $this->parameterBag->get('kernel.project_dir'), 'var/data/')
        ];

        foreach($folders as $folder) {
            if(!file_exists($folder)) {
                mkdir($folder, 0755);

                $this->output->writeln(sprintf('The folder %s has been created.', $folder));
            } else {
                $this->output->writeln(sprintf('The folder %s already exists.', $folder));
            }
        }

        $this->output->writeln('');
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

        $this->output->writeln('');
    }

    protected function installAssets() {
        $this->output->writeln('Running bin/console assets:install');

        $command = $this->getApplication()->find('assets:install');
        $input = new ArrayInput(['command' => 'assets:install']);
        $returnCode = $command->run($input, $this->output);
    }

    protected function createDeveloperUser() {
        $userClass = \Eckinox\Entity\Application\User::class;
        $doctrine = $this->doctrineRegistry;
        $em = $doctrine->getManager();
        $userEmail = 'dev@eckinox.ca';
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

            $encoder = $this->passwordHasher;
            $encoded = $encoder->hashPassword($user, $userPassword);

            $user->setPassword($encoded);

            $em->persist($user);
            $em->flush();

            $this->output->writeln(sprintf("The user %s has been created.", $userEmail));
            $this->output->writeln(sprintf("The password is %s", $userPassword));
            $this->output->writeln("");
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
