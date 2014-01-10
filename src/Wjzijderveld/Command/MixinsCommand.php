<?php
/**
 * Created at 17/12/13 22:37
 */

namespace Wjzijderveld\Command;


use PHPCR\PropertyType;
use PHPCR\SessionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MixinsCommand extends Command
{
    public function configure()
    {
        $this->setName('tutorial:mixins')
            ->setDescription('Some basic code example how to use mixins');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var SessionInterface $session */
        $session = $this->getHelper('phpcr')->getSession();

        $rootNode = $session->getRootNode();

        if ($rootNode->hasNode('mixinExample')) {
            $mixinExample = $rootNode->getNode('mixinExample');
        } else {
            $mixinExample = $rootNode->addNode('mixinExample');
            $mixinExample->addMixin('mix:created');
            $mixinExample->addMixin('mix:lastModified');

            $session->save();
        }

        var_dump($mixinExample->getProperty('jcr:created')->getString());

        $mixinExample->setProperty('jcr:lastModified', new \DateTime('now'));
        $session->save();

        var_dump($mixinExample->getProperty('jcr:created')->getString());
        var_dump($mixinExample->getProperty('jcr:lastModified')->getString());
    }
} 