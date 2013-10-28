<?php

/**
  Initialize environment & session so testing behaves
  correctly
*/
if (!class_exists('AutoLoader')) include(dirname(__FILE__).'/../lib/AutoLoader.php');

$CORE_LOCAL->set("parse_chain",'');
$CORE_LOCAL->set("preparse_chain",'');

AutoLoader::LoadMap();
CoreState::initiate_session();

?>
