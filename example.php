<?php
require_once 'youtube.class.php';
$youtube = new YOUTUBE();
$youtube->_getInfoVideo('THTmDBtAlf4');
print_r($youtube->_genDownload());
