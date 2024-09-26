<?php
/**
* Sets a higher memory limit for admin/structure/menu which frequently
* needs an unusually high amount of memory to load, due to complexity.
* For additional examples of changing memory limits for pages on
* your websites, see
* https://support-acquia.force.com/s/article/360004542293-Conditionally-increasing-memory-limits
*/

if (strpos($_GET['q'], 'admin/structure/menu/') === 0) {
   ini_set('memory_limit', '700M');
}

