<?php

namespace Eckinox\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Eckinox\Controller\Application\SoftwareController;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Update extends Command
{
    const ALLOWED_DATA_EXTENSION = [ 'json' ];

    private $container;

    public function __construct($name = null, ContainerInterface $container) {
        parent::__construct($name);
        $this->container = $container;
    }


    protected function configure()
    {
        $this->setName('eckinox:update')
            ->setDescription('Updates data from updates files')
            ->setHelp('This command allows you to add data from updates files found in private/updates/ folder');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            '===========================',
            ' Updating your application ',
            '===========================',
        ]);

        $this->_update_database($output);
    }

    protected function _update_database($output) {
        $doctrine = $this->container->get('doctrine');

        $stack = [];
        $em = $doctrine->getManager();

        foreach((array)$this->container->getParameter('app.updates.path') as $path) {

            if(is_dir($path)) {
                foreach(scandir($path) as $item) {
                    $ext = pathinfo($item)['extension'];

                    if ( in_array($ext, static::ALLOWED_DATA_EXTENSION) ) {
                        switch($ext) {
                            case "json":
                                foreach(json_decode(file_get_contents($path.$item), true) as $item) {
                                    $stack[] = $item;
                                }

                                break;
                        }
                    }

                    foreach($stack as $item) {
                        $repo = $doctrine->getRepository($item['entity']);

                        $rows = $repo->updateFromArray($item['data'] , $item['update_key']);

                        foreach($rows as $savable_row) {
                            $em->persist($savable_row);
                        }

                        if ( $rows ) {
                            $output->writeln([ 'Added '.count($rows).' rows to entity '.$item['entity'] ]);
                        }
                    }

                    //$em->flush();
                }
            }
        }
    }
}
