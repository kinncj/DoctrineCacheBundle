<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Bundle\DoctrineCacheBundle\Tests\DependencyInjection;

use Doctrine\Bundle\DoctrineCacheBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Doctrine\Bundle\DoctrineCacheBundle\DependencyInjection\SymfonyBridgeAdapter;
use Doctrine\Bundle\DoctrineCacheBundle\DependencyInjection\DoctrineCacheExtension;

/**
 * @group Extension
 *
 * @author  Kinn Coelho Julião <kinncj@php.net>
 */
class SymfonyBridgeAdpterTest extends TestCase
{
    /**
     * @var \Symfony\Bridge\Doctrine\DependencyInjection\AbstractDoctrineExtension
     */
    private $extension;

    protected function setUp()
    {
        parent::setUp();

        $doctrineCacheExtension = $this->createDoctrineCacheExtensionStub('basic');
        $this->extension        = new SymfonyBridgeAdapter($doctrineCacheExtension);
    }

    public function providerBasicDrivers()
    {
        return array(
            array('doctrine.orm.cache.apc.class',       array('type' => 'apc')),
            array('doctrine.orm.cache.array.class',     array('type' => 'array')),
            array('doctrine.orm.cache.xcache.class',    array('type' => 'xcache')),
            array('doctrine.orm.cache.wincache.class',  array('type' => 'wincache')),
            array('doctrine.orm.cache.zenddata.class',  array('type' => 'zenddata')),
            array('doctrine.orm.cache.redis.class',     array('type' => 'redis'),     array('setRedis')),
            array('doctrine.orm.cache.memcache.class',  array('type' => 'memcache'),  array('setMemcache')),
            array('doctrine.orm.cache.memcached.class', array('type' => 'memcached'), array('setMemcached')),
        );
    }

    /**
     * @param string $class
     * @param array  $config
     *
     * @dataProvider providerBasicDrivers
     */
    public function testLoadBasicCacheDriver($class, array $config, array $expectedCalls = array())
    {
        $container      = $this->createContainer();
        $cacheName      = 'metadata_cache';
        $objectManager  = array(
            'name'                  => 'default',
            'metadata_cache_driver' => $config
        );

        $this->invokeLoadCacheDriver($objectManager, $container, $cacheName);

        $this->assertTrue($container->hasDefinition('doctrine.orm.default_metadata_cache'));

        $definition      = $container->getDefinition('doctrine.orm.default_metadata_cache');
        $defCalls        = $definition->getMethodCalls();
        $expectedCalls[] = 'setNamespace';
        $actualCalls     = array_map(function ($call) {
            return $call[0];
        }, $defCalls);

        $this->assertFalse($definition->isPublic());
        $this->assertEquals("%$class%", $definition->getClass());

        foreach (array_unique($expectedCalls) as $call) {
            $this->assertContains($call, $actualCalls);
        }
    }

    public function testServiceCacheDriver()
    {
        $cacheName      = 'metadata_cache';
        $container      = $this->createContainer();
        $definition     = new Definition('%doctrine.orm.cache.apc.class%');
        $objectManager  = array(
            'name'                  => 'default',
            'metadata_cache_driver' => array(
                'type' => 'service',
                'id'   => 'service_driver'
            )
        );

        $container->setDefinition('service_driver', $definition);

        $this->invokeLoadCacheDriver($objectManager, $container, $cacheName);

        $this->assertTrue($container->hasAlias('doctrine.orm.default_metadata_cache'));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage "unrecognized_type" is an unrecognized Doctrine cache driver.
     */
    public function testUnrecognizedCacheDriverException()
    {
        $cacheName      = 'metadata_cache';
        $container      = $this->createContainer();
        $objectManager  = array(
            'name'                  => 'default',
            'metadata_cache_driver' => array(
                'type' => 'unrecognized_type'
            )
        );

        $this->invokeLoadCacheDriver($objectManager, $container, $cacheName);
    }

    protected function invokeLoadCacheDriver(array $objectManager, ContainerBuilder $container, $cacheName)
    {
        $method = new \ReflectionMethod($this->extension, 'loadObjectManagerCacheDriver');

        $method->setAccessible(true);

        $method->invokeArgs($this->extension, array($objectManager, $container, $cacheName));
    }

    /**
     * @param string $file
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @return \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    protected function createDoctrineCacheExtensionStub($file)
    {
        $container = $this->createContainer();
        $loader    = new DoctrineCacheExtension();

        $container->registerExtension($loader);
        $this->loadFromFile($container, $file);
        $container->compile();

        return $loader;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadFromFile(ContainerBuilder $container, $file)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/Fixtures/config/yml'));

        $loader->load($file . '.yml');
    }
}