<?php
/**
 * @copyright   Copyright (C) 2015 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * Harvests metadata in the OAI Dublin Core format.
 *
 * @package  JHarvest.Plugin
 */
class PlgJOaiPmhOai_dc extends JPlugin
{
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->params->set('metadataPrefix', 'oai_dc');

        $registry = new \Joomla\Registry\Registry;
        $registry->loadFile(__DIR__."/crosswalk.json", "JSON");

        $this->registry = $registry;
    }

    /**
     * Harvests a single oai_dc metadata item.
     *
     * @param   string                     $context   The current metadata item context.
     * @param   SimpleXmlElement           $data      The metadata to consume.
     *
     * @return  \Joomla\Registry\Registry  A registry of metadata.
     */
    public function onJOaiPmhHarvestMetadata($context, $data)
    {
        if ($context != "joaipmh.".$this->params->get('metadataPrefix')) {
            return;
        }

        $metadata = array();
        $namespaces = $data->getDocNamespaces(true);

        foreach ($namespaces as $prefix=>$namespace) {
            if ($prefix) {
                $data->registerXPathNamespace($prefix, $namespace);
                $tags = $data->xpath('//'.$prefix.':*');

                foreach ($tags as $tag) {
                    $key = $prefix.':'.(string)$tag->getName();

                    if (JString::trim((string)$tag)) {
                        if ($schemalessKey = $this->registry->get($key)) {
                            $key = $schemalessKey;
                        }

                        $values = JArrayHelper::getValue($metadata, $key);

                        if (!is_array($values)) {
                            $values = array();
                        }

                        $values[] = (string)$tag;

                        $metadata[$key] = $values;
                    }
                }
            }
        }

        return $metadata;
    }
}
