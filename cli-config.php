<?php
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Yaml\Yaml;

// Jackalope already included the composer autoloader, so we don't have to here

$configFile = __DIR__ . '/config.yml';
if (!file_exists($configFile)) {
    $configFile = $configFile .= '.dist';
}

try {
    $config = Yaml::parse(file_get_contents($configFile));

    // In this tutorial, we assume all config variables are present
    switch ($config['jackalope']['transport']) {
        case 'jackalope-doctrine-dbal':
            $dbConnection = \Doctrine\DBAL\DriverManager::getConnection(array(
                'driver'    => $config['jackalope']['dbal']['driver'],
                'host'      => $config['jackalope']['dbal']['hostname'],
                'user'      => $config['jackalope']['dbal']['username'],
                'password'  => $config['jackalope']['dbal']['password'],
                'dbname'    => $config['jackalope']['dbal']['database'],
            ));

            $factory = new \Jackalope\RepositoryFactoryDoctrineDBAL();
            $repository = $factory->getRepository(array(
                'jackalope.doctrine_dbal_connection' => $dbConnection
            ));

            break;
        case 'jackalope-jackrabbit':
            throw new \Exception('Jackrabbit bootstrap has not yet been defined');
            break;
        default:
            throw new \RuntimeException(sprintf('Invalid transport "%s" given', $config['jackalope']['transport']));
    }

    // Special case for DoctrineDBAL, when running the init command, it needs the db connection, but a phpcr session is
    // impossible if the db is not yet initialized. So we create a special helperSet just for that command

    if (isset($argv[1]) && 'jackalope:init:dbal' === $argv[1]) {
        $helperSet = new HelperSet(array(
            'connection' => new \Jackalope\Tools\Console\Helper\DoctrineDbalHelper($dbConnection),
        ));
    } else {

        if (!isset($repository) || !$repository instanceof \PHPCR\RepositoryInterface) {
            throw new \RuntimeException('$repository should be an instance of \PHPCR\RepositoryInterface');
        }

        // We don't need to login if we only request the list or the help
        if (!isset($argv[1]) || !in_array($argv[1], array('list', 'help'))) {
            $credentials = new \PHPCR\SimpleCredentials($config['phpcr']['username'], $config['phpcr']['password']);
            $session = $repository->login($credentials, $config['phpcr']['workspace']);

            $helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
                'session' => new \PHPCR\Util\Console\Helper\PhpcrHelper($session)
            ));
        }
    }
} catch (\Exception $e) {
    echo "\033[31m" . 'Error while bootstrapping Jackalope:' . "\033[0m" . PHP_EOL;
    echo $e->getMessage();
    exit(1);
}