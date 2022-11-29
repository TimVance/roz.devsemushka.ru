<?
$aMenuLinks = Array(
	Array(
		"Мой кабинет", 
		"/personal/index.php", 
		Array(), 
		Array(), 
		"TSolution::isPersonalSaleSectionAvailable()" 
	),
	Array(
		"Личные данные", 
		"/personal/private/", 
		Array(), 
		Array(), 
		"TSolution::isPersonalSectionAvailable()" 
	),
	Array(
		"Сменить пароль", 
		"/personal/change-password/", 
		Array(), 
		Array(), 
		"TSolution::isPersonalSectionAvailable()" 
	),
	Array(
		"Текущие заказы", 
		"/personal/orders/", 
		Array(), 
		Array(), 
		"TSolution::isPersonalSaleSectionAvailable()" 
	),
	Array(
		"История заказов", 
		"/personal/orders/?filter_history=Y", 
		Array(), 
		Array(), 
		"TSolution::isPersonalSaleSectionAvailable()" 
	),
	Array(
		"Профили заказов", 
		"/personal/profiles/", 
		Array(), 
		Array(), 
		"TSolution::isPersonalSaleSectionAvailable()" 
	),
	Array(
		"Подписки", 
		"/personal/subscribe/", 
		Array(), 
		Array(), 
		"Bitrix\\Main\\Loader::includeModule('subscribe') || Bitrix\\Main\\Loader::includeModule('catalog')" 
	),
	Array(
		"Избранные товары", 
		"/personal/favorite/", 
		Array(), 
		Array(), 
		"" 
	),
	Array(
		"Выйти", 
		"?logout=yes&login=yes", 
		Array(), 
		Array("class"=>"exit", "SVG_ICON"=>"header_icons.svg#logout-11-9"), 
		"\$GLOBALS['USER']->IsAuthorized()" 
	)
);
?>