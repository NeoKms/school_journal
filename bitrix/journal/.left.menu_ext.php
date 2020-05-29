<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
if(!CModule::IncludeModule("iblock")) die();
$res=CIBlockElement::GetList(['NAME'=>'ASC'],["IBLOCK_ID"=>11],false,false,['ID','NAME']);
$menu=[];
while($obj=$res->Fetch()){
    $menu[]=[
        $obj['NAME'],"{$obj['ID']}",[]
    ];
}
$aMenuLinksExt = $menu;

$aMenuLinks = array_merge($aMenuLinks, $aMenuLinksExt);
?>
