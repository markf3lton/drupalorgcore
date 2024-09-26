<?php
/**
* Sets config_sync_directory as described at
* https://docs.acquia.com/acquia-cloud-platform/develop-apps/config-drupal#section-required-configdefault-folder
*/

$settings['config_sync_directory'] = $app_root . '/../config/' . basename($site_path);

