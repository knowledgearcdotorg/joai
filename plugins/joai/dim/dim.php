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
class PlgJOaiDim extends JPlugin
{
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->params->set('metadataPrefix', 'dim');
    }

    public function onJOaiQueryMetadataFormat()
    {
        return $this->params->get('metadataPrefix');
    }

    /**
     * Harvests a single dim metadata item, saving it to the cache.
     *
     * @param   string            $context   The current metadata item context.
     * @param   SimpleXmlElement  $data      The metadata to consume.
     *
     * @return  array             An associative array of metadata.
     */
    public function onJOaiHarvestMetadata($context, $data)
    {
        if ($context != "joai.".$this->params->get('metadataPrefix')) {
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
