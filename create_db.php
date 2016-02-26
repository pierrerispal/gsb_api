<?php

ini_set('max_execution_time', 0);
require 'vendor/autoload.php';
require_once 'vendor/fzaninotto/faker/src/autoload.php';

$faker = Faker\Factory::create('fr_FR');

use RedBeanPHP\Facade as R;

R::setup('mysql:host=localhost;dbname=gsb_cost_managment', 'root', 'pwsio');

R::exec('CREATE VIEW visitor AS SELECT * FROM gsb_human_ressources.employee WHERE employee.job_id=3 OR employee.job_id = 2');

$status = R::dispense('status');
$status->label = 'Créée';
R::store($status);
$status = R::dispense('status');
$status->label = 'Clôturée';
R::store($status);
$status = R::dispense('status');
$status->label = 'Validée';
R::store($status);
$status = R::dispense('status');
$status->label = 'Mise en paiement';
R::store($status);
$status = R::dispense('status');
$status->label = 'Remboursée';

for ($i = 0; $i < 20; $i++) {
    $package_cost = R::dispense('packagecost');
    $package_cost->label = $faker->realText($maxNbChars = 20, $indexSize = 2);
    $package_cost->cost = rand(5, 210);
    R::store($package_cost);
}

$visitors = R::findAll('visitor');
foreach ($visitors as $visitor) {
    $nbCostSheet = rand(6, 12);
    for ($i = 1; $i <= $nbCostSheet; $i++) {
        $month = $i;
        $day = rand(1, 27);
        $modif_day = $day + rand(1, 3);


        $cost_sheet = R::dispense('costsheet');
        $cost_sheet->date = '2015-' . $month . '-' . $day;
        $cost_sheet->modification_date = '2015-' . $month . '-' . $modif_day;
        $cost_sheet->visitor = $visitor;
        $cost_sheet->status = R::findOne('status', 'id=?', [$faker->numberBetween($min = 1, $max = 4)]);
        $cost_sheet->justification_number = $faker->randomDigitNotNull;
        $cost_sheet->valid_cost = $faker->randomNumber($nbDigits = 3);


        R::store($cost_sheet);

        $nbPackageLine = rand(5, 10);
        $nbOutPackageLine = rand(0, 3);
        for ($v = 0; $v < $nbPackageLine; $v++) {

            $randPackage = rand(1, 20);
            if ($v != 0) {
                while ($randPackage == $randPackageOld) {
                    $randPackage = rand(1, 20);
                }
            }
            $randPackageOld = $randPackage;

            $dayPackageLine = $day + rand(1, 3);

            $package_line = R::dispense('packageline');
            $package_line->package_cost = R::findOne('packagecost', 'id=?', array($randPackage));
            $package_line->cost_sheet = $cost_sheet;
            $package_line->date = '2015-' . $month . '-' . $dayPackageLine;
            $package_line->quantity = rand(1, 9);
            R::store($package_line);
        }

        for ($v = 0; $v < $nbOutPackageLine; $v++) {
            $dayOutPackageLine = $day + rand(1, 3);

            $outpackage_line = R::dispense('outpackageline');
            $outpackage_line->date = '2015-' . $month . '-' . $dayOutPackageLine;
            $outpackage_line->cost_sheet = $cost_sheet;
            $outpackage_line->cost = rand(13, 300);
            $outpackage_line->label = $faker->realText($maxNbChars = 20, $indexSize = 2);
            R::store($outpackage_line);
        }
    }
}