<?php
return [
  // Namespace \RazyFramework class autoload folder, assign empty string to use default path '/library'
  'library_path' => '',

  // Module folder, assign empty string to use default path '/module'
  'module_path' => '',

  // A callback function to identify the SSL protocal. Like cloudflare, it will using proxy
  // to get the conten via port 80 and transfer to visitor via port 443 in `Flexible SSL` mode,
  // but the $_SERVER['PORT'] still declared as 80 and $_SERVER['HTTPS'] is off.
  // In this case, you can check 'HTTP_X_FORWARDED_PROTO' or 'HTTP_CF_VISITOR' for cloudflare `Flexible SSL` mode.
  // Return true to set the system in 'HTTPS' mode
  'identify_ssl' => null,

  // Force Razy using https protocal, it will redirect http to https
  'force_ssl' => false
];
?>
