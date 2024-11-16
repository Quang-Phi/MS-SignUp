<?php
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php";
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

$APPLICATION->SetTitle("MS unRegister");

if (!$USER->IsAuthorized()) {
    LocalRedirect('/auth/');
}

$userID = (int)$USER->GetID();
$user = CUser::GetByID($userID)->Fetch();

if ($user) {
    $userFullName =  htmlspecialchars($user["NAME"]) . " " . htmlspecialchars($user["LAST_NAME"]);

    $rsDepartments = CIBlockSection::GetList(array(), array("ID" => $user["UF_DEPARTMENT"]));
    $departmentLabels = [];

    while ($arDepartment = $rsDepartments->Fetch()) {
        $departmentLabels[] = $arDepartment["NAME"];
    }

    $msId = $user["UF_USR_1712631669169"];
    $enumList = CUserFieldEnum::GetList(array(), array('ID' => $msId));
    $typeMSLabel = '';

    while ($enum = $enumList->Fetch()) {
        if ($enum['ID'] == $msId) {
            $typeMSLabel = $enum['VALUE'];
            break;
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
    <div id="form-unregister">
        <?php include "templates/form_unregister.php"; ?>
    </div>

    <?php include "../../templates/script.php"; ?>
    <?php include "templates/vue_unregister_script.php"; ?>
</body>

</html>

<?php require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"; ?>