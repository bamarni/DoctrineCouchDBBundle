<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\CouchDBBundle\Tests\DependencyInjection;

use Symfony\Bundle\CouchDBBundle\Tests\TestCase;
use Symfony\Bundle\CouchDBBundle\DependencyInjection\CouchDBExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\ResolveDefinitionTemplatesPass;
use Symfony\Component\Config\FileLocator;

abstract class AbstractDoctrineExtensionTest extends TestCase
{
    abstract protected function loadFromFile(ContainerBuilder $container, $file);

    public function testClientOverrideDefaultConnection()
    {
        $container = $this->getContainer();
        $loader = new CouchDBExtension();

        $loader->load(array(array(), array('client' => array('default_connection' => 'foo')), array()), $container);

        $this->assertEquals('foo', $container->getParameter('doctrine_couchdb.default_connection'), '->load() overrides existing configuration options');
        $this->assertTrue($container->has('doctrine_couchdb.client.foo_connection'));
    }

    public function testClients()
    {
        $container = $this->getContainer();
        $loader = new CouchDBExtension();

        $loader->load(array(
            array(
                'client' => array('default_connection' => 'test', 'connections' => array(
                    'test' => array('port' => 4000),
                    'test2' => array('port' => 1984),
                )),
            )
        ), $container);

        $this->assertTrue($container->has('doctrine_couchdb.client.test_connection'));
        $this->assertTrue($container->has('doctrine_couchdb.client.test2_connection'));
    }

    public function testDocumentManagers()
    {
        $container = $this->getContainer();
        $loader = new CouchDBExtension();

        $loader->load(array(
            array(
                'client' => array(),
                'odm' => array(
                    'default_document_manager' => 'test',
                    'document_managers' => array(
                        'test' => array('connection' => 'default'),
                        'test2' => array('metadata_cache_driver' => array('type' => 'apc'))
                    )
                )
            )
        ), $container);

        $this->assertTrue($container->has('doctrine_couchdb.odm.test_document_manager'));
        $this->assertTrue($container->has('doctrine_couchdb.odm.test2_document_manager'));
    }

    protected function getContainer($bundles = 'YamlBundle', $vendor = null)
    {
        $bundles = (array) $bundles;

        $map = array();
        foreach ($bundles as $bundle) {
            require_once __DIR__.'/Fixtures/Bundles/'.($vendor ? $vendor.'/' : '').$bundle.'/'.$bundle.'.php';

            $map[$bundle] = 'Fixtures\\Bundles\\'.($vendor ? $vendor.'\\' : '').$bundle.'\\'.$bundle;
        }

        return new ContainerBuilder(new ParameterBag(array(
            'kernel.debug'       => false,
            'kernel.bundles'     => $map,
            'kernel.cache_dir'   => sys_get_temp_dir(),
            'kernel.root_dir'    => __DIR__ . "/../../../../../" // src dir
        )));
    }
}