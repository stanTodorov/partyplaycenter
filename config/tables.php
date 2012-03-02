<?php
/**
 * Списък на таблиците, които се използват в БД
 */
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

$prefix = $DB_CONN['PREFIX'];

/******************************************************************************/
define('TABLE_ALBUMS',                 $prefix.'albums'                       );
define('TABLE_ALBUMS_PICS',            $prefix.'albums_pictures'              );
define('TABLE_CLUBS',                  $prefix.'clubs'                        );
define('TABLE_EVENTS',                 $prefix.'events'                       );
define('TABLE_MENU',                   $prefix.'menu'                         );
define('TABLE_MENU_CATS',              $prefix.'menu_categories'              );
define('TABLE_PARTIES',                $prefix.'parties'                      );
define('TABLE_SETTINGS',               $prefix.'settings'                     );
define('TABLE_USERS',                  $prefix.'users'                        );
/******************************************************************************/

unset($prefix);
