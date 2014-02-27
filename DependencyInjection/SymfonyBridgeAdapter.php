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

namespace Doctrine\Bundle\DoctrineCacheBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Alias;

/**
 * Symfony bridge adpter
 *
 * @author Kinn Coelho Julião <kinncj@php.net>
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class SymfonyBridgeAdapter
{
    /**
     * @var \Doctrine\Bundle\DoctrineCacheBundle\DependencyInjection\DoctrineCacheExtension
     */
    protected $cacheExtension;

    /**
     * @param \Doctrine\Bundle\DoctrineCacheBundle\DependencyInjection\DoctrineCacheExtension $cacheExtension
     */
    public function __construct(DoctrineCacheExtension $cacheExtension)
    {
        $this->cacheExtension = $cacheExtension;
    }

    protected function loadCacheDriver($cacheName, $objectManagerName, array $cacheDriver, ContainerBuilder $container)
    {
        $cacheServiceId = $this->getObjectManagerElementName($objectManagerName . '_' . $cacheName);
        $type           = $cacheDriver['type'];

        if ($type == 'service') {
            $container->setAlias($cacheServiceId, new Alias($cacheDriver['id'], false));

            return;
        }

        $newConfig = array(
            'type'  => $type,
            'alias' => $cacheServiceId,
        );

        $newConfig[$type] = array();

        if ( ! isset($cacheDriver['namespace'])) {
            // generate a unique namespace for the given application
            $environment = $container->getParameter('kernel.root_dir').$container->getParameter('kernel.environment');
            $hash        = hash('sha256', $environment);
            $namespace   = 'sf2'.$this->getMappingResourceExtension().'_'.$objectManagerName.'_'.$hash;

            $cacheDriver['namespace'] = $namespace;
        }

        if ( ! empty($cacheDriver['port'])) {
             $newConfig[$type]['port'] = $cacheDriver['port'];
        }

        if ( ! empty($cacheDriver['namespace'])) {
             $newConfig[$type]['namespace'] = $cacheDriver['namespace'];
        }

        if ( ! empty($cacheDriver['host'])) {
             $newConfig[$type]['host'] = $cacheDriver['host'];
        }

        $this->cacheExtension->loadCacheProvider($cacheName, $newConfig, $container);
    }

    protected function loadObjectManagerCacheDriver(array $objectManager, ContainerBuilder $container, $cacheName)
    {
        $this->loadCacheDriver($cacheName, $objectManager['name'], $objectManager[$cacheName.'_driver'], $container);
    }

    protected function getObjectManagerElementName($name)
    {
        return 'doctrine.orm.'.$name;
    }

    protected function getMappingResourceExtension()
    {
        return 'orm';
    }
}