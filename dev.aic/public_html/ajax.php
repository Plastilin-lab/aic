<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
CModule::IncludeModule('iblock');

$arResult = ['success' => false, 'errors' => [], 'error_msg' => ''];
$payload = json_decode(file_get_contents("php://input"),true);

if (isset($payload['method']) && $payload['method'] == 'map_points') {
    $arResult['points'] = [];

    $filter = ['IBLOCK_ID' => 7, 'ACTIVE' => 'Y'];
    $res = CIBlockElement::GetList([], $filter, ['ID', 'PROPERTY_COORD', 'PROPERTY_TYPE']);
    while ($fields = $res->GetNext()) {
        $arResult['points'][] = $fields;
    }
}

if (isset($payload['method']) && $payload['method'] == 'vacancies') {
    $arResult['vacancies'] = [];

    $filter = ['IBLOCK_ID' => 6, 'ACTIVE' => 'Y'];
    $res = CIBlockElement::GetList(['sort' => 'asc'], $filter, ['ID', 'NAME', 'PREVIEW_TEXT', 'PREVIEW_PICTURE']);
    while ($fields = $res->GetNext()) {
        $arResult['vacancies'][] = [
            'id' => $fields['ID'],
            'name' => $fields['NAME'],
            'text' => $fields['PREVIEW_TEXT'],
            'picture' => CFile::GetPath($fields['PREVIEW_PICTURE']),
        ];
    }
}

if (isset($payload['method']) && $payload['method'] == 'banners') {
    $arResult['banners'] = [];

    $filter = ['IBLOCK_ID' => 5, 'ACTIVE' => 'Y'];
    $res = CIBlockElement::GetList(['sort' => 'asc'], $filter, ['ID', 'NAME', 'PROPERTY_FILE']);
    while ($fields = $res->GetNext()) {
        $filename = CFile::GetPath($fields['PROPERTY_FILE_VALUE']);
        $arResult['banners'][] = [
            'name' => $fields['NAME'],
            'file' => $filename,
            'ext' => pathinfo($filename, PATHINFO_EXTENSION),
        ];
    }
}

if (isset($payload['method']) && $payload['method'] == 'instagram_feed') {
    $arResult['photos'] = [];
    $arResult['total'] = 0;


    $accessToken = "IGQVJYMGYwX3B6SEhlNUJwRWFEa1BvaXVRS1M4aWIwNXB1bmxXQTJGS3J6YmNwMWZAaSy0yYTNBZAEIwUTFMNzRBMk1BTWR1cnZAHcVVwb2VoQ1haNkJHVVBHNy00V0VaWmNYb0NZAZAWR6REE4TVVOb3BuTAZDZD"; // получаем токен из базы
    $instaFeed = array();
    if (!empty($accessToken)) {
        // Получаем ленту
        $url = "https://graph.instagram.com/me/media?fields=id,media_type,media_url,caption,timestamp,thumbnail_url,permalink,children{fields=id,media_url,thumbnail_url,permalink}&limit=50&access_token=" . $accessToken;
        $instagramCnct = curl_init(); // инициализация cURL подключения
        curl_setopt($instagramCnct, CURLOPT_URL, $url); // подключаемся
        curl_setopt($instagramCnct, CURLOPT_RETURNTRANSFER, 1); // просим вернуть результат
        $media = json_decode(curl_exec($instagramCnct)); // получаем и декодируем данные из JSON
        curl_close($instagramCnct); // закрываем соединение

        foreach ($media->data as $mediaObj) {
            if (!empty($mediaObj->children->data)) {
                foreach ($mediaObj->children->data as $children) {
                    $instaFeed[$children->id]['img'] = $children->thumbnail_url ?: $children->media_url;
                    $instaFeed[$children->id]['link'] = $children->permalink;
                }
            } else {
                $instaFeed[$mediaObj->id]['img'] = $mediaObj->thumbnail_url ?: $mediaObj->media_url;
                $instaFeed[$mediaObj->id]['link'] = $mediaObj->permalink;
            }
        }
    }

    $arResult['temp'] = $instaFeed;

    $arResult['total'] = 479;
    $item = ['link' => 'https://www.instagram.com/p/CTSIQkArIGW/', 'image' => '/_images/example_image.jpeg'];
    for ($i = 0; $i < $payload['pageSize']; $i++) {
        $arResult['photos'][] = $item;
    }
}

if (isset($_REQUEST['method']) && $_REQUEST['method'] == 'questionnaire') {
    $el = new CIBlockElement;

    $props = [
        'VACANCY' => $_REQUEST['id'],
        'BIRTHDAY' => trim(htmlspecialchars($_REQUEST['birthday'])),
        'GENDER' => trim(htmlspecialchars($_REQUEST['gender'])),
        'PHONE' => trim(htmlspecialchars($_REQUEST['phone'])),
        'EMAIL' => trim(htmlspecialchars($_REQUEST['email'])),
    ];
    $props['RESUME'][0] = ["VALUE" => ["TEXT" => trim(htmlspecialchars($_REQUEST['text'])), "TYPE" => "text"]];
    if (isset($_FILES['file'])) {
        $props['FILE'] = $_FILES['file'];
    }

    $arResult['temp'] = $_FILES['file'];
    $fields = [
        "IBLOCK_SECTION_ID" => false,
        "IBLOCK_ID" => 8,
        "PROPERTY_VALUES" => $props,
        "NAME" => trim(htmlspecialchars($_REQUEST['fio'])),
        "ACTIVE" => "Y",
    ];

    if ($questionnaireId = $el->Add($fields)) {
        $arResult['success'] = true;
    } else {
        $arResult['error'] = $el->LAST_ERROR;
    }

}

echo json_encode($arResult);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');