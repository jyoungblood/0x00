<?php


function rimraf($target) {
  if(is_dir($target)){
    $files = glob( $target . '*', GLOB_MARK );
    foreach( $files as $file ){
      rimraf( $file );      
    }
    rmdir( $target );
  } elseif(is_file($target)) {
    unlink( $target );  
  }
}
