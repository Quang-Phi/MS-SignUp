<?php
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php";
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

$APPLICATION->SetTitle("MS KPI");

if (!$USER->IsAuthorized()) {
    LocalRedirect('/auth/');
}

$userID = (int)$USER->GetID();
$user = CUser::GetByID($userID)->Fetch();

if ($user) {
    $arSelect = array("ID", "NAME", "PROPERTY_68");
    $arFilter = array(
        "IBLOCK_ID" => 18,
        "ACTIVE_DATE" => "Y",
        "ACTIVE" => "Y",
    );
    $temp = CIBlockElement::GetList(
        array(),
        $arFilter,
        false,
        array("nPageSize" => 50),
        $arSelect
    )->arResult;
    $program = array();
    foreach ($temp as $key => $value) {
        if (($value["PROPERTY_68_VALUE"] == "PY") || ($value["PROPERTY_68_VALUE"] == "PTN")) {
            $program[$value["ID"]] = $value["NAME"];
        }
    }
} else {
    LocalRedirect('/');
}
?>

<!DOCTYPE html>
<html>

<head>
    <?php include "../../templates/head.php"; ?>
    <?php include "assets/style.php"; ?>
</head>

<body>
    <div id="form-kpi">
        <?php include "templates/form_kpi.php"; ?>
    </div>

    <?php include "../../templates/script.php"; ?>
    <?php include "templates/vue_kpi_script.php"; ?>
</body>

</html>

<?php require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"; ?>