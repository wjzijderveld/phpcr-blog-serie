<?php
/**
 * Created at 11/01/14 12:15
 */

namespace Wjzijderveld\Command;


use Jackalope\Factory;
use Jackalope\NodeType\NodeType;
use Jackalope\Session;
use PHPCR\PathNotFoundException;
use PHPCR\PropertyType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateNodeTypeCommand extends Command
{
    public function configure()
    {
        $this
            ->setName('tutorial:create-nodetype')
            ->setDescription('Example of how to create a custom NodeType')
            ->addOption('demo', 'd', InputOption::VALUE_NONE, 'Show demo of customNodeType')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $session = $this->getSession();

        if (!$input->getOption('demo')) {

            $session->getWorkspace()->getNamespaceRegistry()->registerNamespace('acme', 'http://acme.example.com/phpcr/1.0');

            $nodeTypesDocument = new \DOMDocument();
            $nodeTypesDocument->load(__DIR__ . '/../Resources/data/customNodeTypes.xml');
            $xpath = new \DOMXPath($nodeTypesDocument);
            foreach ($xpath->query('//nodeType') as $nodeTypeElement) {
                $nodeType = new NodeType(new Factory(), $session->getWorkspace()->getNodeTypeManager(), $nodeTypeElement);
                $session->getWorkspace()->getNodeTypeManager()->registerNodeType($nodeType, true);
            }

            $session->save();
        } else {
            $this->executeDemo();
        }
    }

    public function executeDemo()
    {
        $session = $this->getSession();

        $rootNode = $session->getRootNode();
        try {
            $session->removeItem('/customNodeTypeNode');
        } catch (PathNotFoundException $e) {
            // We don't care, we were removing it anyway
        }

        $productNode = $rootNode->addNode('customNodeTypeNode', 'acme:product');
        $productNode->setProperty('acme:rrpPrice', 3.14, PropertyType::DECIMAL);
        $productNode->setProperty('jcr:title', 'FooProduct');
        $productNode->setProperty('foo', 'bar');

        $session->save();
    }

    /**
     * @return Session
     */
    protected function getSession()
    {
        return $this->getHelper('session')->getSession();
    }
} 