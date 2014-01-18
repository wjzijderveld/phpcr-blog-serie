<?php
/**
 * Created at 16/12/13 21:11
 */

namespace Wjzijderveld\Command;


use PHPCR\NodeType\NodeDefinitionInterface;
use PHPCR\NodeType\NodeTypeInterface;
use PHPCR\NodeType\PropertyDefinitionInterface;
use PHPCR\PropertyType;
use PHPCR\SessionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends Command
{
    public function configure()
    {
        $this->setName('tutorial:info')
            ->setDescription('Returns some basic info about your repository')
            ->addArgument('nodeType', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Specify to get information for only the given nodeType', array())
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Jackalope\Session $session */
        $session = $this->getHelper('phpcr')->getSession();

        $ntm = $session->getWorkspace()->getNodeTypeManager();

        $nodeTypeNames = $input->getArgument('nodeType');
        if (array() !== $nodeTypeNames) {
            foreach ($nodeTypeNames as $nodeType) {
                if (!$ntm->hasNodeType($nodeType)) {
                    $output->writeln(sprintf('<error>Given nodeType "%s" doesn\'t exists.</error>', $nodeType));
                    return 1;
                }
                $nodeTypes[$nodeType] = $ntm->getNodeType($nodeType);
            }
        } else {
            $nodeTypes = $ntm->getAllNodeTypes();
        }

        /** @var NodeTypeInterface $nodeType */
        foreach ($nodeTypes as $name => $nodeType) {
            $output->writeln('<info>'.$name.'</info>');

            $output->writeln('  <comment>Abstract:</comment> ' . ($nodeType->isAbstract() ? 'true' : 'false'));
            $output->writeln('  <comment>Mixin:</comment> ' . ($nodeType->isMixin() ? 'true' : 'false'));

            $superTypes = $nodeType->getSupertypeNames();
            if (count($superTypes)) {
                $output->writeln('  <comment>Supertypes:</comment>');
                foreach ($superTypes as $stName) {
                    $output->writeln('    <comment>></comment> '.$stName);
                }
            }

            $propertyDefinitions = $nodeType->getPropertyDefinitions();

            if (count($propertyDefinitions)) {
                $output->writeln('  <comment>PropertyDefinitions:</comment>');
                /** @var PropertyDefinitionInterface $propertyDefinition */
                foreach ($propertyDefinitions as $propertyDefinition) {
                    $output->writeln(sprintf('    <comment>></comment> %s (%s: %s)',
                        $propertyDefinition->getName(),
                        PropertyType::nameFromValue($propertyDefinition->getRequiredType()),
                        $propertyDefinition->isMandatory() ? 'Required' : 'Optional'
                    ));
                }
            }

            $childNodeDefinitions = $nodeType->getChildNodeDefinitions();
            if (count($childNodeDefinitions)) {
                $output->writeln('  <comment>ChildNodeDefinitions:</comment>');
                /** @var NodeDefinitionInterface $childNodeType */
                foreach ($childNodeDefinitions as $childNodeType) {
                    $output->writeln('    <comment>></comment> ' . $childNodeType->getName() . ' ' . ($childNodeType->isMandatory() ? '(Required)' : '(Optional)'));
                }
            }

        }

        return 0;
    }
} 