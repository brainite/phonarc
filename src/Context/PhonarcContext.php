<?php
namespace Witti\Phonarc\Context;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\StaticPHPDriver;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Symfony\Component\Yaml\Yaml;


class PhonarcContext {
  const CURRENT_CONTEXT = -1;
  const NEXT_CONTEXT = -2;

  static public function factory($context_id = NULL) {
    static $contexts = array();
    static $prev = NULL;

    // Add another context to the factory.
    if (is_object($context_id)) {
      $contexts[$context_id->getId()] = $context_id;
      return $contexts[$context_id->getId()];
    }

    if (isset($context_id)) {
      if ($context_id == PhonarcContext::CURRENT_CONTEXT) {
        if (!isset($prev)) {
          $ids = array_keys($contexts);
          $prev = array_shift($ids);
        }
      }
      elseif ($context_id == PhonarcContext::NEXT_CONTEXT) {
        $ids = array_keys($contexts);
        if (!isset($prev)) {
          $prev = array_shift($ids);
        }
        else {
          $idix = array_search($prev, $ids);
          if ($idix == sizeof($ids) - 1) {
            $prev = NULL;
          }
          else {
            $prev = $ids[$idix + 1];
          }
        }
      }
      else {
        $prev = $context_id;
      }
    }

    // Build any new requested context.
    if (!isset($prev) || !isset($contexts[$prev])) {
      return NULL;
    }
    return $contexts[$prev];
  }

  static public function loadConf($path) {
    // Locate the configuration.
    if (!is_file($path)) {
      throw new \InvalidArgumentException("Configuration file does not exist.");
    }

    // Parse the configuration.
    $confs = Yaml::parse($path);
    if (!is_array($confs)) {
      throw new \InvalidArgumentException("Empty configuration file.");
    }

    // Extract the defaults.
    $defaults = array();
    if (isset($confs['defaults'])) {
      $defaults = (array) $confs['defaults'];
      unset($confs['defaults']);
    }

    // Normalize the configurations.
    foreach ($confs as $conf_id => $conf) {
      // Apply default settings to the configuration.
      $conf = array_replace_recursive(array(
        'title' => 'Untitled',
        'link' => 'http://example.com/test/',
        'description' => 'This is the archive for untitled.',
        'baseurl' => '/test/',
        'basepath' => '/tmp/',
        'max_downloads' => 1,
        'doctrine' => array(
          'prefix' => '',
        ),
        'getmail' => array(
          'options' => array(
            'verbose' => 0,
            'delete' => 0,
          ),
          'retriever' => array(
            'type' => 'BrokenUIDLPOP3Retriever',
            'server' => 'localhost',
            'username' => 'unknown',
            'password' => 'password',
          ),
        ),
        'mhonarc' => array(
          'idxsize' => 2000,
          'idxfname' => 'archive.rss',
        ),
        'message' => array(
          'version' => 'none',
        ),
      ), $defaults, $conf, array(
        'getmail' => array(
          'options' => array(
            'max_messages_per_session' => 1,
          ),
          'destination' => array(
            'type' => 'Mboxrd',
            'user' => 'apache',
            "path" => NULL,
          ),
        ),
      ));
      PhonarcContext::factory(new PhonarcContext($conf_id, $conf));
    }
  }

  protected $entityManager;
  protected $helperSet;
  protected $conf;
  protected $conf_id;

  public function __construct($conf_id, $conf) {
    $this->conf_id = $conf_id;
    $this->conf = $conf;
  }

  public function getConf($key = NULL) {
    if (!isset($key)) {
      return $this->conf;
    }
    // Locate a specific key.
    $cursor = $this->conf;
    foreach (explode('.', $key) as $k) {
      if (!isset($cursor[$k])) {
        return NULL;
      }
      $cursor = $cursor[$k];
    }
    return $cursor;
  }

  public function getEntityManager() {
    if (!isset($this->entityManager)) {
      $isDevMode = FALSE;

      // Load the dbparams from the conf array.
      $dbParams = $this->conf['doctrine'];

      $paths = array(
        dirname(__DIR__) . '/Message',
      );
      $config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);
      $this->entityManager = EntityManager::create($dbParams, $config);
      $driver = new StaticPHPDriver($paths);
      $this->entityManager->getConfiguration()->setMetadataDriverImpl($driver);

      $conf = &$this->entityManager->getConfiguration();

      // Reset most caches since we may have switched contexts.
      // @link http://docs.doctrine-project.org/en/2.0.x/reference/caching.html
      // $c = new \Doctrine\Common\Cache\ArrayCache();
      // $c->flushAll();
      $conf->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
      $conf->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
      $conf->setHydrationCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
      $conf->setResultCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
      $cmf = $this->entityManager->getMetadataFactory();
      $cmf->setCacheDriver(new \Doctrine\Common\Cache\ArrayCache());
    }
    return $this->entityManager;
  }

  public function getHelperSet() {
    if (!isset($this->helperSet)) {
      $this->helperSet = ConsoleRunner::createHelperSet($this->getEntityManager());
    }
    return $this->helperSet;
  }

  public function getId() {
    return $this->conf_id;
  }

}