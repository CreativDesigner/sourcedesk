<?php
namespace jtl\Connector\Plugin;

use \jtl\Connector\Core\IO\Path;
use \Symfony\Component\Finder\Finder;
use \jtl\Connector\Plugin\IPlugin;
use \Symfony\Component\EventDispatcher\EventDispatcher;

class PluginLoader
{
    public function load(EventDispatcher $dispatcher)
    {
        $dir = Path::combine(CONNECTOR_DIR, 'plugins');
        $finder = new Finder();

        $finder->files()->name('bootstrap.php')->in($dir);
        foreach ($finder as $file) {
            include ($file->getPathName());

            $class = sprintf('\\%s\\Bootstrap', str_replace(DIRECTORY_SEPARATOR, '\\', $file->getRelativePath()));
            if (class_exists($class)) {
                $plugin = new $class();
                if ($plugin instanceof IPlugin) {
                    $plugin->registerListener($dispatcher);
                }
            }
        }
    }
}