<?php

return array (
  'autoload' => false,
  'hooks' => 
  array (
    'app_init' => 
    array (
      0 => 'cms',
    ),
    'view_filter' => 
    array (
      0 => 'cms',
    ),
    'user_sidenav_after' => 
    array (
      0 => 'cms',
    ),
    'xunsearch_config_init' => 
    array (
      0 => 'cms',
    ),
    'xunsearch_index_reset' => 
    array (
      0 => 'cms',
    ),
  ),
  'route' => 
  array (
    '/$' => 'cms/index/index',
    '/cms/a/[:diyname]' => 'cms/archives/index',
    '/cms/t/[:name]' => 'cms/tags/index',
    '/cms/p/[:diyname]' => 'cms/page/index',
    '/cms/s' => 'cms/search/index',
    '/cms/c/[:diyname]' => 'cms/channel/index',
    '/cms/d/[:diyname]' => 'cms/diyform/index',
    '/cms/special/[:diyname]' => 'cms/special/index',
    '/u/[:id]' => 'cms/user/index',
    '/qrcode$' => 'qrcode/index/index',
    '/qrcode/build$' => 'qrcode/index/build',
  ),
);