<?php
define('PROGRAM', 1);
define('SITE', 'client');

chdir(dirname(__FILE__));
require_once('./location.php');
require_once(BASE_PATH.'common.php');

$img = new Securimage();
$img->perturbation         = 0.8;
$img->image_width          = 120;
$img->image_height         = (int)($img->image_width * 0.35);
$img->text_color           = new Securimage_Color("#777777");
$img->num_lines            = 3;
$img->draw_lines_over_text = false;
$img->line_color           = new Securimage_Color("#333333");
$img->signature_color      = new Securimage_Color(rand(0, 64), rand(64, 128), rand(128, 255));
$img->image_type           = Securimage::SI_IMAGE_PNG;
$img->ttf_file             = Securimage::getPath() . '/arialbd.ttf';
$img->code_length          = CFG('captcha.length');
$img->show();