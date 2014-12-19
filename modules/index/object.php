<?php

$module = $Params['ObjectID'];
$tpl = eZTemplate::factory();

$error = false;
$info = false;
$detail = false;
$xml = false;
$solr = false;

$objectID = $Params['ObjectID'];

if ( NULL == $Params['ObjectID'] )
{
    $error = "Object ID or object remote_id not found";
}
else
{
    $object = eZContentObject::fetch( intval( $objectID ) );
    if ( !$object instanceof eZContentObject )
    {
        $object = eZContentObject::fetchByRemoteID( $objectID );
    }
    
    if ( $object instanceof eZContentObject )
    {
        if ( $object->attribute( 'can_read' ) )
        {
            $searchEngine = new eZSolr();
            $result = $searchEngine->addObject( $object, true );
            $info = array(
                'object' => $object,
                'result' => $result
            );
            $detail = array();
            $attributes = $object->dataMap();
            foreach( $attributes as $attribute )
            {
                $isSearchable = $attribute->attribute( 'contentclass_attribute' )->attribute( 'is_searchable' );
                $documentFieldBase = ezfSolrDocumentFieldBase::getInstance( $attribute );
                $detail[] = array(
                  'attribute' => $attribute,
                  'name' =>  $attribute->attribute( 'contentclass_attribute_name' ),
                  'identifier' =>  $attribute->attribute( 'contentclass_attribute_identifier' ),
                  'data_type_string' => $attribute->attribute( 'data_type_string' ),
                  'is_searchable' => $isSearchable,
                  'ez_metadata' => $isSearchable ? trim( print_r( $attribute->metadata(), 1 ) ) : false,
                  'solr_metadata' => $isSearchable ? trim( print_r( $documentFieldBase->getData(), 1 ) ) : false,
                  'solr_metadata_class' => $isSearchable ? get_class( $documentFieldBase ) : false,
                );
            }
            
            $xml = array();
            $xmlData = fakeAddObject( $object );
            foreach ( $xmlData as $doc )
            {
                if ( is_object( $doc ) )
                {
                    if ( is_object( $doc->Doc ) )
                    {
                        $doc->Doc->formatOutput = true;
                        $xml[] = $doc->Doc->saveXML( $doc->RootElement );
                    }
                    else
                    {
                        $dom = new DOMDocument;
                        $dom->preserveWhiteSpace = FALSE;
                        $dom->loadXML( $doc->docToXML() );
                        $dom->formatOutput = TRUE;
                        $xml[] = $dom->saveXML( $dom->documentElement );
                    }
                }
            }
            
            $solrBase = new eZSolrBase();
            $version = json_decode( eZHTTPTool::getDataByURL( $solrBase->SearchServerURI . '/admin/system/?wt=json' ), true );
            $solr = array(
              'ping' => trim( print_r( $solrBase->ping(), 1 ) ),
              'version' => trim( print_r( $version, 1 ) )
            );
            
        }
        else
        {
            $error = "Current user can not read object {$objectID}";
        }
    }
    else
    {
        $error = "Object {$objectID} not found";
    }
    
}

$tpl->setVariable( 'error', $error );
$tpl->setVariable( 'info', $info );
$tpl->setVariable( 'detail', $detail );
$tpl->setVariable( 'xml', $xml );
$tpl->setVariable( 'solr', $solr );

echo $tpl->fetch( 'design:index/object.tpl' );
eZDisplayDebug();
eZExecution::cleanExit();

/*
if ( NULL == $Params['ObjectID'] )
{
    echo 'Specificare un object ID';
}
else
{
    $ObjectID = $Params['ObjectID'];
    $searchEngine = new eZSolr();
    $object = eZContentObject::fetch( intval( $ObjectID ) );
    
    if ( $object )
    {
        if ( !$object->attribute( 'can_read' ) )
        {
            return $Module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
            eZExecution::cleanExit();
        }
        
        echo "<h2>Indexing object ID: <em>" . $object->attribute( 'id' ) . "</em><br />Name: <em>" . $object->attribute( 'name' ) . "</em><br /> Main node ID:  <em>" . $object->attribute( 'main_node_id' ) . "</em><br />Class: <em>" . $object->attribute( 'class_identifier' ) . "</em></h2>";
        $attribues = $object->dataMap();
        echo "<a href='#' onclick='javascript:document.getElementById(\"details\").style.display=\"block\"; return false;'>Vedi dettagli</a>";
        echo '<table id="details" cellpadding="10" width="100%" style="display:none">';
        echo '<tr>';
        echo '<th>Identifier</th>';
        echo '<th>Metadata</th>';
        echo '<th>ezfSolrDocumentFieldBase::getData</th>';
        echo '</tr>';
        $index = 0;
        foreach( $attribues as $i => $a )
        {
            $index++;
            $color = ( $index & 1 ) ? '#fff' : '#eee';
            
            if ( $a->attribute( 'contentclass_attribute' )->attribute( 'is_searchable' ) > 0 )
            {
                echo '<tr style="background:'.$color.'">';
                echo '<td>';
                echo $i . '<br /><small>(' . $a->attribute( 'data_type_string' ) . ')</small>';
                echo '</td><td><small>';
                print_r( $a->metadata() );
                echo '</small></td><td><small>';
                
                print_r( $documentFieldBase->getData() ) ;
                echo '</small></td>';
                echo '</tr>';
            }
            else
            {
                echo '<tr style="background:'.$color.'">';
                echo '<td>';
                echo $i . '<br /><small>(' . $a->attribute( 'data_type_string' ) . ')</small>';
                echo '</td><td><small>';
                print_r( $a->metadata() );
                echo '</small></td><td>';
                echo '<small><em>(not searchable)</em></small>';
                echo '</td>';
                echo '</tr>';
            }
        }
        echo '</table>';
        
        $result = $searchEngine->addObject( $object, true );
        echo '<h2>Index result: ' . print_r( $result, 1 ) . '</h2>';
        
        echo '<h3>Xml sent to engine:</h3>';
        $xml = fakeAddObject( $object );
        foreach ( $xml as $doc )
        {
            if ( is_object( $doc ) )
            {
                if ( is_object( $doc->Doc ) )
                {
                    $doc->Doc->formatOutput = true;
                    echo '<pre style="border: 1px solid rgb(204, 204, 204); background: none repeat scroll 0% 0% rgb(238, 238, 238); border-radius: 5px 5px 5px 5px; padding: 10px;">' . htmlentities( $doc->Doc->saveXML( $doc->RootElement ) ) . '</pre>';
                }
                else
                {
                    $dom = new DOMDocument;
                    $dom->preserveWhiteSpace = FALSE;
                    $dom->loadXML( $doc->docToXML() );
                    $dom->formatOutput = TRUE;
                    echo '<pre style="border: 1px solid rgb(204, 204, 204); background: none repeat scroll 0% 0% rgb(238, 238, 238); border-radius: 5px 5px 5px 5px; padding: 10px;">' . htmlentities( $dom->saveXML( $dom->documentElement ) ) . '</pre>';
                }
            }
        }
        
        
    }
    else
    {
        echo 'Non esiste oggetto con ID #' . $ObjectID;
    }
}
*/


function fakeAddObject( $contentObject )
{
    $eZSolr = new eZSolr();
    
    // Add all translations to the document list
    $docList = array();

    // Check if we need to index this object after all
    // Exclude if class identifier is in the exclude list for classes
    $excludeClasses = $eZSolr->FindINI->variable( 'IndexExclude', 'ClassIdentifierList' );
    if ( $excludeClasses && in_array( $contentObject->attribute( 'class_identifier' ), $excludeClasses ) )
    {
        return true;
    }
    // Get global object values
    $mainNode = $contentObject->attribute( 'main_node' );
    if ( !$mainNode )
    {
        eZDebug::writeError( 'Unable to fetch main node for object: ' . $contentObject->attribute( 'id' ), __METHOD__ );
        return false;
    }

    $mainNodePathArray = $mainNode->attribute( 'path_array' );
    // initialize array of parent node path ids, needed for multivalued path field and subtree filters
    $nodePathArray = array();

    //included in $nodePathArray
    //$pathArray = $mainNode->attribute( 'path_array' );
    $currentVersion = $contentObject->currentVersion();

    // Get object meta attributes.
    $metaAttributeValues = eZSolr::getMetaAttributesForObject( $contentObject );

    // Get node attributes.
    $nodeAttributeValues = array();
    foreach ( $contentObject->attribute( 'assigned_nodes' ) as $contentNode )
    {
        foreach ( eZSolr::nodeAttributes() as $attributeName => $fieldType )
        {
            $nodeAttributeValues[] = array( 'name' => $attributeName,
                                            'value' => $contentNode->attribute( $attributeName ),
                                            'fieldType' => $fieldType );
        }
        $nodePathArray[] = $contentNode->attribute( 'path_array' );

    }

    // Check anonymous user access.
    if ( $eZSolr->FindINI->variable( 'SiteSettings', 'IndexPubliclyAvailable' ) == 'enabled' )
    {
        $anonymousUserID = $eZSolr->SiteINI->variable( 'UserSettings', 'AnonymousUserID' );
        $currentUserID = eZUser::currentUserID();
        $user = eZUser::instance( $anonymousUserID );
        eZUser::setCurrentlyLoggedInUser( $user, $anonymousUserID );
        $anonymousAccess = $contentObject->attribute( 'can_read' );
        $user = eZUser::instance( $currentUserID );
        eZUser::setCurrentlyLoggedInUser( $user, $currentUserID );
        $anonymousAccess = $anonymousAccess ? 'true' : 'false';
    }
    else
    {
        $anonymousAccess = 'false';
    }

    // Load index time boost factors if any
    //$boostMetaFields = $eZSolr->FindINI->variable( "IndexBoost", "MetaField" );
    $boostClasses = $eZSolr->FindINI->variable( 'IndexBoost', 'Class' );
    $boostAttributes = $eZSolr->FindINI->variable( 'IndexBoost', 'Attribute' );
    $boostDatatypes = $eZSolr->FindINI->variable( 'IndexBoost', 'Datatype' );
    $reverseRelatedScale = $eZSolr->FindINI->variable( 'IndexBoost', 'ReverseRelatedScale' );

    // Initialise default doc boost
    $docBoost = 1.0;
    $contentClassIdentifier = $contentObject->attribute( 'class_identifier' );
    // Just test if the boost factor is defined by checking if it has a numeric value
    if ( isset( $boostClasses[$contentClassIdentifier] ) && is_numeric( $boostClasses[$contentClassIdentifier] ) )
    {
        $docBoost += $boostClasses[$contentClassIdentifier];
    }
    // Google like boosting, using eZ Publish reverseRelatedObjectCount
    $reverseRelatedObjectCount = $contentObject->reverseRelatedObjectCount();
    $docBoost += $reverseRelatedScale * $reverseRelatedObjectCount;

    //  Create the list of available languages for this version :
    $availableLanguages = $currentVersion->translationList( false, false );

    // Loop over each language version and create an eZSolrDoc for it
    foreach ( $availableLanguages as $languageCode )
    {
        $doc = new eZSolrDoc( $docBoost );
        // Set global unique object ID
        $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'guid' ), $eZSolr->guid( $contentObject, $languageCode ) );

        // Set installation identifier
        $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'installation_id' ), eZSolr::installationID() );
        $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'installation_url' ),
                        $eZSolr->FindINI->variable( 'SiteSettings', 'URLProtocol' ) . $eZSolr->SiteINI->variable( 'SiteSettings', 'SiteURL' ) . '/' );

        // Set Object attributes
        $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'name' ), $contentObject->name( false, $languageCode ) );
        // Also add value to the "sort_name" field as "name" is unsortable, due to Solr limitation (tokenized field)
        $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'sort_name' ), $contentObject->name( false, $languageCode ) );
        $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'anon_access' ), $anonymousAccess );
        $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'language_code' ), $languageCode );
        $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'available_language_codes' ), $availableLanguages );

        if ( $owner = $contentObject->attribute( 'owner' ) )
        {
            // Set owner name
            $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'owner_name' ),
                            $owner->name( false, $languageCode ) );

            // Set owner group ID
            foreach ( $owner->attribute( 'parent_nodes' ) as $groupID )
            {
                $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'owner_group_id' ), $groupID );
            }
        }

        // from eZ Publish 4.1 only: object states
        // so let's check if the content object has it
        if ( method_exists( $contentObject, 'stateIDArray' ) )
        {
            $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'object_states' ),
                            $contentObject->stateIDArray() );
        }

        // Set content object meta attribute values.
        foreach ( $metaAttributeValues as $metaInfo )
        {
            $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( $metaInfo['name'] ),
                            ezfSolrDocumentFieldBase::preProcessValue( $metaInfo['value'], $metaInfo['fieldType'] ) );
        }

        // Set content node meta attribute values.
        foreach ( $nodeAttributeValues as $metaInfo )
        {
            $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( $metaInfo['name'] ),
                            ezfSolrDocumentFieldBase::preProcessValue( $metaInfo['value'], $metaInfo['fieldType'] ) );
        }

        // Add main url_alias
        $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_url_alias' ), $mainNode->attribute( 'url_alias' ) );

        // Add main path_string
        $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'main_path_string' ), $mainNode->attribute( 'path_string' ) );

        // add nodeid of all parent nodes path elements
        foreach ( $nodePathArray as $pathArray )
        {
            foreach ( $pathArray as $pathNodeID)
            {
                $doc->addField( ezfSolrDocumentFieldBase::generateMetaFieldName( 'path' ), $pathNodeID );
            }
        }

        // Since eZ Fnd 2.3
        // cannot call metafield field bame constructor as we are creating multiple fields
        foreach ( $mainNodePathArray as $key => $pathNodeID )
        {
            $doc->addField( 'meta_main_path_element_' . $key . '_si', $pathNodeID );

        }

        eZContentObject::recursionProtectionStart();

        // Loop through all eZContentObjectAttributes and add them to the Solr document.
        // @since eZ Find 2.3: look for the attribute storage setting

        $doAttributeStorage = ( ( $eZSolr->FindINI->variable( 'IndexOptions', 'EnableSolrAttributeStorage' ) ) === 'true' ) ? true : false;

        if ( $doAttributeStorage )
        {
            $allAttributeData = array();
        }

        foreach ( $currentVersion->contentObjectAttributes( $languageCode ) as $attribute )
        {
            $metaDataText = '';
            $classAttribute = $attribute->contentClassAttribute();
            $attributeIdentifier = $classAttribute->attribute( 'identifier' );
            $combinedIdentifier = $contentClassIdentifier . '/' . $attributeIdentifier;
            $boostAttribute = false;
            if ( isset( $boostAttributes[$attributeIdentifier]) && is_numeric( $boostAttributes[$attributeIdentifier]))
            {
                $boostAttribute = $boostAttributes[$attributeIdentifier];
            }
            if ( isset( $boostAttributes[$combinedIdentifier]) && is_numeric( $boostAttributes[$combinedIdentifier]))
            {
                $boostAttribute += $boostAttributes[$combinedIdentifier];
            }
            if ( $classAttribute->attribute( 'is_searchable' ) == 1 )
            {
                $documentFieldBase = ezfSolrDocumentFieldBase::getInstance( $attribute );
                $eZSolr->addFieldBaseToDoc( $documentFieldBase, $doc, $boostAttribute );
            }

            if ( $doAttributeStorage )
            {
                $storageFieldName = ezfSolrStorage::getSolrStorageFieldName( $attributeIdentifier );
                $attributeData = ezfSolrStorage::getAttributeData( $attribute );
                $allAttributeData['data_map'][$attributeIdentifier] = $attributeData;
                $doc->addField( $storageFieldName, ezfSolrStorage::serializeData( $attributeData ) );
            }
        }
        eZContentObject::recursionProtectionEnd();

        if ( $doAttributeStorage )
        {
            $doc->addField( 'as_all_bst', ezfSolrStorage::serializeData( $allAttributeData ) );
        }

        $docList[$languageCode] = $doc;
        
        $generalPlugins = (array) eZINI::instance( 'ezfind.ini' )->variable( 'IndexPlugins', 'General' );
        $classPlugins   = (array) eZINI::instance( 'ezfind.ini' )->variable( 'IndexPlugins', 'Class' );
        if ( !empty( $generalPlugins ) )
        {
            foreach ( $generalPlugins as $pluginClassString )
            {
                if( !class_exists( $pluginClassString ) )
                {
                    eZDebug::writeError( "Unable to find the PHP class '$pluginClassString' defined for index time plugins for eZ Find", __METHOD__ );
                    continue;
                }
                $plugin = new $pluginClassString;
                if ( $plugin instanceof ezfIndexPlugin )
                {
                    $plugin->modify( $contentObject, $docList );
                }
            }
        }        
        if (array_key_exists($contentObject->attribute( 'class_identifier' ), $classPlugins ) )
        {
            $pluginClassString = $classPlugins[$contentObject->attribute( 'class_identifier' )];
            if ( class_exists( $pluginClassString ) )
            {
                $plugin = new $pluginClassString;
                if ( $plugin instanceof ezfIndexPlugin )
                {
                        $plugin->modify( $contentObject, $docList );
                }
            }
        }

        
    }

    return $docList;


}

?>