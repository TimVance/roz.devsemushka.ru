<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
global $arMainPageOrder, $arTheme, $bigBannersIndexClass; //global array for order blocks

if($arMainPageOrder && is_array($arMainPageOrder)){
	$bActiveTheme = ($arTheme["THEME_SWITCHER"]["VALUE"] == 'Y');
	$indexType = $arTheme["INDEX_TYPE"]["VALUE"];
	$indexPageOptions = $arTheme['INDEX_TYPE']['SUB_PARAMS'][$indexType];

	foreach($arMainPageOrder as $key => $optionCode){
		$bShowBlock = ($bActiveTheme || ($indexPageOptions[$optionCode]["VALUE"] != "N"));
		$bBlockIndexClass = ($indexPageOptions[$optionCode]["VALUE"] == 'Y' ? '' : 'hidden');

		if($optionCode === 'BIG_BANNER_INDEX'){
			$bigBannersIndexClass = $bBlockIndexClass;
		}
		
		$currentBlockOptions = $indexPageOptions[$optionCode]['INDEX_BLOCK_OPTIONS'];
		$indexBlockClasses = CLite::getIndexBlockClasses($currentBlockOptions);
		
		$strTemplateName = $arTheme['TEMPLATE_PARAMS'][$arTheme['INDEX_TYPE']['VALUE']][$arTheme['INDEX_TYPE']['VALUE'].'_'.$optionCode.'_TEMPLATE']['VALUE'];
		$subtype = strtolower($optionCode);
		
		$bAjaxBlock = ($currentBlockOptions['AJAX']['ENABLE'] === 'Y' && $currentBlockOptions['AJAX']['FILE_PATH']);
		$ajaxPath = $bAjaxBlock ? str_replace('//', '/', SITE_DIR.$currentBlockOptions['AJAX']['FILE_PATH'].'/'.$subtype.'/'.$strTemplateName.'.php') : '';

		if($bShowBlock){
			?><div class="drag-block container <?=$optionCode?> <?=$bBlockIndexClass?>" data-class="<?=$subtype?>_drag" data-order="<?=++$key?>">				
				<div class="<?=$indexBlockClasses?> " <?=($ajaxPath ? 'data-file="'.$ajaxPath.'"' : '')?>>
					<?=TSolution::ShowPageType('mainpage', $subtype, $strTemplateName, true);?>
				</div>
			</div><?			
		}		
	}
}