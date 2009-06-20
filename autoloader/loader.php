<?php

  function rasp_lib(){
    $lib_names = func_get_args();
    foreach($lib_names as $lib_name) {
      $path = explode('.', $lib_name);
      require_once RASP_PATH . join('/', $path) . '.php';
    }
  }
?>