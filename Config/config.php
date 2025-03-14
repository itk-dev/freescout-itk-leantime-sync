<?php
$projectKeys = [];

foreach($_ENV as $key => $value){
  if(preg_match('/^LEANTIME_PROJECT_KEY_MAP/', $key)){
    $projectKeys[$key] = $value;
  }
}

return [
    'name' => 'ItkLeantimeSync',
    'leantimeUrl' => env('LEANTIME_URL', null),
    'leantimeApiKey' => env('LEANTIME_API_KEY', null),
    'leantimeProjectKeys' => $projectKeys,
];