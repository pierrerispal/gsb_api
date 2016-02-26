<?php

//COSTSHEETMANAGMENT
require 'class/TokenManager.php';
$app->post('/', function() {

})->name('home');
if (isset($_POST['token'])) {
    $GLOBALS['user'] = TokenManager::checkToken($_POST['token'], '1200');
}

$authenticateForRole = function ( $roles = array('Visiteur') ) {
    return function () use ( $roles ) {
        if (!isset($GLOBALS['user']['label'])) {
            $app = \Slim\Slim::getInstance();
            $app->redirectTo('home');
        } else {
            if (!in_array($GLOBALS['user']['label'], $roles)) {
                $app = \Slim\Slim::getInstance();
                $app->redirectTo('home');
            }
        }
    };
};

/*
 * CREATE
 */

$app->get('/test', function(){
});

//CREATE A COST SHEET
$app->post('/create/costsheet/:month', $authenticateForRole(array('Visiteur')), function($date) {
    echo API::createCostSheet($GLOBALS['user']['id'], $date);
});

//CREATE DE PACKAGELINE
$app->post('/create/packageLine/:month/:quantity/:packageCostId', $authenticateForRole(array('Visiteur')), function($month, $quantity, $packageCostId) {
    echo API::createPackageLine($month, $quantity, $packageCostId);
});

//CREATE AN OUTPACKAGE LINE
$app->post('/create/outPackageLine/:date/:cost/:label', $authenticateForRole(array('Visiteur')), function($date, $cost, $label, $costSheetId) {
    echo API::createOutPackageLine($date, $cost, $label, $costSheetId);
});

//CREATE A PACKAGECOST
$app->post('/create/packageCost/:label/:cost', $authenticateForRole(array('Comptable')), function($label, $cost) {
    echo API::createPackageCost($label, $cost);
});

/*
 * READ
 */

//VISITOR BY MATRICULE @TODO appel a l'API RH
$app->post('/visitor/:id', $authenticateForRole(array('Comptable')), function($id) {
    
});

//ALL COST SHEET BY MONTH
$app->post('/costSheet/month/:month', $authenticateForRole(array('Comptable')), function($month) {
    echo API::readCostSheetsByMonth($month);
});

//COST SHEET BY VISITOR & BY MONTH
$app->post('/costSheet/visitor/month/:month', $authenticateForRole(array('Visiteur', 'Comptable')), function($month) {
    echo API::readCostSheetsByMonthVisitor($month, $GLOBALS['user']['id']);
});

//ALL COST SHEET FOR ONE VISITOR
$app->post('/costSheet/visitor', $authenticateForRole(array('Visiteur', 'Comptable')), function() {
    echo API::readCostSheetsByVisitor($GLOBALS['user']['id']);
});

$app->map('/auth/:login/:password', function($login, $password) {
    $user = API::readAuth($login, $password);
    $userTable = json_decode($user, true);
    if (!empty($userTable)) { //Si le login / password concordent
        if (!TokenManager::hasToken($userTable[0]['id'], '1200')) { //Si aucun token n'a été trouvé pour l'utilisateur
            $GLOBALS['user'] = $userTable[0];
            TokenManager::removeTokens($userTable[0]['id']);  //Suppression des tokens de l'utilisateur
            $token = TokenManager::generateToken($userTable[0]['id']); //Generation d'un token
            $userTable[0]['token'] = $token; //Ajout du champ token dans le tableau
        } else {
            $GLOBALS['user'] = $userTable[0];
            $userTable[0]['token'] = TokenManager::getToken($userTable[0]['id'], '1200'); //Sinon, si token valide : on met le token existant dans le tableau
        }
        
    }
    $userJSON = json_encode($userTable); //Encode en json le tableau avec token + info utilisateur

    echo $userJSON;
})->via('GET', 'POST');

$app->post('/visitor/:id', $authenticateForRole(array('Comptable')), function($id) {
    echo API::readVisitorById($id);
});

//ALL PACKAGE COST
$app->post('/costSheet/packageCost', $authenticateForRole(array('Visiteur', 'Comptable')), function() {
    echo API::readPackageCost();
});

//ALL COST SHEET BY STATUS
$app->post('/costSheet/Status/:status', $authenticateForRole(array('Visiteur', 'Comptable')), function($status) {
    echo API::readCostSheetByStatus($status);
});

//ALL PACKAGE LINE FROM A COST SHEET
$app->post('/packageLine/:costSheetId', $authenticateForRole(array('Visiteur', 'Comptable')), function($costSheetId) {
    echo API::readPackageLineByCostSheet($costSheetId);
});

//ALL OUT PACKAGE LINE FROM A COST SHEET
$app->post('/outPackageLine/:costSheetId', $authenticateForRole(array('Visiteur', 'Comptable')), function($costSheetId) {
    echo API::readOutPackageLineByCostSheet($costSheetId);
});

$app->post('/outPackageLine/month/:month', $authenticateForRole(array('Visiteur', 'Comptable')), function($month) {
    echo API::readOutPackageLineByVisitorIdAndMonth($GLOBALS['user']['id'], $month);
});

$app->post('/packageLine/month/:month', $authenticateForRole(array('Visiteur', 'Comptable')), function($month) {
    echo API::readPackageLineByVisitorIdAndMonth($GLOBALS['user']['id'], $month);
});

/*
 * UPDATE
 */

//UPDATE COST SHEET STATUS
$app->post('/update/costSheet/status/:status/idCostSheet/:idCostSheet', $authenticateForRole(array('Visiteur', 'Comptable')), function($status, $idCostSheet) {
    echo API::updateCostSheetStatus($status, $idCostSheet);
});

//UPDATE A COST SHEET
$app->post('/update/costsheet/:id/:justificationNumber/:validCost', function($id, $justificationNumber, $validCost) {
    echo API::updateCostSheet($id, $justificationNumber, $validCost);
});

//UPDATE AN OUTPACKAGE LINE
$app->post('/update/outpackageLine/:id/:cost/:label', $authenticateForRole(array('Visiteur', 'Comptable')), function($id, $cost, $label) {
    echo API::updateOutPackageLine($id, $cost, $label);
});

//UPDATE A PACKAGE LINE
$app->post('/update/packageLine/:id/:quantity/:packageCostId', $authenticateForRole(array('Visiteur', 'Comptable')), function($id, $quantity, $packageCostId) {
    echo API::updatePackageLine($id, $quantity, $packageCostId);
});

//UPDATE A PACKAGE COST
$app->post('/update/packageCost/:id/:cost/:label', $authenticateForRole(array('Comptable')), function($id, $cost, $label) {
    echo API::updatePackageCost($id, $cost, $label);
});

/*
 * DELETE
 */

//DELETE ALL COSTSHEET OLDER THAN A YEAR
$app->post('/delete/costSheet/month/:month', $authenticateForRole(array('Comptable')), function($month) {
    API::deleteCostSheetYear($month);
});

//DELETE OUTPACKAGE LINE FROM A COSTSHEET
$app->post('/delete/outpackageLine/costSheetId/:costSheetId', $authenticateForRole(array('Comptable')), function($costSheetId) {
    API::deleteOutPackageLine($costSheetId);
});

//DELETE PACKAGE LINE FROM A COSTSHEET
$app->post('/delete/packageLine/costSheetId/:costSheetId', $authenticateForRole(array('Visiteur', 'Comptable')), function($costSheetId) {
    API::deletePackageLine($costSheetId);
});


//DELETE ALL LINES FROM A COSTSHEET
$app->post('/delete/Line/costSheetId/:costSheetId', $authenticateForRole(array('Comptable')), function($costSheetId) {
    API::deleteOutPackageLine($costSheetId);
    API::deletePackageLine($costSheetId);
});

//DELETE A PACKAGE COST
$app->post('/delete/packageCost/:id', $authenticateForRole(array('Comptable')), function($id) {
    API::deletePackageCost($id);
});
