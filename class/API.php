<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of API
 *
 * @author usersio
 */
use RedBeanPHP\Facade as R;

class API {
    /*
     * READ
     */

    /*
     * @TODO checker ce qu'est verification number et valid cost
     */
    
    static function costSheetExist($month){
        $bean=R::find('costsheet','month(date)=? AND visitor_id=?',[$month,$GLOBALS['user']['id']]);
        $exist=R::exportAll($bean);
        if(sizeof($exist)>0){
            return $exist[0]['id'];
        }else{
            return self::createCostSheet($GLOBALS['user']['id'], date('Y')."-".$month."-".date('d'));
        }
    }
    
    static function createCostSheet($idVisitor, $date) {
        $costSheet = R::dispense('costsheet');
        $costSheet->visitor_id = $idVisitor;
        $costSheet->modification_date = date('Y-m-d');
        $costSheet->status_id = 1;
        $costSheet->date = $date;
        R::store($costSheet);
        $id = array();
        $costSheet=R::exportAll($costSheet);
        $id['id']=$costSheet[0]['id'];
        return TokenManager::json_encode_token($id);
    }

    static function createPackageLine($month, $quantity, $packageCostId) {
        $costSheetId=self::costSheetExist($month);
        $packageLine = R::dispense('packageline');
        $packageLine->month = $month;
        $packageLine->quantity = $quantity;
        $packageLine->date = date('Y').'-'.$month.'-'.date('d');
        $packageLine->package_cost_id = $packageCostId;
        $packageLine->cost_sheet_id = $costSheetId;
        R::store($packageLine);
        return TokenManager::json_encode_token(R::exportAll($packageLine));
    }

    static function createOutPackageLine($date, $cost, $label) {
        $costSheetId=self::costSheetExist($month);
        $outPackageLine = R::dispense('outPackageLine');
        $outPackageLine->date = $date;
        $outPackageLine->cost = $cost;
        $outPackageLine->label = $label;
        $outPackageLine->cost_sheet_id = $costSheetId;
        R::store($outPackageLine);
        return TokenManager::json_encode_token(R::exportAll($outPackageLine));
    }

    static function createPackageCost($label, $cost) {
        $packageCost = R::dispense('packageCost');
        $packageCost->label = $label;
        $packageCost->cost = $cost;
        return TokenManager::json_encode_token(R::exportAll($packageCost));
    }

    /*
     * READ
     */

    static function readCostSheetsByMonth($month) {
        $costSheets = R::find('costsheet', 'month = ?', [$month]);
        return TokenManager::json_encode_token(R::exportAll($costSheets));
    }

    static function readCostSheetsByMonthVisitor($month, $matricule) {
        $costSheets = R::find('costsheet', 'month(date) = ? AND visitor_id= ?', [$month, $matricule]);
        //var_dump(R::exportAll($costSheets));
        //var_dump(json_encode(R::exportAll($costSheets)));
        return TokenManager::json_encode_token(R::exportAll($costSheets));
    }

    static function readVisitorById($id) {
        $visitor = R::getAll('select visitor.id, name, first_name, location, city, postal_code, label '
                        . 'from visitor, job where visitor.job_id = job.id and visitor.id=?', [$id]);
        return R::exportAll(R::convertToBeans('visitor', $visitor));
    }

    static function readAuth($login, $password) {
        $auth = R::getAll('select visitor.id, name, first_name, location, city, postal_code, label '
                        . 'from visitor, job where visitor.job_id = job.id and login = ? '
                        . 'and password = ?', [$login, $password]);
        return json_encode(R::exportAll(R::convertToBeans('auth', $auth)));
    }

    static function readCostSheetsByVisitor($matricule) {
        $costSheets = R::find('costsheet', 'visitor_id= ?', [$matricule]);
        return TokenManager::json_encode_token(R::exportAll($costSheets));
    }

    static function readPackageCost() {
        $packageCost = R::findAll('packagecost', 'ORDER BY label');
        return TokenManager::json_encode_token(R::exportAll($packageCost));
    }

    static function readCostSheetByStatus($status) {
        $costSheets = R::find('costsheet', 'status_id=?', [$status]);
        return TokenManager::json_encode_token(R::exportAll($costSheets));
    }

    static function readPackageLineByCostSheet($costSheet) {
        $packageLine = R::find('packageline', 'cost_sheet_id=?', [$costSheet]);
        return TokenManager::json_encode_token(R::exportAll($packageLine));
    }

    static function readOutPackageLineByCostSheet($costSheet) {
        $outPackageLine = R::find('outpackageline', 'cost_sheet_id=?', [$costSheet]);

        return TokenManager::json_encode_token(R::exportAll($outPackageLine));
    }

    static function readOutPackageLineByVisitorIdAndMonth($id, $month) {
        $outPackageLine = R::getAll('SELECT visitor.id,outpackageline.label, outpackageline.cost FROM outpackageline, costsheet, visitor '
                        . 'where visitor.id = costsheet.visitor_id and outpackageline.cost_sheet_id = costsheet.id '
                        . 'and visitor.id = ? and month(outpackageline.date) = ?', [$id, $month]);

        return TokenManager::json_encode_token($outPackageLine);
    }

    static function readPackageLineByVisitorIdAndMonth($id, $month) {
        $outPackageLine = R::getAll('SELECT visitor.id,packagecost.label, packagecost.cost, packageline.quantity FROM packagecost, packageline, costsheet, visitor '
                        . 'where visitor.id = costsheet.visitor_id and packageline.cost_sheet_id = costsheet.id and packagecost.id = packageline.package_cost_id '
                        . 'and visitor.id = ? and month(packageline.date) = ?', [$id, $month]);

        return TokenManager::json_encode_token($outPackageLine);
    }

    /*
     * UPDATE
     * @TODO changer la date lors de la modif
     */

    static function updateCostSheetStatus($status, $idCostSheet) {
        $costSheet = R::load('costsheet', $idCostSheet);
        $costSheet->status_id = $status;
        R::store($costSheet);
        return TokenManager::json_encode_token(R::exportAll($costSheet));
    }

    static function updateCostSheet($idCostSheet, $justificationNumber, $validCost) {
        $costSheet = R::load('costsheet', $idCostSheet);
        $costSheet->justification_number = $justificationNumber;
        $costSheet->valid_cost = $validCost;
        R::store($costSheet);
        return TokenManager::json_encode_token(R::exportAll($costSheet));
    }

    static function updateOutPackageLine($id, $cost, $label) {
        $outPackageLine = R::load('outpackageline', $id);
        $outPackageLine->cost = $cost;
        $outPackageLine->label = $label;
        R::store($outPackageLine);
        return TokenManager::json_encode_token(R::exportAll($outPackageLine));
    }

    static function updatePackageLine($id, $quantity, $packageCostId) {
        $packageLine = R::load('packageline', $id);
        $packageLine->quantity = $quantity;
        $packageLine->package_cost_id = $packageCostId;
        R::store($packageLine);
        return TokenManager::json_encode_token(R::exportAll($packageLine));
    }

    static function updatePackageCost($id, $cost, $label) {
        $packageCost = R::load('packagecost', $id);
        $packageCost->cost = $cost;
        $packageCost->label = $label;
        R::store($packageCost);
        return TokenManager::json_encode_token(R::exportAll($packageCost));
    }

    /*
     * DELETE
     */

    static function deleteCostSheetYear($month) {
        $costSheets = R::find('costsheet', 'month=?', [$month]);
        R::trashAll($costSheets);
    }

    static function deleteOutPackageLine($costSheetId) {
        $outPackageLine = R::find('outpackageline', 'cost_sheet_id=?', [$costSheetId]);
        R::trashAll($outPackageLine);
    }

    static function deletePackageLine($costSheetId) {
        $packageLine = R::find('packageline', 'cost_sheet_id=?', [$costSheetId]);
        R::trashAll($packageLine);
    }

    static function deletePackageCost($id) {
        $packageCost = R::find('packagecost', 'id=?', [$id]);
        R::trashAll($packageCost);
    }

}
