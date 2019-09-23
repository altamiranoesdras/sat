<?php
/**
 * Provides an easy-to-use class for reading and parsing DTE documents issued by
 * Superintendencia de Administración Tributaria of Guatemala.
 *
 * @package    Sat
 * @subpackage Sat.DteParser
 * @copyright  Copyright (c) 2018-2019 Abdy Franco. All Rights Reserved.
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @author     Abdy Franco <iam@abdyfran.co>
 */

namespace Sat;

class DteParser
{
    private $xml;

    public function __construct($xml)
    {
        // Check if the provided argument it's a file
        if (file_exists($xml)) {
            $xml = @file_get_contents($xml);
        } else {
            // Maybe the provided argument it's an XML string
            $xml = trim($xml);
        }

        // Set parsed SimpleXML object
        $this->xml = simplexml_load_string($xml);
    }

    public function getXmlObject()
    {
        return $this->xml;
    }

    public function getDte()
    {
        return $this->xml->children('dte', true);
    }

    public function getSignature()
    {
        return $this->xml->children('ds', true);
    }

    public function getParsedDocument($json = false)
    {
        $dte = $this->getDte();
        $ds  = $this->getSignature();

        $document = (array) json_decode(json_encode($dte));

        // Fetch DTE properties
        if (isset($dte->SAT->DTE)) {
            $document = (array) json_decode(json_encode(array_merge($document, (array) $dte->SAT->DTE)));

            // Get "DatosGenerales" attributes
            if (isset($dte->SAT->DTE->DatosEmision->DatosGenerales)) {
                $content    = (array) $dte->SAT->DTE->DatosEmision->DatosGenerales;
                $attributes = (array) $dte->SAT->DTE->DatosEmision->DatosGenerales->attributes();
                $attributes = $attributes['@attributes'];
                $node       = (object) array_merge($content, $attributes);

                $document['DatosEmision']->DatosGenerales = $node;
            }

            // Get "Emisor" attributes
            if (isset($dte->SAT->DTE->DatosEmision->Emisor)) {
                $content    = (array) $dte->SAT->DTE->DatosEmision->Emisor;
                $attributes = (array) $dte->SAT->DTE->DatosEmision->Emisor->attributes();
                $attributes = $attributes['@attributes'];
                $node       = (object) array_merge($content, $attributes);

                $document['DatosEmision']->Emisor = $node;
            }

            // Get "Receptor" attributes
            if (isset($dte->SAT->DTE->DatosEmision->Receptor)) {
                $content    = (array) $dte->SAT->DTE->DatosEmision->Receptor;
                $attributes = (array) $dte->SAT->DTE->DatosEmision->Receptor->attributes();
                $attributes = $attributes['@attributes'];
                $node       = (object) array_merge($content, $attributes);

                $document['DatosEmision']->Receptor = $node;
            }

            // Get "Frases" attributes
            if (isset($dte->SAT->DTE->DatosEmision->Frases)) {
                if (is_array($document['DatosEmision']->Frases->Frase)) {
                    $document['DatosEmision']->Frases->Frase = [];

                    foreach ($dte->SAT->DTE->DatosEmision->Frases->Frase as $key => $value) {
                        $content    = (array) $value;
                        $attributes = (array) $value->attributes();
                        $attributes = $attributes['@attributes'];
                        $node       = (object) array_merge($content, $attributes);

                        $document['DatosEmision']->Frases->Frase[] = $node;
                    }
                } elseif (is_object($document['DatosEmision']->Frases->Frase)) {
                    $content    = (array) $dte->SAT->DTE->DatosEmision->Frases->Frase;
                    $attributes = (array) $dte->SAT->DTE->DatosEmision->Frases->Frase->attributes();
                    $attributes = $attributes['@attributes'];
                    $node       = (object) array_merge($content, $attributes);

                    $document['DatosEmision']->Frases->Frase = $node;
                }
            }

            // Get "Items" attributes
            if (isset($dte->SAT->DTE->DatosEmision->Items)) {
                if (is_array($document['DatosEmision']->Items->Item)) {
                    $document['DatosEmision']->Items->Item = [];

                    foreach ($dte->SAT->DTE->DatosEmision->Items->Item as $key => $value) {
                        $content    = (array) $value;
                        $attributes = (array) $value->attributes();
                        $attributes = $attributes['@attributes'];
                        $node       = (object) array_merge($content, $attributes);

                        $document['DatosEmision']->Items->Item[] = $node;
                    }
                } elseif (is_object($document['DatosEmision']->Items->Item)) {
                    $content    = (array) $dte->SAT->DTE->DatosEmision->Items->Item;
                    $attributes = (array) $dte->SAT->DTE->DatosEmision->Items->Item->attributes();
                    $attributes = $attributes['@attributes'];
                    $node       = (object) array_merge($content, $attributes);

                    $document['DatosEmision']->Items->Item = $node;
                }
            }

            // Get "Complementos" attributes
            if (isset($dte->SAT->DTE->DatosEmision->Complementos)) {
                if (is_array($document['DatosEmision']->Complementos->Complemento)) {
                    $document['DatosEmision']->Complementos->Item = [];

                    foreach ($dte->SAT->DTE->DatosEmision->Complementos->Complemento as $key => $value) {
                        $content    = (array) $value->children('cex', true);
                        $attributes = (array) $value->attributes();
                        $attributes = $attributes['@attributes'];
                        $node       = (object) array_merge($content, $attributes);

                        $document['DatosEmision']->Complementos->Complemento[] = $node;
                    }
                } elseif (is_object($document['DatosEmision']->Complementos->Complemento)) {
                    $content    = (array) $dte->SAT->DTE->DatosEmision->Complementos->Complemento->children('cex', true);
                    $attributes = (array) $dte->SAT->DTE->DatosEmision->Complementos->Complemento->attributes();
                    $attributes = $attributes['@attributes'];
                    $node       = (object) array_merge($content, $attributes);

                    $document['DatosEmision']->Complementos->Complemento = $node;
                }
            }

            // Get "NumeroAutorizacion" attributes
            if (isset($dte->SAT->DTE->Certificacion->NumeroAutorizacion)) {
                $content    = ['NumeroAutorizacion' => $document['Certificacion']->NumeroAutorizacion];
                $attributes = (array) $dte->SAT->DTE->Certificacion->NumeroAutorizacion->attributes();
                $attributes = $attributes['@attributes'];
                $node       = (object) array_merge($content, $attributes);

                $document['Certificacion']->NumeroAutorizacion = $node;
            }
        }

        // Fetch DS properties
        if (isset($ds->Signature)) {
            $document['Signature'] = json_decode(json_encode($ds->Signature));

            // Get "CanonicalizationMethod" attributes
            if (isset($ds->Signature->SignedInfo->CanonicalizationMethod)) {
                $content    = (array) $ds->Signature->SignedInfo->CanonicalizationMethod;
                $attributes = (array) $ds->Signature->SignedInfo->CanonicalizationMethod->attributes();
                $attributes = $attributes['@attributes'];
                $node       = (object) array_merge($content, $attributes);

                $document['Signature']->SignedInfo->CanonicalizationMethod = $node;
            }

            // Get "SignatureMethod" attributes
            if (isset($ds->Signature->SignedInfo->SignatureMethod)) {
                $content    = (array) $ds->Signature->SignedInfo->SignatureMethod;
                $attributes = (array) $ds->Signature->SignedInfo->SignatureMethod->attributes();
                $attributes = $attributes['@attributes'];
                $node       = (object) array_merge($content, $attributes);

                $document['Signature']->SignedInfo->SignatureMethod = $node;
            }

            // Get "Reference" attributes
            if (isset($ds->Signature->SignedInfo->Reference)) {
                if (is_array($document['Signature']->SignedInfo->Reference)) {
                    $document['Signature']->SignedInfo->Reference = [];

                    foreach ($ds->Signature->SignedInfo->Reference as $key => $value) {
                        $content    = (array) $value;
                        $attributes = (array) $value->attributes();
                        $attributes = $attributes['@attributes'];
                        $node       = (object) array_merge($content, $attributes);

                        $document['Signature']->SignedInfo->Reference[] = $node;
                    }
                } elseif (is_object($document['Signature']->SignedInfo->Reference)) {
                    $content    = (array) $ds->Signature->SignedInfo->Reference;
                    $attributes = (array) $ds->Signature->SignedInfo->Reference->attributes();
                    $attributes = $attributes['@attributes'];
                    $node       = (object) array_merge($content, $attributes);

                    $document['Signature']->SignedInfo->Reference = $node;
                }
            }

            // Get "SignatureValue" attributes
            if (isset($ds->Signature->SignatureValue)) {
                $content    = ['SignatureValue' => $document['Signature']->SignatureValue];
                $attributes = (array) $ds->Signature->SignatureValue->attributes();
                $attributes = $attributes['@attributes'];
                $node       = (object) array_merge($content, $attributes);

                $document['Signature']->SignatureValue = $node;
            }

            // Get "Object" namespace
            if (isset($ds->Signature->Object)) {
                $node                          = $ds->Signature->Object->children('xades', true);
                $document['Signature']->Object = $node;
            }
        }

        if ($json) {
            return json_encode((object) $document);
        } else {
            return json_decode(json_encode((object) $document));
        }
    }
}
