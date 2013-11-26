<?php
namespace Wjzijderveld\Command;

use PHPCR\NodeInterface;
use Jackalope\Transport\WorkspaceManagementInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class GenerateDataCommand extends Command
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    public function configure()
    {
        $this
            ->setName('tutorial:generate-data')
            ->setDescription('Loads some basic fixtures to demostrate querying')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Whether to append to the workspace, or replace all nodes');
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \InvalidArgumentException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input    = $input;
        $this->output   = $output;

        $session = $this->getSession();
        $rootNode = $session->getRootNode();

        if (!$this->input->getOption('append')) {
            if ($session->getTransport() instanceOf WorkspaceManagementInterface) {
                $workspace = $session->getWorkspace()->getName();
                $session->getTransport()->deleteWorkspace($workspace);
                $session->getTransport()->createWorkspace($workspace);
                $session->refresh(false);
            }
        }

        $faker = \Faker\Factory::create();
        while (count($rootNode->getNodes()) < 10) {

            try {
                $node = $rootNode->addNode($faker->word);

                $node->setProperty('title', $faker->name);
                $node->setProperty('body', $faker->words(50, true));
            } catch (\PHPCR\ItemExistsException $e) {
                // Ignore, and try again
            }
        }

        $dummyNode = $rootNode->addNode('dummy');
        $dummyNode->setProperty('createdAt', new \DateTime('now'));
        $dummyNode->setProperty('title', $faker->lastName);

        $session->save();

        $this->output->writeln(sprintf('Generated %d nodes under /', count($rootNode)));
        /** @var $childNode NodeInterface */
        foreach ($rootNode as $childNode) {
            $name = $childNode->getName();
            $path = $childNode->getPath();
            $title = $body = null;

            if ($childNode->hasProperty('title')) {
                $title = $childNode->getProperty('title')->getValue();
            }

            if ($childNode->hasProperty('body')) {
                $body = $childNode->getProperty('body')->getValue();
            }

            $this->output->writeln(sprintf(' - <info>%s</info>', $path));
            $this->output->writeln(sprintf('   - Name: <info>%s</info>', $name));
            $this->output->writeln(sprintf('   - Title: <info>%s</info>', $title ?: '<comment>null</comment>'));
            $this->output->writeln(sprintf('   - Body: <info>%s</info>', $body ?: '<comment>null</comment>'));
        }

        $dummyNode = $session->getNode('/dummy');
        $this->output->writeln('Node <info>/dummy</info>');
        $this->output->writeln(sprintf('Dummy node title: <info>%s</info>', $dummyNode->getProperty('title')->getValue()));

        $dummyNode = $rootNode->getNode('dummy');
        $this->output->writeln('Node <info>dummy</info> from <info>/</info>');
        $this->output->writeln(sprintf('Dummy node title: <info>%s</info>', $dummyNode->getProperty('title')->getValue()));

        $nodePaths = array('/', '/dummy');
        $nodes = $session->getNodes($nodePaths);
        $this->output->writeln('');
        $this->output->writeln('Searched for: ' . join(', ', $nodePaths));
        foreach ($nodes as $node) {
            $name = $node->getPath();
            $title = $node->hasProperty('title') ? $node->getProperty('title')->getValue() : null;
            $this->output->writeln(sprintf('- Path: <info>%s</info>', $name));
            $this->output->writeln(sprintf('  - Title: <info>%s</info>', $title ?: '<comment>null</comment>'));
        }

        return 0;
    }

    /**
     * @param mixed $data
     * @throws \InvalidArgumentException
     */
    public function importData($data)
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException('No data found in fixture file');
        }

        $session = $this->getSession();
        $root = $session->getRootNode();


    }

    /**
     * @return \Jackalope\Session
     */
    protected function getSession()
    {
        return $this->getHelper('phpcr')->getSession();
    }
} 