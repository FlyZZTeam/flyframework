<?php
/**
 * URI ROUTING
 *
 * This file lets you re-map URI requests to specific controller functions.
 *
 * Typically there is a one-to-one relationship between a URL string
 * and its corresponding controller class/method. The segments in a URL normally follow this pattern:
 *
 *      example.com/class/method/id/
 *
 * In some instances, however, you may want to remap this relationship so that a different class/function is called than the one
 * corresponding to the URL.
 *
 * RESERVED ROUTES
 *
 * There area two reserved routes:
 *
 *      $route['defaultController'] = 'welcome';
 *
 * This route indicates which controller class should be loaded if the
 * URI contains no data. In the above example, the "welcome" class would be loaded.
 *
 * $route['error404'] = 'errors/page_missing';
 *
 * This route will tell the Router what URI segments to use if those provided in the URL cannot be matched to a valid route.
 */

$route['defaultController'] = "index";
$route['error404'] = '';
return $route;