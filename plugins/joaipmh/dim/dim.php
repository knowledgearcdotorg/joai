<?php
/**
 * @copyright   Copyright (C) 2015 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

/**
 * Harvests metadata in the DSpace Intermediate Metadata format.
 *
 * @package  JOai.Plugin
 */
class PlgJOaiPmhDim extends JPlugin
{
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->params->set('metadataPrefix', 'dim');

        $registry = new \Joomla\Registry\Registry;
        $registry->separator = "-"; // we want to keep the "." notation for dc.
        $registry->loadFile(__DIR__."/crosswalk.json", "JSON");

        $this->registry = $registry;
    }

    /**
     * Harvests a single dim metadata item, saving it to the cache.
     *
     * @param   string            $context   The current metadata item context.
     * @param   SimpleXmlElement  $data      The metadata to consume.
     *
     * @return  array             An associative array of metadata.
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
                    if (JString::trim((string)$tag)) {
                        $attributes = (array)$tag->attributes();

                        if ($attributes["@attributes"]) {
                            $attributes = $attributes["@attributes"];

                            $parts = array();

                            if (isset($attributes["mdschema"])) {
                                $parts[] = $attributes["mdschema"];
                            }

                            if (isset($attributes["element"])) {
                                $parts[] = $attributes["element"];
                            }

                            if (isset($attributes["qualifier"])) {
                                $parts[] = $attributes["qualifier"];
                            }

                            $key = implode('.', $parts);

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
        }

        return $metadata;
    }
}
