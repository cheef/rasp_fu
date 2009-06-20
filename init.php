<?php
	define('RASP_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
  require_once 'autoloader/loader.php';

  // For supporting old-style
  define('RASP_RESOURCES_PATH', RASP_PATH . 'resources' . DIRECTORY_SEPARATOR);
  define('RASP_SERVICES_PATH', RASP_PATH . 'services' . DIRECTORY_SEPARATOR);
  define('RASP_TYPES_PATH', RASP_PATH . 'types' . DIRECTORY_SEPARATOR);
  define('RASP_TOOLS_PATH', RASP_PATH . 'tools' . DIRECTORY_SEPARATOR);
  define('RASP_VENDOR_PATH', RASP_PATH . 'vendor' . DIRECTORY_SEPARATOR);
  define('RASP_ORM_PATH', RASP_PATH . 'orm' . DIRECTORY_SEPARATOR);
  define('RASP_CONTROLLERS_PATH', RASP_PATH . 'controller' . DIRECTORY_SEPARATOR);
?>