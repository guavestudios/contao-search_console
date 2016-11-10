<?php

if (class_exists('NamespaceClassLoader')) {
    NamespaceClassLoader::add('Guave', 'system/modules/search_console/library');
}

/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
    'search_console_result'   => 'system/modules/search_console/templates/modules',
));
